<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\CancellationRequest;
use App\Models\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class CancellationRequestsController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        
        $q = CancellationRequest::with([
            'userRequest',
            'userRequest.user',
            'userRequest.plan',
            'user',
            'processedBy'
        ]);

        if ($status) {
            $q->where('status', $status);
        } else {
            // Default: show pending first
            $q->where('status', CancellationRequest::STATUS_PENDING);
        }

        $requests = $q->latest()->paginate(20)->withQueryString();

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
            'userRequest.payments',
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

        $cancellation = CancellationRequest::with(['userRequest', 'userRequest.user', 'userRequest.payments'])
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

            $totalPaid = $userRequest->total_paid_minor;
            if ($totalPaid > 0) {
                $refundAmount = (int) round($totalPaid / 100);
                $user = $userRequest->user;
                $user->increment('points_balance', $refundAmount);
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

