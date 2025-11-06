@extends('admin.layouts.app')
@section('title', 'الرئيسيه')
@section('content')

<div class="row g-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">{{ $planCount }}</h5>
                    <p class="mb-0">عدد الخطط</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-primary rounded p-2">
                        <i class="icon-base ti tabler-package icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">{{ $usersCount }}</h5>
                    <p class="mb-0">عدد المستخدمين</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-success rounded p-2">
                        <i class="icon-base ti tabler-users icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">{{ $trainersCount }}</h5>
                    <p class="mb-0">عدد المدربين</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-info rounded p-2">
                        <i class="icon-base ti tabler-user-star icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">{{ $bookingsCount }}</h5>
                    <p class="mb-0">إجمالي الحجوزات</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-warning rounded p-2">
                        <i class="icon-base ti tabler-calendar icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">حجوزات معلقة</h6>
                        <h3 class="mb-0">{{ $pendingBookings }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-clock"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">حجوزات نشطة</h6>
                        <h3 class="mb-0">{{ $activeBookings }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-activity"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">حجوزات مكتملة</h6>
                        <h3 class="mb-0">{{ $completedBookings }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">إشعارات غير مقروءة</h6>
                        <h3 class="mb-0">{{ $unreadNotifications }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="icon-base ti tabler-bell"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-3 col-sm-6">
        <a href="{{ route('admin.cancellation-requests.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">طلبات الإلغاء</h6>
                        <h3 class="mb-0">{{ $pendingCancellations }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-x"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="{{ route('admin.wallet-transactions.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">طلبات المحفظة</h6>
                        <h3 class="mb-0">{{ $pendingWalletRequests }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-wallet"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="{{ route('admin.prize-redemptions.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">طلبات الجوائز</h6>
                        <h3 class="mb-0">{{ $pendingPrizeRequests }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-gift"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="{{ route('admin.support.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">تذاكر الدعم</h6>
                        <h3 class="mb-0">{{ $pendingSupportTickets }}</h3>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-secondary">
                            <i class="icon-base ti tabler-ticket"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row mt-4 g-6">
  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">إنشاء المستخدمين والخطط والحجوزات (7 أيام)</h5>
      </div>
      <div class="card-body">
        <div id="chart-users-plans" style="height: 320px;"></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">نسب عامة</h5>
      </div>
      <div class="card-body">
        <div id="chart-overview" style="height: 320px;"></div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (typeof ApexCharts !== 'undefined') {
    const labels = @json($labels ?? []);
    const userSeries = @json($userSeries ?? []);
    const planSeries = @json($planSeries ?? []);
    const bookingSeries = @json($bookingSeries ?? []);

    const lineOptions = {
      chart: { type: 'line', height: 320, toolbar: { show: false } },
      stroke: { width: 3, curve: 'smooth' },
      series: [
        { name: 'المستخدمون', data: userSeries },
        { name: 'الخطط', data: planSeries },
        { name: 'الحجوزات', data: bookingSeries }
      ],
      xaxis: { categories: labels },
      yaxis: { min: 0 },
      legend: { position: 'top' },
      colors: ['#696cff', '#71dd37', '#ffab00']
    };
    new ApexCharts(document.querySelector('#chart-users-plans'), lineOptions).render();

    const donutOptions = {
      chart: { type: 'donut', height: 320 },
      labels: ['الخطط','الدول','المدن','المستخدمون'],
      series: [{{ $planCount }}, {{ $countriesCount }}, {{ $citiesCount }}, {{ $usersCount }}],
      legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector('#chart-overview'), donutOptions).render();
  }
});
</script>
@endpush
@endsection
