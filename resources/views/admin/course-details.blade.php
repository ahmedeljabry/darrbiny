@extends('admin.layouts.app')
@section('title', 'تفاصيل الدورات والحجوزات')
@section('content')

<div class="row g-6 mb-6">
    <!-- Statistics Cards -->
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي الحجوزات</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $bookingStats['total'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-calendar-event icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">قيد الانتظار</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $bookingStats['pending'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-clock icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">قيد التدريب</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $bookingStats['in_training'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-school icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">مكتملة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $bookingStats['completed'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-check icon-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-6">
    <!-- Course Selection and Booking Form -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">إنشاء حجز جديد</h5>
            </div>
            <div class="card-body">
                <form id="bookingForm" method="POST" action="{{ route('admin.bookings.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">اختر الخطة</label>
                        <select name="plan_id" id="planSelect" class="form-select select2" required>
                            <option value="">اختر خطة</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" 
                                    data-hours="{{ $plan->hours_count }}"
                                    data-sessions="{{ $plan->session_count }}"
                                    data-price="{{ $plan->price_min }}">
                                    {{ $plan->title }} - {{ $plan->city->name ?? '' }}, {{ $plan->country->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اختاري تاريخ البدء المناسب :</label>
                        <input type="text" name="start_date" id="startDate" class="form-control flatpickr" 
                               placeholder="اختر تاريخ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">معلومات إضافية</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="has_user_car" id="hasUserCar" value="1">
                            <label class="form-check-label" for="hasUserCar">
                                لديه سيارة
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="wants_trainer_car" id="wantsTrainerCar" value="1">
                            <label class="form-check-label" for="wantsTrainerCar">
                                يريد سيارة المدرب
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="needs_pickup" id="needsPickup" value="1">
                            <label class="form-check-label" for="needsPickup">
                                يحتاج استقبال
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المستخدم</label>
                        <select name="user_id" class="form-select select2" required>
                            <option value="">اختر مستخدم</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone_with_cc }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="icon-base ti tabler-calendar-plus me-1"></i> إنشاء حجز
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر الحجوزات</h5>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-primary">
                    عرض الكل
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الخطة</th>
                            <th>تاريخ البدء</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->user->name ?? 'غير معروف' }}</td>
                                <td>{{ $booking->plan->title ?? '-' }}</td>
                                <td>{{ $booking->start_date ? $booking->start_date->format('Y-m-d') : '-' }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $booking->status === 'completed' ? 'success' : ($booking->status === 'cancelled' ? 'danger' : 'warning') }}">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <p class="text-muted mb-0">لا توجد حجوزات حديثة</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .flatpickr-input {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        padding: 0.5rem;
        border-radius: 0.375rem;
    }
    .flatpickr-input:focus {
        border-color: var(--bs-primary);
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('admin/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for date picker
        flatpickr('.flatpickr', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            locale: {
                firstDayOfWeek: 6, // Saturday
                weekdays: {
                    shorthand: ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
                    longhand: ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
                },
                months: {
                    shorthand: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                    longhand: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
                }
            },
            allowInput: true
        });

        // Handle form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // You can add AJAX submission here or let it submit normally
            this.submit();
        });
    });
</script>
@endpush

@endsection

