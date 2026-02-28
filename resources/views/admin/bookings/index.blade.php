@extends('admin.layouts.app')
@section('title', 'إدارة الحجوزات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الحجوزات</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
                    <i class="icon-base ti tabler-calendar-event" style="font-size: 24px;"></i>
                  </span>
                  <div>
                    <h5 class="mb-0 fw-bold">إدارة الحجوزات</h5>
                    <small class="text-muted">عرض وإدارة جميع الحجوزات والطلبات</small>
                  </div>
                </div>
                <a href="{{ route('admin.bookings.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
                    <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
                </a>
            </div>
            <div class="card-body pt-0">
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">بحث</label>
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="اسم المستخدم أو رقم الهاتف">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">الخطة</label>
                        <select name="plan_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($planId === $plan->id)>{{ $plan->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">من تاريخ</label>
                        <input type="text" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm flatpickr" placeholder="اختر تاريخ">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">إلى تاريخ</label>
                        <input type="text" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm flatpickr" placeholder="اختر تاريخ">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            <i class="icon-base ti tabler-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;"><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
                            <th style="width: 200px;"><i class="icon-base ti tabler-file-check me-1"></i> الخطة</th>
                            <th style="width: 120px;"><i class="icon-base ti tabler-calendar me-1"></i> تاريخ البدء</th>
                            <th style="width: 130px;"><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th style="width: 240px;"><i class="icon-base ti tabler-currency-dollar me-1"></i> الدفعات</th>
                            <th style="width: 150px;"><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الإنشاء</th>
                            <th style="width: 100px;" class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($booking->user?->profile_picture_url)
                                            <img src="{{ $booking->user->profile_picture_url }}" alt="{{ $booking->user->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                            <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                                                {{ substr($booking->user->name ?? 'U', 0, 1) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $booking->user->name ?? 'غير معروف' }}</div>
                                            <small class="text-muted">{{ $booking->user->phone_with_cc ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $booking->plan->title ?? '-' }}</span>
                                        @if($booking->plan)
                                            <small class="text-muted">{{ $booking->plan->city->name ?? '' }}, {{ $booking->plan->country->name ?? '' }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($booking->start_date)
                                        <span>{{ $booking->start_date->format('Y-m-d') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending_payment' => 'warning',
                                            'awaiting_offers' => 'info',
                                            'offer_selected' => 'primary',
                                            'paid' => 'success',
                                            'in_training' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                        ];
                                        $color = $statusColors[$booking->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-label-{{ $color }}">{{ $statuses[$booking->status] ?? $booking->status }}</span>
                                </td>
                                <td>
                                    @if($booking->payments->isNotEmpty())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($booking->payments as $payment)
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold">{{ $payment->typeLabel() }}</span>
                                                    <small class="text-muted">
                                                        {{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}
                                                        ({{ $payment->statusLabel() }})
                                                    </small>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $booking->created_at->format('Y-m-d') }}</span>
                                        <small class="text-muted">{{ $booking->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="عرض التفاصيل">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                                            <i class="icon-base ti tabler-calendar-event" style="font-size: 32px;"></i>
                                        </span>
                                        <p class="text-muted mb-0">لا توجد حجوزات</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bookings->hasPages())
                <div class="card-footer border-top">
                    {{ $bookings->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .flatpickr-input {
        background-color: var(--bs-body-bg);
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('admin/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr('.flatpickr', {
            dateFormat: 'Y-m-d',
            locale: 'ar',
            allowInput: true
        });
    });
</script>
@endpush

@endsection
