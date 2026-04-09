<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Support\Fees;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletsController extends BaseController
{
    public function __construct(private readonly WalletService $wallets) {}

    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        $appFeePercent = Fees::appFeePercent();

        return view('admin.wallets.index', compact('users', 'appFeePercent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'apply_app_fee' => ['nullable', 'boolean'],
            'course_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $creditedAmount = $this->resolveCreditedAmount($data);
        $notes = $this->resolveCreditNotes($data);

        $this->wallets->addAdjustment($user, $creditedAmount, $request->user(), $notes);

        $newBalance = (int) $user->fresh()->points_balance;

        return back()->with('status', $this->buildStoreStatusMessage($data, $creditedAmount, $newBalance));
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'balance' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($id);
        $this->wallets->setBalance($user, (int) $data['balance'], $request->user(), $data['notes'] ?? null);

        return back()->with('status', 'تم تحديث رصيد المحفظة بنجاح');
    }

    private function resolveCreditNotes(array $data): ?string
    {
        $courseReference = trim((string) ($data['course_reference'] ?? ''));
        if ($courseReference !== '') {
            return 'إضافة مستحقات كورس رقم ' . $courseReference;
        }

        return $data['notes'] ?? null;
    }

    private function resolveCreditedAmount(array $data): int
    {
        $grossAmount = (int) $data['amount'];

        if (! $this->shouldApplyAppFee($data)) {
            return $grossAmount;
        }

        $appFeePercent = max(0, Fees::appFeePercent());
        $appFeeAmount = (int) round($grossAmount * ($appFeePercent / 100));
        $appFeeAmount = min($appFeeAmount, $grossAmount);

        return max(0, $grossAmount - $appFeeAmount);
    }

    private function shouldApplyAppFee(array $data): bool
    {
        if (trim((string) ($data['course_reference'] ?? '')) !== '') {
            return true;
        }

        return filter_var($data['apply_app_fee'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function buildStoreStatusMessage(array $data, int $creditedAmount, int $newBalance): string
    {
        if (! $this->shouldApplyAppFee($data)) {
            return "تم إضافة الرصيد إلى المحفظة. الرصيد الحالي: {$newBalance}";
        }

        return "تم إضافة صافي {$creditedAmount} إلى المحفظة بعد خصم رسوم التطبيق. الرصيد الحالي: {$newBalance}";
    }
}
