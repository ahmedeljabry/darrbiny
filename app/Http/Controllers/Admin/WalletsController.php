<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserRequest;
use App\Modules\Wallet\Services\WalletService;
use App\Support\Fees;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletsController extends BaseController
{
    public function __construct(private readonly WalletService $wallets) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $countryId = trim((string) $request->query('country_id', ''));

        $users = User::query()
            ->with(['country', 'bankCountry'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_with_cc', 'like', "%{$search}%");
                });
            })
            ->when($countryId !== '', function ($query) use ($countryId): void {
                $query->where(function ($userQuery) use ($countryId): void {
                    $userQuery->where('country_id', $countryId)
                        ->orWhere('bank_country_id', $countryId);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $appFeePercent = Fees::appFeePercent();

        return view('admin.wallets.index', compact('users', 'countries', 'appFeePercent', 'search', 'countryId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'amount' => ['required_without:course_reference', 'nullable', 'numeric', 'min:0.01'],
            'apply_app_fee' => ['nullable', 'boolean'],
            'course_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $creditedAmountMinor = $this->resolveCreditedAmountMinor($data);
        $notes = $this->resolveCreditNotes($data);

        $this->wallets->addAdjustment($user, WalletAmount::minorToMajor($creditedAmountMinor), $request->user(), $notes);

        $newBalanceMinor = $user->fresh()->pointsBalanceMinor();

        return back()->with('status', $this->buildStoreStatusMessage($data, $creditedAmountMinor, $newBalanceMinor));
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($id);
        $this->wallets->setBalance($user, $data['balance'], $request->user(), $data['notes'] ?? null);

        return back()->with('status', 'تم تحديث رصيد المحفظة بنجاح');
    }

    private function resolveCreditNotes(array $data): ?string
    {
        $courseReference = trim((string) ($data['course_reference'] ?? ''));
        if ($courseReference !== '') {
            $booking = $this->resolveCourseBooking($data);

            return 'إضافة مستحقات كورس رقم '.($booking->formatted_order_number ?? $booking->order_number ?? $booking->id);
        }

        return $data['notes'] ?? null;
    }

    private function resolveCreditedAmountMinor(array $data): int
    {
        if ($this->shouldResolveCompletedCoursePayout($data)) {
            return $this->resolveCompletedCoursePayoutAmountMinor($data);
        }

        $grossAmountMinor = WalletAmount::majorToMinor($data['amount']);

        if (! $this->shouldApplyAppFee($data)) {
            return $grossAmountMinor;
        }

        $appFeePercent = max(0, Fees::appFeePercent());
        $appFeeAmountMinor = (int) round($grossAmountMinor * ($appFeePercent / 100));
        $appFeeAmountMinor = min($appFeeAmountMinor, $grossAmountMinor);

        return max(0, $grossAmountMinor - $appFeeAmountMinor);
    }

    private function shouldApplyAppFee(array $data): bool
    {
        if (trim((string) ($data['course_reference'] ?? '')) !== '') {
            return true;
        }

        return filter_var($data['apply_app_fee'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function buildStoreStatusMessage(array $data, int $creditedAmountMinor, int $newBalanceMinor): string
    {
        $creditedAmount = WalletAmount::formatMajor(WalletAmount::minorToMajor($creditedAmountMinor), 2, true);
        $newBalance = WalletAmount::formatMajor(WalletAmount::minorToMajor($newBalanceMinor), 2, true);

        if (! $this->shouldApplyAppFee($data)) {
            return "تم إضافة الرصيد إلى المحفظة. الرصيد الحالي: {$newBalance}";
        }

        return "تم إضافة صافي {$creditedAmount} إلى المحفظة بعد خصم رسوم التطبيق. الرصيد الحالي: {$newBalance}";
    }

    private function shouldResolveCompletedCoursePayout(array $data): bool
    {
        return trim((string) ($data['course_reference'] ?? '')) !== '';
    }

    private function resolveCompletedCoursePayoutAmountMinor(array $data): int
    {
        $booking = $this->resolveCourseBooking($data);
        $payment = $booking->latestSuccessfulFullPayment();

        abort_unless($booking->status === UserRequest::STATUS_COMPLETED, 422, 'يجب أن يكون الكورس مكتملًا قبل إضافة مستحقاته إلى المحفظة');
        abort_unless($payment instanceof Payment, 422, 'لا توجد دفعة كاملة ناجحة لهذا الكورس');
        abort_unless((string) $booking->trainer_id === (string) $data['user_id'], 422, 'هذا الكورس لا يخص هذا المدرب');

        return max(0, (int) $payment->trainer_net_minor);
    }

    private function resolveCourseBooking(array $data): UserRequest
    {
        $courseReference = trim((string) ($data['course_reference'] ?? ''));
        $courseReference = ltrim($courseReference, '#');
        $orderNumber = UserRequest::normalizeOrderNumberSearch($courseReference);

        return UserRequest::query()
            ->with('payments')
            ->where('id', $courseReference)
            ->when($orderNumber !== null, fn ($query) => $query->orWhere('order_number', $orderNumber))
            ->firstOrFail();
    }
}
