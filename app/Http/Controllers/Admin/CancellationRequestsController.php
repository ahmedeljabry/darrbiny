<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\CancellationRequestsExport;
use App\Models\CancellationRequest;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use App\Notifications\CancellationRequestNotification;
use App\Notifications\WalletBalanceAddedNotification;
use App\Support\ReportCurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class CancellationRequestsController extends BaseController
{
    public function index(Request $request)
    {
        $this->syncMissingCancellationRequests();

        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        $q = CancellationRequest::with([
            'userRequest',
            'userRequest.user',
            'userRequest.plan',
            'userRequest.payments' => fn ($query) => $query->latest(),
            'user',
            'processedBy'
        ]);

        if ($status) {
            $q->where('status', $status);
        } else {
            $q->orderByRaw(
                'CASE WHEN status = ? THEN 0 ELSE 1 END',
                [CancellationRequest::STATUS_PENDING]
            );
        }

        if ($search !== '') {
            $q->where(function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_with_cc', 'like', "%{$search}%");
                })->orWhereHas('userRequest', function ($requestQuery) use ($search): void {
                    $normalizedOrderNumber = UserRequest::normalizeOrderNumberSearch($search);

                    $requestQuery->whereRaw('CAST(order_number as CHAR) like ?', ["%{$search}%"]);

                    if ($normalizedOrderNumber !== null) {
                        $requestQuery->orWhere('order_number', $normalizedOrderNumber);
                    }
                });
            });
        }

        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        $statsRequests = (clone $q)->get();
        $converter = app(ReportCurrencyConverter::class);
        $totalRefundMinor = (int) $statsRequests->sum(function (CancellationRequest $cancellation) use ($converter): int {
            return $converter->convertMinor(
                (int) $cancellation->refund_amount_minor,
                $cancellation->userRequest?->currency ?? ReportCurrencyConverter::REPORT_CURRENCY
            );
        });
        $movementsCount = $statsRequests->count();

        if ($request->query('export') === 'excel') {
            $allRequests = (clone $q)->latest()->get();

            return Excel::download(
                new CancellationRequestsExport($allRequests),
                'cancellation-requests-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $requests = $q->latest()->paginate(20)->withQueryString();

        return view('admin.cancellation-requests.index', compact(
            'requests',
            'status',
            'search',
            'dateFrom',
            'dateTo',
            'totalRefundMinor',
            'movementsCount'
        ));
    }

    public function show(string $id)
    {
        $this->syncMissingCancellationRequests($id);

        $cancellation = CancellationRequest::with([
            'userRequest',
            'userRequest.user',
            'userRequest.plan',
            'userRequest.plan.country',
            'userRequest.payments' => fn ($query) => $query->latest(),
            'user',
            'processedBy'
        ])
            ->where(function ($query) use ($id) {
                $query->whereKey($id)
                    ->orWhere('user_request_id', $id);
            })
            ->firstOrFail();

        return view('admin.cancellation-requests.show', compact('cancellation'));
    }

    public function approve(Request $request, string $id)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cancellation = CancellationRequest::with(['userRequest', 'userRequest.user', 'userRequest.trainer', 'userRequest.plan', 'userRequest.payments'])
            ->findOrFail($id);

        abort_unless($cancellation->status === CancellationRequest::STATUS_PENDING, 422, 'Cancellation already processed');

        DB::transaction(function () use ($cancellation, $validated, $request) {
            $cancellation->status = CancellationRequest::STATUS_APPROVED;
            $cancellation->admin_notes = $validated['admin_notes'] ?? null;
            $cancellation->processed_by = $request->user()->id;
            $cancellation->processed_at = now();

            $userRequest = $cancellation->userRequest;
            $userRequest->status = UserRequest::STATUS_CANCELLED;
            $userRequest->save();

            $totalPaid = $userRequest->totalSuccessfulPaymentsMinor();
            $packageValue = $userRequest->plan?->price_min ?? 0;
            $cancellation->refund_amount_minor = (int) $totalPaid;
            $cancellation->save();
            
            if ($totalPaid > 0) {
                $refundAmountMinor = (int) $totalPaid;
                $user = $userRequest->user;
                $user->increment('points_balance', $refundAmountMinor);

                // Create wallet transaction record
                $walletTransaction = WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $refundAmountMinor,
                    'type' => WalletTransaction::TYPE_REFUND,
                    'status' => WalletTransaction::STATUS_APPROVED,
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                    'notes' => "إرجاع مبلغ طلب الإلغاء #{$cancellation->id} - قيمة الباقة: {$packageValue}",
                ]);

                $user->notify(new WalletBalanceAddedNotification(
                    $refundAmountMinor,
                    'cancellation_refund',
                    $walletTransaction->id
                ));
            }

            // Send notifications
            if ($userRequest->user) {
                Notification::send($userRequest->user, new CancellationRequestNotification($cancellation));
            }
            if ($userRequest->trainer) {
                Notification::send($userRequest->trainer, new CancellationRequestNotification($cancellation));
            }
        });

        return back()->with('status', 'تم قبول طلب الإلغاء وإرجاع المبلغ إلى محفظة المستخدم');
    }

    public function reject(Request $request, string $id)
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        $cancellation = CancellationRequest::findOrFail($id);

        abort_unless($cancellation->status === CancellationRequest::STATUS_PENDING, 422, 'Cancellation already processed');

        $cancellation->status = CancellationRequest::STATUS_REJECTED;
        $cancellation->admin_notes = $validated['admin_notes'];
        $cancellation->processed_by = $request->user()->id;
        $cancellation->processed_at = now();
        $cancellation->save();

        return back()->with('status', 'تم رفض طلب الإلغاء');
    }

    private function syncMissingCancellationRequests(?string $userRequestId = null): void
    {
        $cancelledRequests = UserRequest::query()
            ->where('status', UserRequest::STATUS_CANCELLED)
            ->when($userRequestId, function ($query, string $id) {
                $query->where(function ($nested) use ($id) {
                    $nested->where('id', $id)
                        ->orWhereHas('cancellationRequest', fn ($cancellationQuery) => $cancellationQuery->where('id', $id));
                });
            })
            ->doesntHave('cancellationRequest')
            ->get();

        foreach ($cancelledRequests as $userRequest) {
            $this->createBackfilledCancellationRequest($userRequest);
        }
    }

    private function createBackfilledCancellationRequest(UserRequest $userRequest): CancellationRequest
    {
        $timestamp = $userRequest->updated_at ?? $userRequest->created_at ?? now();

        $cancellation = new CancellationRequest([
            'user_request_id' => $userRequest->id,
            'user_id' => $userRequest->user_id,
            'reason' => 'تم إلغاء الدورة من النظام',
            'status' => CancellationRequest::STATUS_APPROVED,
            'admin_notes' => 'تمت مزامنة سجل إلغاء تلقائيا لأن الحجز بحالة ملغي.',
            'refund_amount_minor' => $userRequest->totalSuccessfulPaymentsMinor(),
        ]);

        $cancellation->processed_at = $timestamp;
        $cancellation->created_at = $timestamp;
        $cancellation->updated_at = $timestamp;
        $cancellation->save();

        return $cancellation;
    }
}
