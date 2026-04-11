@extends('admin.layouts.app')
@section('title', 'لوحة التحكم')
@php
  $today = \Illuminate\Support\Carbon::now();
  $rangeOptions = ['day' => 'اليوم', 'month' => 'هذا الشهر', 'year' => 'هذا العام'];
  $fromValue = ($from ?? null) instanceof \DateTimeInterface ? $from->format('Y-m-d') : '';
  $toValue = ($to ?? null) instanceof \DateTimeInterface ? $to->format('Y-m-d') : '';
@endphp

@section('content')
<div class="row g-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm hero-surface">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="chip chip-primary">تحديث {{ $today->translatedFormat('d M') }}</span>
              <span class="text-muted small">{{ $today->translatedFormat('l d M Y') }}</span>
              <span class="chip chip-ghost">{{ $rangeLabel ?? ($rangeOptions[$range ?? 'day'] ?? 'اليوم') }}</span>
            </div>
            <h3 class="fw-bold mb-1 text-dark">التحكم في الطلبات والمبيعات من مكان واحد</h3>
            <p class="mb-0 text-body-secondary">راقب العروض، المدفوعات، مستحقات المدربين، وأرسل التنبيهات بسرعة ضمن النطاق الزمني المحدد.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach($rangeOptions as $key => $label)
              <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                 class="chip {{ ($range ?? 'day') === $key ? 'chip-primary' : 'chip-ghost' }}">
                {{ $label }}
              </a>
            @endforeach
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.bookings.index') }}">إدارة الحجوزات</a>
          </div>
        </div>

        <form method="get" class="row g-2 align-items-end mt-3 dashboard-range-form">
          <div class="col-xl-3 col-md-4">
            <label class="form-label small text-muted mb-1">من تاريخ</label>
            <input type="date" name="from" value="{{ $fromValue }}" class="form-control">
          </div>
          <div class="col-xl-3 col-md-4">
            <label class="form-label small text-muted mb-1">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $toValue }}" class="form-control">
          </div>
          <div class="col-xl-2 col-md-4">
            <button class="btn btn-primary w-100">
              <i class="ti tabler-filter me-1"></i>
              تطبيق النطاق
            </button>
          </div>
          <div class="col-xl-2 col-md-4">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary w-100">
              <i class="ti tabler-rotate-2 me-1"></i>
              إعادة
            </a>
          </div>
          <div class="col-xl-2 col-md-8">
            <div class="small text-muted">
              {{ $usesCustomRange ? 'فلترة مخصصة مفعلة' : 'يمكنك استخدام الفترة الجاهزة أو تحديد تاريخين' }}
            </div>
          </div>
        </form>

        <div class="row g-3 mt-3">
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-primary"><i class="ti tabler-cash"></i></span>
                  <div>
                    <p class="text-muted small mb-0">إجمالي المبيعات</p>
                    <h4 class="mb-0">{{ number_format($salesMinor/100, 2) }}</h4>
                    <small class="text-muted">{{ $rangeLabel ?? ($rangeOptions[$range ?? 'day'] ?? 'اليوم') }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-info"><i class="ti tabler-receipt-2"></i></span>
                  <div>
                    <p class="text-muted small mb-0">رسوم الحجز</p>
                    <h4 class="mb-0">{{ number_format($reservationFeesMinor/100, 2) }}</h4>
                    <small class="text-muted">ضمن النطاق المحدد</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-success"><i class="ti tabler-stack-3"></i></span>
                  <div>
                    <p class="text-muted small mb-0">رسوم الباقات</p>
                    <h4 class="mb-0">{{ number_format($packageFeesMinor/100, 2) }}</h4>
                    <small class="text-muted">تشمل رسوم الجدية والدفع الكلي</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-danger"><i class="ti tabler-credit-card-off"></i></span>
                  <div>
                    <p class="text-muted small mb-0">المصروفات</p>
                    <h4 class="mb-0">{{ number_format($expensesMinor/100, 2) }}</h4>
                    <small class="text-muted">المسجلة ضمن النطاق المحدد</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-primary"><i class="ti tabler-wallet"></i></span>
                  <div>
                    <p class="text-muted small mb-0">رصيد محفظة التطبيق</p>
                    <h4 class="mb-0">{{ number_format($appWalletBalanceMinor/100, 2) }}</h4>
                    <small class="text-muted">صافي الربح بعد خصم المصروفات</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-warning"><i class="ti tabler-calendar-dollar"></i></span>
                  <div>
                    <p class="text-muted small mb-0">قيمة الحجوزات</p>
                    <h4 class="mb-0">{{ number_format($bookingsValueMinor/100, 2) }}</h4>
                    <small class="text-muted">الحجوزات المدفوعة بالكامل</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-dark"><i class="ti tabler-chart-arrows-vertical"></i></span>
                  <div>
                    <p class="text-muted small mb-0">صافي الربح</p>
                    <h4 class="mb-0">{{ number_format($netProfitMinor/100, 2) }}</h4>
                    <small class="text-muted">رسوم الباقات + رسوم الحجز - المصروفات</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <div class="card metric h-100">
              <div class="card-body">
                <div class="d-flex align-items-start gap-2">
                  <span class="icon-pill bg-label-danger"><i class="ti tabler-bell"></i></span>
                  <div>
                    <p class="text-muted small mb-0">تنبيهات غير مقروءة</p>
                    <h4 class="mb-0">{{ $unreadNotifications }}</h4>
                    <small class="text-muted">{{ $rangeLabel ?? 'مركز الإشعارات' }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  @php
    $stateCards = [
      ['label' => 'بانتظار العروض', 'value' => $awaitingOffers, 'desc' => 'طلبات تحتاج عروض', 'color' => 'warning', 'icon' => 'clock'],
      ['label' => 'قيد التدريب', 'value' => $activeBookings, 'desc' => 'حجوزات نشطة', 'color' => 'info', 'icon' => 'activity'],
      ['label' => 'مكتملة', 'value' => $completedBookings, 'desc' => 'جاهزة للإغلاق', 'color' => 'success', 'icon' => 'check'],
      ['label' => 'الكورسات الملغاة', 'value' => $cancelledBookings, 'desc' => 'تم إلغاؤها', 'color' => 'danger', 'icon' => 'x'],
    ];
  @endphp
  @foreach($stateCards as $card)
    <div class="col-xl-3 col-md-6 col-sm-12">
      <div class="card h-100 border-0 shadow-sm metric">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="icon-pill bg-label-{{ $card['color'] }}"><i class="ti tabler-{{ $card['icon'] }}"></i></span>
              <span class="badge bg-label-{{ $card['color'] }}">{{ $card['label'] }}</span>
            </div>
          </div>
          <h3 class="mb-1">{{ $card['value'] }}</h3>
          <small class="text-muted">{{ $card['desc'] }}</small>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="row g-3 mt-2">
  <div class="col-lg-8">
    <div class="card h-100 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div>
          <p class="mb-1 text-muted small">اتجاه {{ $trendLabel ?? 'يومي' }}</p>
          <h5 class="mb-0">المستخدمون / الخطط / الحجوزات</h5>
        </div>
        <span class="badge bg-label-primary">مباشر</span>
      </div>
      <div class="card-body">
        <div id="chart-users-plans" style="height: 320px;"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div>
          <p class="mb-1 text-muted small">توزيع الحالات ضمن الفترة</p>
          <h5 class="mb-0">صحة الحجز</h5>
        </div>
        <span class="badge bg-label-secondary">الآن</span>
      </div>
      <div class="card-body">
        <div id="chart-statuses" style="height: 260px;"></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <span class="badge bg-label-danger">الكورسات الملغاة</span>
          <span class="badge bg-label-warning">بانتظار العروض</span>
          <span class="badge bg-label-info">قيد التدريب</span>
          <span class="badge bg-label-success">مكتملة</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div>
          <p class="mb-1 text-muted small">التسجيلات الجديدة ضمن الفترة</p>
          <h5 class="mb-0">الخطط / الدول / المستخدمون</h5>
        </div>
        <span class="badge bg-label-secondary">توزيع</span>
      </div>
      <div class="card-body">
        <div id="chart-overview" style="height: 280px;"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div>
          <p class="mb-1 text-muted small">تنبيهات عاجلة ضمن الفترة</p>
          <h5 class="mb-0">المهام ذات الأولوية</h5>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.notifications.index') }}">مركز الإشعارات</a>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-warning"></span>
            <div>
              <div class="fw-semibold">طلبات الإلغاء</div>
              <small class="text-muted">مراجعة الطلبات المعلقة</small>
            </div>
          </div>
          <a href="{{ route('admin.cancellation-requests.index') }}" class="badge bg-label-warning rounded-pill text-decoration-none">{{ $pendingCancellations }}</a>
        </li>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-primary"></span>
            <div>
              <div class="fw-semibold">طلبات الإيداع</div>
              <small class="text-muted">طلبات إضافة رصيد للمحفظة</small>
            </div>
          </div>
          <a href="{{ route('admin.wallet-transactions.index') }}" class="badge bg-label-primary rounded-pill text-decoration-none">{{ $pendingWalletRequests }}</a>
        </li>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-danger"></span>
            <div>
              <div class="fw-semibold">طلبات السحب</div>
              <small class="text-muted">طلبات سحب من محافظ الطلاب والمدربين</small>
            </div>
          </div>
          <a href="{{ route('admin.withdrawal-requests.index') }}" class="badge bg-label-danger rounded-pill text-decoration-none">{{ $pendingWithdrawalRequests }}</a>
        </li>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-info"></span>
            <div>
              <div class="fw-semibold">طلبات الجوائز</div>
              <small class="text-muted">استبدال النقاط</small>
            </div>
          </div>
          <a href="{{ route('admin.prize-redemptions.index') }}" class="badge bg-label-info rounded-pill text-decoration-none">{{ $pendingPrizeRequests }}</a>
        </li>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-secondary"></span>
            <div>
              <div class="fw-semibold">تذاكر الدعم</div>
              <small class="text-muted">قيد المعالجة</small>
            </div>
          </div>
          <a href="{{ route('admin.support.index') }}" class="badge bg-label-secondary rounded-pill text-decoration-none">{{ $pendingSupportTickets }}</a>
        </li>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="legend-dot bg-danger"></span>
            <div>
              <div class="fw-semibold">تنبيهات غير مقروءة</div>
              <small class="text-muted">رسائل النظام</small>
            </div>
          </div>
          <a href="{{ route('admin.notifications.index') }}" class="badge bg-label-danger rounded-pill text-decoration-none">{{ $unreadNotifications }}</a>
        </li>
      </ul>
    </div>
  </div>
</div>

@push('styles')
<style>
  .chip { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:12px; font-size:13px; text-decoration:none; }
  .chip-primary { background:#e0e7ff; color:#312e81; border:1px solid #c7d2fe; }
  .chip-ghost { background:#fff; color:#4b5563; border:1px solid #e5e7eb; }
  .metric { border: 1px solid #eef2f6; }
  .legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
  .icon-pill { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:12px; }
  .hero-surface { background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); }
  .dashboard-range-form .form-control { min-height: 42px; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (typeof ApexCharts === 'undefined') return;

  const labels = @json($labels ?? []);
  const userSeries = @json($userSeries ?? []);
  const planSeries = @json($planSeries ?? []);
  const bookingSeries = @json($bookingSeries ?? []);

  new ApexCharts(document.querySelector('#chart-users-plans'), {
    chart: { type: 'area', height: 320, toolbar: { show: false }, foreColor: '#6b7280' },
    stroke: { width: 3, curve: 'smooth' },
    dataLabels: { enabled: false },
    fill: { type: 'gradient', gradient: { shadeIntensity: 0.9, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
    series: [
      { name: 'المستخدمون', data: userSeries },
      { name: 'الخطط', data: planSeries },
      { name: 'الحجوزات', data: bookingSeries }
    ],
    xaxis: { categories: labels, labels: { rotate: -45 } },
    yaxis: { min: 0 },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    legend: { position: 'top', fontSize: '13px' },
    colors: ['#22c55e', '#0ea5e9', '#f59e0b']
  }).render();

  new ApexCharts(document.querySelector('#chart-overview'), {
    chart: { type: 'donut', height: 280 },
    labels: ['الخطط','الدول','المستخدمون'],
    series: [{{ $planCount }}, {{ $countriesCount }}, {{ $usersCount }}],
    legend: { position: 'bottom' },
    colors: ['#6366f1', '#22d3ee', '#22c55e'],
    stroke: { width: 0 }
  }).render();

  new ApexCharts(document.querySelector('#chart-statuses'), {
    chart: { type: 'bar', height: 260, toolbar: { show: false }, foreColor: '#6b7280' },
    plotOptions: { bar: { borderRadius: 8, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    series: [{
      name: 'الطلبات',
      data: [
        {{ $cancelledBookings }},
        {{ $awaitingOffers }},
        {{ $activeBookings }},
        {{ $completedBookings }}
      ]
    }],
    xaxis: { categories: ['الكورسات الملغاة','بانتظار العروض','قيد التدريب','مكتملة'] },
    colors: ['#ef4444', '#f59e0b', '#0ea5e9', '#22c55e'],
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 }
  }).render();
});
</script>
@endpush
@endsection
