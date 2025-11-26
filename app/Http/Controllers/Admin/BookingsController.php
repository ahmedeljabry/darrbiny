<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\UserRequest;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class BookingsController extends BaseController
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $status = $request->query('status');
        $planId = $request->query('plan_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $bookings = UserRequest::with(['user', 'plan', 'plan.country', 'plan.city'])
            ->when($q, function ($query) use ($q) {
                $query->whereHas('user', function ($userQuery) use ($q) {
                    $userQuery->where('name', 'like', "%{$q}%")
                      ->orWhere('phone_with_cc', 'like', "%{$q}%");
                });
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($planId, fn($query) => $query->where('plan_id', $planId))
            ->when($dateFrom, fn($query) => $query->whereDate('start_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('start_date', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $plans = Plan::active()->orderBy('title')->get();
        $statuses = [
            UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            UserRequest::STATUS_AWAITING_OFFERS => 'في انتظار العروض',
            UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            UserRequest::STATUS_PAID => 'مدفوع',
            UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            UserRequest::STATUS_COMPLETED => 'مكتمل',
            UserRequest::STATUS_CANCELLED => 'ملغي',
        ];

        return view('admin.bookings.index', compact('bookings', 'plans', 'statuses', 'q', 'status', 'planId', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $booking = UserRequest::with([
            'user',
            'plan',
            'plan.country',
            'plan.city',
            'plan.features'
        ])->findOrFail($id);

        $offers = \App\Models\TrainerOffer::where('user_request_id', $id)
            ->with('trainer')
            ->latest()
            ->get();

        $payments = \App\Models\Payment::where('user_request_id', $id)
            ->latest()
            ->get();

        $trainingDays = \App\Models\TrainingDay::where('user_request_id', $id)
            ->with('trainer')
            ->latest()
            ->get();

        $statuses = [
            UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            UserRequest::STATUS_AWAITING_OFFERS => 'في انتظار العروض',
            UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            UserRequest::STATUS_PAID => 'مدفوع',
            UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            UserRequest::STATUS_COMPLETED => 'مكتمل',
            UserRequest::STATUS_CANCELLED => 'ملغي',
        ];

        return view('admin.bookings.show', compact('booking', 'offers', 'payments', 'trainingDays', 'statuses'));
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                UserRequest::STATUS_PENDING_PAYMENT,
                UserRequest::STATUS_AWAITING_OFFERS,
                UserRequest::STATUS_OFFER_SELECTED,
                UserRequest::STATUS_PAID,
                UserRequest::STATUS_IN_TRAINING,
                UserRequest::STATUS_COMPLETED,
                UserRequest::STATUS_CANCELLED,
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = UserRequest::findOrFail($id);
        $oldStatus = $booking->status;
        $booking->status = $request->status;
        $booking->save();

        if ($request->notes) {
        }

        return back()->with('status', "تم تحديث حالة الحجز من {$oldStatus} إلى {$request->status}");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'has_user_car' => ['nullable', 'boolean'],
            'wants_trainer_car' => ['nullable', 'boolean'],
            'needs_pickup' => ['nullable', 'boolean'],
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        $booking = DB::transaction(function () use ($validated, $user, $plan) {
            $booking = new UserRequest([
                'user_id' => $validated['user_id'],
                'plan_id' => $validated['plan_id'],
                'start_date' => $validated['start_date'],
                'has_user_car' => $validated['has_user_car'] ?? false,
                'wants_trainer_car' => $validated['wants_trainer_car'] ?? false,
                'needs_pickup' => $validated['needs_pickup'] ?? false,
                'status' => UserRequest::STATUS_PENDING_PAYMENT,
                'currency' => $user->currency ?? 'USD',
                'app_fee_reserved_minor' => \App\Support\Fees::reservationFeeMinor(),
                'total_paid_minor' => 0,
            ]);
            $booking->save();
            return $booking;
        });

        return redirect()->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم إنشاء الحجز بنجاح');
    }

    public function destroy(string $id)
    {
        $booking = UserRequest::findOrFail($id);
        
        if (!in_array($booking->status, [UserRequest::STATUS_CANCELLED, UserRequest::STATUS_PENDING_PAYMENT])) {
            return back()->withErrors(['error' => 'لا يمكن حذف الحجز في هذه الحالة']);
        }

        DB::transaction(function () use ($booking) {
            $booking->delete();
        });

        return redirect()->route('admin.bookings.index')->with('status', 'تم حذف الحجز بنجاح');
    }
}

