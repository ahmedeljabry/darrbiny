<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\BookingsExport;
use App\Models\CancellationRequest;
use App\Models\UserRequest;
use App\Models\Plan;
use App\Models\WalletTransaction;
use App\Modules\Requests\Services\RequestService;
use App\Notifications\CourseCancelledNotification;
use App\Notifications\WalletBalanceAddedNotification;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BookingsController extends BaseController
{
    public function __construct(
        private readonly RequestService $requests,
    ) {}

    public function index(Request $request)
    {
        $q = $request->query('q');
        $status = $request->query('status');
        $planId = $request->query('plan_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $bookings = UserRequest::with([
            'user',
            'country',
            'plan',
            'plan.country',
            'payments' => fn ($query) => $query->latest(),
        ])
            ->when($q, function ($query) use ($q) {
                $normalizedOrderNumber = UserRequest::normalizeOrderNumberSearch($q);

                $query->where(function ($searchQuery) use ($q, $normalizedOrderNumber) {
                    $searchQuery->whereHas('user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('phone_with_cc', 'like', "%{$q}%");
                    })->orWhere(function ($requestQuery) use ($q, $normalizedOrderNumber) {
                        $requestQuery->where('id', 'like', "%{$q}%")
                            ->orWhereRaw('CAST(order_number as CHAR) like ?', ["%{$q}%"]);

                        if ($normalizedOrderNumber !== null) {
                            $requestQuery->orWhere('order_number', $normalizedOrderNumber);
                        }
                    });
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

        if ($request->query('export') === 'excel') {
            $allBookings = UserRequest::with([
                'user',
                'trainer',
                'country',
                'plan',
                'plan.country',
                'payments' => fn ($query) => $query->latest(),
            ])
                ->when($q, function ($query) use ($q) {
                    $normalizedOrderNumber = UserRequest::normalizeOrderNumberSearch($q);

                    $query->where(function ($searchQuery) use ($q, $normalizedOrderNumber) {
                        $searchQuery->whereHas('user', function ($userQuery) use ($q) {
                            $userQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone_with_cc', 'like', "%{$q}%");
                        })->orWhere(function ($requestQuery) use ($q, $normalizedOrderNumber) {
                            $requestQuery->where('id', 'like', "%{$q}%")
                                ->orWhereRaw('CAST(order_number as CHAR) like ?', ["%{$q}%"]);

                            if ($normalizedOrderNumber !== null) {
                                $requestQuery->orWhere('order_number', $normalizedOrderNumber);
                            }
                        });
                    });
                })
                ->when($status, fn($query) => $query->where('status', $status))
                ->when($planId, fn($query) => $query->where('plan_id', $planId))
                ->when($dateFrom, fn($query) => $query->whereDate('start_date', '>=', $dateFrom))
                ->when($dateTo, fn($query) => $query->whereDate('start_date', '<=', $dateTo))
                ->latest()
                ->get();

            return Excel::download(
                new BookingsExport($allBookings),
                'bookings-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        return view('admin.bookings.index', compact('bookings', 'plans', 'statuses', 'q', 'status', 'planId', 'dateFrom', 'dateTo'));
    }

    public function show(string $id)
    {
        $booking = UserRequest::with([
            'user',
            'country',
            'plan',
            'plan.country',
            'plan.features',
            'payments' => fn ($query) => $query->latest(),
        ])->findOrFail($id);

        $offers = \App\Models\TrainerOffer::where('user_request_id', $id)
            ->with('trainer')
            ->latest()
            ->get();

        $payments = $booking->payments;

        $trainingDays = \App\Models\TrainingDay::where('user_request_id', $id)
            ->with('trainer')
            ->latest()
            ->get();

        $fullPayment = $booking->latestSuccessfulFullPayment();
        $partialPayment = $booking->latestSuccessfulPartialPayment();
        $refundableAmountMinor = $booking->totalSuccessfulPaymentsMinor();

        $statuses = [
            UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            UserRequest::STATUS_AWAITING_OFFERS => 'في انتظار العروض',
            UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            UserRequest::STATUS_PAID => 'مدفوع',
            UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            UserRequest::STATUS_COMPLETED => 'مكتمل',
            UserRequest::STATUS_CANCELLED => 'ملغي',
        ];

        return view(
            'admin.bookings.show',
            compact(
                'booking',
                'offers',
                'payments',
                'trainingDays',
                'statuses',
                'fullPayment',
                'partialPayment',
                'refundableAmountMinor'
            )
        );
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

        if ($request->status === UserRequest::STATUS_CANCELLED) {
            return back()->withErrors(['status' => 'يرجى استخدام إجراء إلغاء الدورة لإضافة الرصيد وإرسال الإشعار.']);
        }

        $booking = UserRequest::findOrFail($id);
        $oldStatus = $booking->status;

        if ($request->status === UserRequest::STATUS_COMPLETED) {
            $this->requests->complete($booking, $request->user());
        } else {
            $booking->status = $request->status;
            $booking->save();
        }

        if ($request->notes) {
        }

        return back()->with('status', "تم تحديث حالة الحجز من {$oldStatus} إلى {$request->status}");
    }

    public function cancel(Request $request, string $id)
    {
        abort_unless($request->user()->can('cancel_courses'), 403);

        $validated = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $booking = UserRequest::with(['user', 'trainer', 'plan', 'cancellationRequest'])->findOrFail($id);
        abort_if($booking->status === UserRequest::STATUS_CANCELLED, 422, 'Course already cancelled');
        abort_if($booking->status === UserRequest::STATUS_COMPLETED, 422, 'Cannot cancel completed course');

        DB::transaction(function () use ($booking, $validated, $request) {
            $booking->status = UserRequest::STATUS_CANCELLED;
            $booking->save();

            $cancellation = $booking->cancellationRequest ?? new CancellationRequest([
                'user_request_id' => $booking->id,
                'user_id' => $booking->user_id,
                'reason' => $validated['reason'],
            ]);

            if ($cancellation->exists) {
                if (blank($cancellation->reason)) {
                    $cancellation->reason = $validated['reason'];
                }
                $cancellation->admin_notes = $validated['reason'];
            } else {
                $cancellation->admin_notes = $validated['reason'];
            }

            $cancellation->status = CancellationRequest::STATUS_APPROVED;
            $cancellation->refund_amount_minor = WalletAmount::majorToMinor($validated['refund_amount']);
            $cancellation->processed_by = $request->user()->id;
            $cancellation->processed_at = now();
            $cancellation->save();

            $refundAmountMinor = (int) $cancellation->refund_amount_minor;
            if ($refundAmountMinor > 0 && $booking->user) {
                $booking->user->increment('points_balance', $refundAmountMinor);

                $walletTransaction = WalletTransaction::create([
                    'user_id' => $booking->user->id,
                    'amount' => $refundAmountMinor,
                    'type' => WalletTransaction::TYPE_REFUND,
                    'status' => WalletTransaction::STATUS_APPROVED,
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                    'notes' => "إلغاء دورة #{$booking->id} - {$validated['reason']}",
                ]);

                $booking->user->notify(new WalletBalanceAddedNotification(
                    $refundAmountMinor,
                    'course_cancellation_refund',
                    $walletTransaction->id
                ));
            }

            $notification = new CourseCancelledNotification(
                $booking,
                $validated['reason'],
                WalletAmount::minorToMajor($refundAmountMinor)
            );

            if ($booking->user) {
                $booking->user->notify($notification);
            }
            if ($booking->trainer) {
                $booking->trainer->notify($notification);
            }
        });

        return back()->with('status', 'تم إلغاء الدورة وإضافة الرصيد وإرسال الإشعارات');
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
                'app_fee_reserved_minor' => \App\Support\Fees::reservationFeeMinor($plan->country_id),
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
