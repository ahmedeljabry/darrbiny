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
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">قائمة الحجوزات</h5>
                <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                    <div>
                        <label class="form-label">بحث</label>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="اسم المستخدم أو رقم الهاتف">
                    </div>
                    <div>
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">الخطة</label>
                        <select name="plan_id" class="form-select select2" style="min-width:200px">
                            <option value="">الكل</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected($planId === $plan->id)>{{ $plan->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">من تاريخ</label>
                        <input type="text" name="date_from" value="{{ $dateFrom }}" class="form-control flatpickr" placeholder="اختر تاريخ">
                    </div>
                    <div>
                        <label class="form-label">إلى تاريخ</label>
                        <input type="text" name="date_to" value="{{ $dateTo }}" class="form-control flatpickr" placeholder="اختر تاريخ">
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <button class="btn btn-outline-secondary">تصفية</button>
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الخطة</th>
                            <th>تاريخ البدء</th>
                            <th>الحالة</th>
                            <th>المبلغ المدفوع</th>
                            <th>تاريخ الإنشاء</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $booking->user->name ?? 'غير معروف' }}</span>
                                        <small class="text-muted">{{ $booking->user->phone_with_cc ?? '-' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $booking->plan->title ?? '-' }}</span>
                                        @if($booking->plan)
                                            <small class="text-muted">{{ $booking->plan->city->name ?? '' }}, {{ $booking->plan->country->name ?? '' }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $booking->start_date ? $booking->start_date->format('Y-m-d') : '-' }}</td>
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
                                    @if($booking->total_paid_minor > 0)
                                        {{ number_format($booking->total_paid_minor / 100, 2) }} {{ $booking->currency }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $booking->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.bookings.show', $booking->id) }}">
                                                <i class="icon-base ti tabler-eye me-1"></i> عرض التفاصيل
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">لا توجد حجوزات</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $bookings->links() }}</div>
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

