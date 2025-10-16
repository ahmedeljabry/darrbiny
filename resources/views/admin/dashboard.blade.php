@extends('admin.layouts.app')
@section('title', 'الرئيسيه')
@section('content')
<div class="row">
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
                    <h5 class="mb-1 me-2">{{ $countriesCount }}</h5>
                    <p class="mb-0">عدد الدول</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-primary rounded p-2">
                        <i class="icon-base ti tabler-world icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">{{ $citiesCount }}</h5>
                    <p class="mb-0">عدد المدن</p>
                </div>
                <div class="card-icon">
                    <span class="badge bg-label-primary rounded p-2">
                        <i class="icon-base ti tabler-world icon-26px"></i>
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
                    <span class="badge bg-label-primary rounded p-2">
                        <i class="icon-base ti tabler-users icon-26px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mt-4 g-6">
  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">إنشاء المستخدمين والخطط (7 أيام)</h5>
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

    const lineOptions = {
      chart: { type: 'line', height: 320, toolbar: { show: false } },
      stroke: { width: 3, curve: 'smooth' },
      series: [
        { name: 'المستخدمون', data: userSeries },
        { name: 'الخطط', data: planSeries }
      ],
      xaxis: { categories: labels },
      yaxis: { min: 0 },
      legend: { position: 'top' }
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
