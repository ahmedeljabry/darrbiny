@extends('admin.layouts.app')
@section('title', 'تفاصيل الحجز')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">الحجوزات</a></li>
    <li class="breadcrumb-item active" aria-current="page">تفاصيل الحجز</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger alert-dismissible" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <!-- Booking Details -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تفاصيل الحجز</h5>
                <div class="d-flex gap-2">
                    @can('cancel_courses')
                        @if(!in_array($booking->status, [\App\Models\UserRequest::STATUS_CANCELLED, \App\Models\UserRequest::STATUS_COMPLETED]))
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                                <i class="icon-base ti tabler-x me-1"></i> إلغاء الدورة
                            </button>
                        @endif
                    @endcan
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                        <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات المستخدم</h6>
                        <p class="mb-1"><strong>الاسم:</strong> {{ $booking->user->name ?? 'غير معروف' }}</p>
                        <p class="mb-1"><strong>الهاتف:</strong> {{ $booking->user->phone_with_cc ?? '-' }}</p>
                        <p class="mb-1"><strong>البريد الإلكتروني:</strong> {{ $booking->user->email ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات الخطة</h6>
                        <p class="mb-1"><strong>الخطة:</strong> {{ $booking->plan->title ?? '-' }}</p>
                        @php
                            $bookingLocation = implode(' ، ', array_filter([
                                $booking->locality,
                                $booking->area_level_3,
                                $booking->area_level_2,
                                $booking->area_level_1,
                                $booking->country?->name ?? $booking->plan->country->name ?? null,
                            ]));
                        @endphp
                        <p class="mb-1"><strong>الموقع:</strong> {{ $bookingLocation !== '' ? $bookingLocation : '-' }}</p>
                        <p class="mb-1"><strong>الساعات:</strong> {{ $booking->plan->hours_count ?? '-' }}</p>
                        <p class="mb-1"><strong>الجلسات:</strong> {{ $booking->plan->session_count ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات الحجز</h6>
                        <p class="mb-1"><strong>تاريخ البدء:</strong> {{ $booking->start_date ? $booking->start_date->format('Y-m-d') : '-' }}</p>
                        <p class="mb-1"><strong>الحالة:</strong> 
                            <span class="badge bg-label-{{ $booking->status === 'completed' ? 'success' : ($booking->status === 'cancelled' ? 'danger' : 'warning') }}">
                                {{ $statuses[$booking->status] ?? $booking->status }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>لديه سيارة:</strong> {{ $booking->has_user_car ? 'نعم' : 'لا' }}</p>
                        <p class="mb-1"><strong>يريد سيارة المدرب:</strong> {{ $booking->wants_trainer_car ? 'نعم' : 'لا' }}</p>
                        <p class="mb-1"><strong>يحتاج استقبال:</strong> {{ $booking->needs_pickup ? 'نعم' : 'لا' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">المعلومات المالية</h6>
                        <p class="mb-1"><strong>العملة:</strong> {{ $booking->currency }}</p>
                        @if($fullPayment)
                            <p class="mb-1"><strong>نوع الدفع:</strong> {{ $fullPayment->typeLabel() }}</p>
                            <p class="mb-1"><strong>قيمة الباقة:</strong> {{ number_format($fullPayment->amount_minor / 100, 2) }} {{ $booking->currency }}</p>
                            <p class="mb-1"><strong>رسوم التطبيق:</strong> {{ number_format($fullPayment->app_fee_minor / 100, 2) }} {{ $booking->currency }}</p>
                        @elseif($partialPayment)
                            <p class="mb-1"><strong>نوع الدفع:</strong> {{ $partialPayment->typeLabel() }}</p>
                            <p class="mb-1"><strong>{{ $partialPayment->typeLabel() }}:</strong> {{ number_format($partialPayment->amount_minor / 100, 2) }} {{ $booking->currency }}</p>
                        @else
                            <p class="mb-1"><strong>نوع الدفع:</strong> -</p>
                        @endif
                        <p class="mb-1"><strong>إجمالي الدفعات الناجحة:</strong> {{ number_format($refundableAmountMinor / 100, 2) }} {{ $booking->currency }}</p>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header">
                                <h6 class="mb-0">تحديث حالة الحجز</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.bookings.update-status', $booking->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">الحالة الجديدة</label>
                                            <select name="status" class="form-select select2" required>
                                                @foreach($statuses as $key => $label)
                                                    <option value="{{ $key }}" @selected($booking->status === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">ملاحظات (اختياري)</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="أضف ملاحظات حول تغيير الحالة"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Offers -->
    @if($offers->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">العروض</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المدرب</th>
                            <th>السعر</th>
                            <th>الرسالة</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($offers as $offer)
                            <tr>
                                <td>{{ $offer->trainer->name ?? 'غير معروف' }}</td>
                                <td>{{ number_format($offer->price_minor / 100, 2) }} {{ $booking->currency }}</td>
                                <td>{{ $offer->message ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $offer->status === 'accepted' ? 'success' : ($offer->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $offer->status }}
                                    </span>
                                </td>
                                <td>{{ $offer->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Payments -->
    @if($payments->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">المدفوعات</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>المبلغ</th>
                            <th>النوع</th>
                            <th>الحالة</th>
                            <th>مزود الدفع</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                                <td>{{ $payment->typeLabel() }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $payment->status === 'succeeded' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                                        {{ $payment->statusLabel() }}
                                    </span>
                                </td>
                                <td>{{ $payment->payment_method ?? '-' }}</td>
                                <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Training Days -->
    @if($trainingDays->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">أيام التدريب</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الساعات</th>
                            <th>المدرب</th>
                            <th>الحالة</th>
                            <th>الملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainingDays as $day)
                            <tr>
                                <td>{{ $day->date->format('Y-m-d') }}</td>
                                <td>{{ $day->hours_done }}</td>
                                <td>{{ $day->trainer->name ?? 'غير معروف' }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $day->status === 'approved' ? 'success' : ($day->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $day->status }}
                                    </span>
                                </td>
                                <td>{{ $day->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@can('cancel_courses')
  <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="cancelBookingModalLabel">إلغاء الدورة</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
              </div>
              <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}">
                  @csrf
                  <div class="modal-body">
                      <div class="mb-3">
                          <label class="form-label">المبلغ المراد إضافته للمحفظة</label>
                          <input type="number"
                                 name="refund_amount"
                                 class="form-control"
                                 min="0"
                                 step="0.01"
                                 value="{{ number_format($refundableAmountMinor / 100, 2, '.', '') }}"
                                 required>
                          <small class="text-muted d-block mt-1">
                              إجمالي الدفعات الناجحة: {{ number_format($refundableAmountMinor / 100, 2) }} {{ $booking->currency }}
                          </small>
                      </div>
                      <div class="mb-3">
                          <label class="form-label">سبب الإلغاء</label>
                          <textarea name="reason" class="form-control" rows="3" required placeholder="اكتب سبب الإلغاء ليظهر في إشعار المستخدم والمدربة"></textarea>
                      </div>
                      <div class="alert alert-info mb-0">
                          سيتم إرسال إشعار للمستخدم والمدربة مع سبب الإلغاء.
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                      <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
@endcan

@endsection
