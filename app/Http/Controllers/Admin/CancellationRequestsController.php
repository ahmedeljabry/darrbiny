<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\CancellationRequestsExport;
use App\Models\CancellationRequest;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use App\Notifications\CancellationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class CancellationRequestsController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        
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

        $requests = $q->latest()->paginate(20)->withQueryString();

        if ($request->query('export') === 'excel') {
            $allRequests = CancellationRequest::with([
                'userRequest',
                'userRequest.user',
                'userRequest.plan',
                'userRequest.payments' => fn ($query) => $query->latest(),
                'user',
                'processedBy'
            ])
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->get();

            return Excel::download(
                new CancellationRequestsExport($allRequests),
                'cancellation-requests-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        return view('admin.cancellation-requests.index', compact('requests', 'status'));
    }

    public function show(string $id)
    {
        $cancellation = CancellationRequest::with([
            'userRequest',
            'userRequest.user',
            'userRequest.plan',
            'userRequest.plan.country',
            'userRequest.plan.city',
            'userRequest.payments' => fn ($query) => $query->latest(),
            'user',
            'processedBy'
        ])->findOrFail($id);

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
            $cancellation->save();

            $userRequest = $cancellation->userRequest;
            $userRequest->status = UserRequest::STATUS_CANCELLED;
            $userRequest->save();

            $totalPaid = $userRequest->totalSuccessfulPaymentsMinor();
            $packageValue = $userRequest->plan?->price_min ?? 0;
            
            if ($totalPaid > 0) {
                $refundAmount = (int) round($totalPaid / 100);
                $user = $userRequest->user;
                $user->increment('points_balance', $refundAmount);

                // Create wallet transaction record
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $refundAmount,
                    'type' => WalletTransaction::TYPE_REFUND,
                    'status' => WalletTransaction::STATUS_APPROVED,
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                    'notes' => "إرجاع مبلغ طلب الإلغاء #{$cancellation->id} - قيمة الباقة: {$packageValue}",
                ]);
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
}
