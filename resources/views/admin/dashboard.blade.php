@extends('admin.layouts.app')
@section('title', 'لوحة التحكم')

@include('admin.reports.partials.theme')

@php
  $today = \Illuminate\Support\Carbon::now();
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $rangeOptions = ['day' => 'اليوم', 'month' => 'هذا الشهر', 'year' => 'هذا العام'];
  $fromValue = ($from ?? null) instanceof \DateTimeInterface ? $from->format('Y-m-d') : '';
  $toValue = ($to ?? null) instanceof \DateTimeInterface ? $to->format('Y-m-d') : '';

  $financeCards = [
    ['label' => 'إجمالي الإيرادات', 'value' => number_format($totalRevenueMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'رسوم الحجز + قيمة الباقة بالكامل ضمن ' . ($rangeLabel ?? ($rangeOptions[$range ?? 'day'] ?? 'اليوم')), 'icon' => 'cash', 'tone' => 'success'],
    ['label' => 'رسوم الحجز', 'value' => number_format($packageReservationFeesMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'رسوم الحجز الثابتة ورسوم حجز الباقات • محول للريال', 'icon' => 'receipt-2', 'tone' => 'info'],
    ['label' => 'رسوم الباقات', 'value' => number_format($appFeesMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'نسبة التطبيق من الدفعات الكلية • محول للريال', 'icon' => 'stack-3', 'tone' => 'primary'],
    ['label' => 'المصروفات', 'value' => number_format($expensesMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'المسجلة ضمن النطاق المحدد', 'icon' => 'credit-card-off', 'tone' => 'danger'],
    ['label' => 'رصيد محفظة التطبيق', 'value' => number_format($appWalletBalanceMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'الرصيد الحقيقي الحالي بالريال ولا يتأثر بفلتر التاريخ', 'icon' => 'wallet', 'tone' => 'primary'],
    ['label' => 'مستحقات المدربين', 'value' => number_format($bookingsValueMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'صافي المدرب للكورسات المكتملة بعد خصم سحوبات المدربين المنفذة', 'icon' => 'calendar-dollar', 'tone' => 'warning'],
    ['label' => 'صافي الربح', 'value' => number_format($netProfitMinor / 100, 2) . ' ' . $reportCurrency, 'desc' => 'رسوم الحجز + رسوم الباقات - المصروفات', 'icon' => 'chart-arrows-vertical', 'tone' => 'secondary'],
    ['label' => 'السحوبات', 'value' => number_format(($executedWithdrawalsMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'desc' => 'إجمالي قيمة طلبات السحب المنفذة ضمن ' . ($rangeLabel ?? 'الفترة الحالية'), 'icon' => 'arrow-up-right-circle', 'tone' => 'danger'],
  ];

  $stateCards = [
    ['label' => 'بانتظار العروض', 'value' => $awaitingOffers, 'desc' => 'طلبات تحتاج تسعير ومتابعة سريعة', 'icon' => 'clock-hour-4', 'tone' => 'warning'],
    ['label' => 'قيد التدريب', 'value' => $activeBookings, 'desc' => 'حجوزات نشطة داخل مرحلة التنفيذ', 'icon' => 'activity', 'tone' => 'info'],
    ['label' => 'مكتملة', 'value' => $completedBookings, 'desc' => 'طلبات جاهزة للإغلاق والمتابعة', 'icon' => 'circle-check', 'tone' => 'success'],
    ['label' => 'الكورسات الملغاة', 'value' => $cancelledBookings, 'desc' => 'تم إلغاؤها وتحتاج مراجعة الأثر المالي', 'icon' => 'circle-x', 'tone' => 'danger'],
  ];

  $overviewStats = [
    ['label' => 'إجمالي الحجوزات', 'value' => $bookingsCount, 'formatted' => number_format($bookingsCount), 'icon' => 'calendar-check', 'tone' => 'primary'],
    ['label' => 'المستخدمون الجدد', 'value' => $usersCount, 'formatted' => number_format($usersCount), 'icon' => 'users', 'tone' => 'success'],
    ['label' => 'المدربون الجدد', 'value' => $trainersCount, 'formatted' => number_format($trainersCount), 'icon' => 'user-star', 'tone' => 'info'],
    ['label' => 'الخطط الجديدة', 'value' => $planCount, 'formatted' => number_format($planCount), 'icon' => 'package', 'tone' => 'warning'],
    ['label' => 'الدول الجديدة', 'value' => $countriesCount, 'formatted' => number_format($countriesCount), 'icon' => 'world', 'tone' => 'secondary'],
  ];
  $overviewStatsLabels = array_column($overviewStats, 'label');
  $overviewStatsSeries = array_column($overviewStats, 'value');
  $overviewStatsTotal = array_sum($overviewStatsSeries);
  $overviewSeries = [$planCount, $countriesCount, $usersCount];
  $overviewTotal = array_sum($overviewSeries);

  $queueItems = [
    ['label' => 'طلبات الإلغاء', 'desc' => 'مراجعة الطلبات المعلقة', 'value' => $pendingCancellations, 'route' => route('admin.cancellation-requests.index'), 'tone' => 'warning', 'icon' => 'rotate-clockwise-2'],
    ['label' => 'طلبات الإيداع', 'desc' => 'طلبات إضافة رصيد للمحفظة', 'value' => $pendingWalletRequests, 'route' => route('admin.wallet-transactions.index'), 'tone' => 'primary', 'icon' => 'wallet'],
    ['label' => 'طلبات السحب', 'desc' => 'طلبات سحب من محافظ الطلاب والمدربين', 'meta' => number_format(($withdrawalRequestsValueMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'value' => $pendingWithdrawalRequests, 'route' => route('admin.withdrawal-requests.index'), 'tone' => 'danger', 'icon' => 'arrow-up-right-circle'],
    ['label' => 'طلبات الجوائز', 'desc' => 'استبدال النقاط بالمكافآت', 'value' => $pendingPrizeRequests, 'route' => route('admin.prize-redemptions.index'), 'tone' => 'info', 'icon' => 'gift'],
    ['label' => 'تذاكر الدعم', 'desc' => 'تذاكر جديدة أو ردود من المستخدمين', 'value' => $pendingSupportTickets, 'alert' => $supportTicketAlertsCount ?? 0, 'route' => route('admin.support.index'), 'tone' => 'secondary', 'icon' => 'message-2'],
    ['label' => 'إشعارات السحوبات', 'desc' => 'طلبات سحب غير مقروءة تحتاج مراجعة', 'value' => ($dashboardAlerts ?? collect())->count(), 'route' => route('admin.notifications.view', ['read' => 'unread', 'type' => 'WalletWithdrawRequest']), 'tone' => 'danger', 'icon' => 'bell-ringing'],
  ];

@endphp

@section('content')
<div class="dashboard-shell">
  <div class="report-hero report-hero--primary">
    <div class="report-hero__body">
      <div class="report-hero__lead">
        <span class="report-hero__icon bg-label-primary">
          <i class="icon-base ti tabler-layout-dashboard"></i>
        </span>
        <div class="report-hero__text">
          <h2>لوحة التحكم الرئيسية</h2>
          <p>واجهة موحدة لمراقبة الإيرادات، حالة التشغيل، والتنبيهات اليومية باستخدام نفس مكونات التقارير وبترتيب أوضح للأولويات.</p>
          <div class="report-hero__tags">
            <span class="report-tag"><i class="icon-base ti tabler-calendar"></i>{{ $today->translatedFormat('l d M Y') }}</span>
            <span class="report-tag"><i class="icon-base ti tabler-clock-hour-4"></i>{{ $rangeLabel ?? ($rangeOptions[$range ?? 'day'] ?? 'اليوم') }}</span>
            <span class="report-tag"><i class="icon-base ti tabler-filter-check"></i>{{ $usesCustomRange ? 'فلترة مخصصة مفعلة' : 'فترة جاهزة مفعلة' }}</span>
          </div>
        </div>
      </div>

      <div class="report-hero__actions">
        @foreach($rangeOptions as $key => $label)
          <a
            href="{{ route('admin.dashboard', ['range' => $key]) }}"
            class="btn {{ ($range ?? 'day') === $key && ! $usesCustomRange ? 'btn-primary' : 'btn-outline-primary' }}"
          >
            {{ $label }}
          </a>
        @endforeach
        @can('manage_plans')
          <a class="btn btn-outline-secondary" href="{{ route('admin.bookings.index') }}">
            <i class="icon-base ti tabler-arrow-up-left me-1"></i>
            إدارة الحجوزات
          </a>
        @endcan
      </div>
    </div>

    <div class="row g-3 report-stats mt-2">
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">إجمالي الحجوزات</div>
              <p class="report-stat__value">{{ number_format($bookingsCount) }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-calendar-check"></i></span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">المستخدمون الجدد</div>
              <p class="report-stat__value">{{ number_format($usersCount) }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-users"></i></span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">المدربون الجدد</div>
              <p class="report-stat__value">{{ number_format($trainersCount) }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-user-star"></i></span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">الخطط / الدول</div>
              <p class="report-stat__value">{{ number_format($planCount) }} / {{ number_format($countriesCount) }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-package"></i></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card report-panel">
    <div class="card-body">
      <div class="report-filter-card mb-0">
        <div class="report-filter-card__header">
          <div class="report-filter-card__title">
            <span class="report-filter-card__icon">
              <i class="icon-base ti tabler-adjustments-horizontal"></i>
            </span>
            <div>
              <h6>فلترة لوحة التحكم</h6>
              <p>تحكم في جميع أرقام الصفحة من نفس النطاق الزمني بدل التنقل بين أكثر من شاشة.</p>
            </div>
          </div>
          <div class="report-toolbar-note">النطاق الحالي: {{ $rangeLabel ?? ($rangeOptions[$range ?? 'day'] ?? 'اليوم') }}</div>
        </div>

        <form method="get" class="row g-3 align-items-end">
          <div class="col-xl-4 col-md-6">
            <label class="report-form-label">من تاريخ</label>
            <input type="date" name="from" value="{{ $fromValue }}" class="form-control report-input">
          </div>
          <div class="col-xl-4 col-md-6">
            <label class="report-form-label">إلى تاريخ</label>
            <input type="date" name="to" value="{{ $toValue }}" class="form-control report-input">
          </div>
          <div class="col-xl-2 col-md-6">
            <button class="btn btn-primary w-100">
              <i class="icon-base ti tabler-filter me-1"></i>
              تطبيق
            </button>
          </div>
          <div class="col-xl-2 col-md-6">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary w-100 report-reset">
              <i class="icon-base ti tabler-rotate-2 me-1"></i>
              إعادة
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card report-panel">
    <div class="card-body">
      <div class="dashboard-section-head">
        <div>
          <h5 class="mb-1">الملخص المالي</h5>
          <p class="text-muted mb-0 small">جميع مؤشرات النقدية والرسوم مجمعة في شبكة موحدة لقراءة أسرع.</p>
        </div>
        <span class="report-tag"><i class="icon-base ti tabler-coins"></i>{{ $rangeLabel ?? 'الفترة الحالية' }}</span>
      </div>

      <div class="row g-3">
        @foreach($financeCards as $card)
          <div class="col-12 col-md-6 col-xl-3">
            <div class="dashboard-metric-card h-100">
              <div class="dashboard-metric-card__top">
                <span class="dashboard-metric-card__icon bg-label-{{ $card['tone'] }}">
                  <i class="icon-base ti tabler-{{ $card['icon'] }}"></i>
                </span>
                <span class="report-status report-status--{{ $card['tone'] === 'secondary' ? 'secondary' : $card['tone'] }}">{{ $card['label'] }}</span>
              </div>
              <div class="dashboard-metric-card__value">{{ $card['value'] }}</div>
              <div class="dashboard-metric-card__desc">{{ $card['desc'] }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-xl-7">
      <div class="card report-panel h-100">
        <div class="card-body">
          <div class="dashboard-section-head">
            <div>
              <h5 class="mb-1">حالة التشغيل</h5>
              <p class="text-muted mb-0 small">ترتيب واضح لمسار الطلب من العروض وحتى الإغلاق والإلغاء.</p>
            </div>
            <span class="report-tag"><i class="icon-base ti tabler-activity-heartbeat"></i>متابعة مباشرة</span>
          </div>

          <div class="row g-3">
            @foreach($stateCards as $card)
              <div class="col-md-6">
                <div class="dashboard-status-card h-100">
                  <div class="dashboard-status-card__top">
                    <span class="dashboard-status-card__icon bg-label-{{ $card['tone'] }}">
                      <i class="icon-base ti tabler-{{ $card['icon'] }}"></i>
                    </span>
                    <span class="report-status report-status--{{ $card['tone'] }}">{{ $card['label'] }}</span>
                  </div>
                  <div class="dashboard-status-card__value">{{ $card['value'] }}</div>
                  <div class="dashboard-status-card__desc">{{ $card['desc'] }}</div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card report-panel h-100">
        <div class="card-body p-0">
          <div class="dashboard-section-head dashboard-section-head--padded">
            <div>
              <h5 class="mb-1">قائمة المتابعة السريعة</h5>
              <p class="text-muted mb-0 small">صف المهام ذات الأولوية حسب الطلبات والتنبيهات المفتوحة الآن.</p>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.notifications.index') }}">مركز الإشعارات</a>
          </div>

          <ul class="list-group list-group-flush dashboard-queue">
            @foreach($queueItems as $item)
              <li class="list-group-item d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="dashboard-queue__icon bg-label-{{ $item['tone'] }}">
                    <i class="icon-base ti tabler-{{ $item['icon'] }}"></i>
                  </span>
                  <div>
                    <div class="fw-semibold">{{ $item['label'] }}</div>
                    <small class="text-muted">{{ $item['desc'] }}</small>
                    @if(!empty($item['meta']))
                      <small class="text-muted d-block">{{ $item['meta'] }}</small>
                    @endif
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                  @if(($item['alert'] ?? 0) > 0)
                    <span class="badge bg-label-warning rounded-pill">
                      <i class="icon-base ti tabler-bell-ringing me-1"></i>{{ $item['alert'] }}
                    </span>
                  @endif
                  <a href="{{ $item['route'] }}" class="badge bg-label-{{ $item['tone'] }} rounded-pill text-decoration-none">{{ $item['value'] }}</a>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>

  @can('manage_users')
    <div class="card report-panel mt-4 border border-danger-subtle">
      <div class="card-body">
        <div class="dashboard-section-head">
          <div>
            <h5 class="mb-1 text-danger">إعادة تهيئة بيانات المستخدمين</h5>
            <p class="text-muted mb-0 small">يحذف حسابات المستخدمين غير الإداريين ويحرر أرقام الجوال مع الحفاظ على الحجوزات والمدفوعات والسجلات المالية السابقة.</p>
          </div>
          <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetUsersModal">
            <i class="icon-base ti tabler-alert-triangle me-1"></i> بدء داتا جديدة
          </button>
        </div>
        <div class="report-note-box mt-3">
          <p>تنبيه: هذا الإجراء غير قابل للتراجع. سيتم تحرير أرقام الجوال وإخفاء بيانات الحسابات الشخصية، مع تنظيف تذاكر الدعم وبقاء الحجوزات والمدفوعات والمحافظ والسجلات المالية محفوظة.</p>
        </div>
      </div>
    </div>

    <div class="modal fade" id="resetUsersModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="post" action="{{ route('admin.users.reset-all') }}">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title text-danger">تأكيد إعادة تهيئة البيانات</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
              <p class="mb-3">سيتم حذف حسابات المستخدمين غير الإداريين وتحرير أرقام الجوال، مع الحفاظ على السجلات المالية والتشغيلية السابقة. لا يمكن التراجع بعد التأكيد.</p>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="confirm_reset" name="confirm_reset" required>
                <label class="form-check-label" for="confirm_reset">أفهم أن العملية نهائية وأريد المتابعة.</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
              <button type="submit" class="btn btn-danger">تأكيد حذف الحسابات</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endcan

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card report-panel h-100">
        <div class="card-body">
          <div class="dashboard-section-head">
            <div>
              <h5 class="mb-1">اتجاه المستخدمين / الخطط / الحجوزات</h5>
              <p class="text-muted mb-0 small">قراءة يومية أو شهرية للحركة العامة داخل النظام بحسب النطاق المختار.</p>
            </div>
            <span class="report-tag"><i class="icon-base ti tabler-chart-area"></i>{{ $trendLabel ?? 'يومي' }}</span>
          </div>
          <div id="chart-users-plans" style="height: 320px;"></div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card report-panel h-100">
        <div class="card-body">
          <div class="dashboard-section-head">
            <div>
              <h5 class="mb-1">توزيع الحالات</h5>
              <p class="text-muted mb-0 small">ملخص بصري سريع لمكان تراكم العمل التشغيلي داخل دورة الطلب.</p>
            </div>
            <span class="report-tag"><i class="icon-base ti tabler-chart-bar"></i>الآن</span>
          </div>
          <div id="chart-statuses" style="height: 260px;"></div>
          <div class="report-note-box mt-3">
            <p>الأولوية اليومية تبدأ من <strong>بانتظار العروض</strong> ثم <strong>قيد التدريب</strong> لأنهما الأكثر تأثيرًا على التحويل والرضا.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-xl-5">
      <div class="card report-panel h-100">
        <div class="card-body">
          <div class="dashboard-section-head">
            <div>
              <h5 class="mb-1">حجم القاعدة الحالية</h5>
              <p class="text-muted mb-0 small">مقارنة سريعة بين الخطط، الدول، والمستخدمين المسجلين في الفترة الحالية.</p>
            </div>
            <span class="report-tag"><i class="icon-base ti tabler-chart-donut"></i>توزيع</span>
          </div>
          @if($overviewTotal > 0)
            <div id="chart-overview" style="height: 280px;"></div>
          @else
            <div class="report-empty dashboard-overview-empty">
              <span class="report-empty__icon"><i class="icon-base ti tabler-chart-donut"></i></span>
              <div class="fw-semibold">لا توجد بيانات كافية لعرض الرسم حالياً</div>
              <div class="text-muted small">سيظهر الرسم تلقائياً بعد إضافة خطط أو دول أو مستخدمين ضمن النطاق المحدد.</div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="col-xl-7">
      <div class="card report-panel h-100">
        <div class="card-body">
          <div class="dashboard-section-head">
            <div>
              <h5 class="mb-1">مؤشرات التشغيل السريعة</h5>
              <p class="text-muted mb-0 small">بطاقات ثانوية لقراءة التوسع الحالي في المستخدمين والطلبات دون مغادرة الصفحة.</p>
            </div>
            <span class="report-tag"><i class="icon-base ti tabler-sparkles"></i>Snapshot</span>
          </div>

          <div class="row g-3">
            @foreach($overviewStats as $stat)
              <div class="col-md-6 col-xl">
                <div class="report-stat dashboard-mini-stat h-100">
                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                      <div class="report-stat__label">{{ $stat['label'] }}</div>
                      <p class="report-stat__value">{{ $stat['formatted'] }}</p>
                    </div>
                    <span class="avatar-initial rounded bg-label-{{ $stat['tone'] }}">
                      <i class="icon-base ti tabler-{{ $stat['icon'] }}"></i>
                    </span>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="dashboard-mini-chart mt-4 pt-3 border-top">
            @if($overviewStatsTotal > 0)
              <div id="chart-quick-stats" style="height: 250px;"></div>
            @else
              <div class="report-empty dashboard-mini-chart__empty">
                <span class="report-empty__icon"><i class="icon-base ti tabler-chart-bar"></i></span>
                <div class="fw-semibold">لا توجد مؤشرات كافية لعرض الرسم حالياً</div>
                <div class="text-muted small">سيظهر الرسم فور توفر حجوزات أو مستخدمين أو خطط ضمن النطاق المحدد.</div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .dashboard-shell {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .dashboard-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }

  .dashboard-section-head--padded {
    padding: 1.4rem 1.4rem 0;
    margin-bottom: 0.8rem;
  }

  .dashboard-metric-card,
  .dashboard-status-card {
    height: 100%;
    border: 1px solid rgba(106, 125, 156, 0.12);
    border-radius: 22px;
    padding: 1.1rem;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    box-shadow: 0 12px 30px rgba(47, 43, 61, 0.06);
  }

  .dashboard-metric-card__top,
  .dashboard-status-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .dashboard-metric-card__icon,
  .dashboard-status-card__icon,
  .dashboard-queue__icon {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .dashboard-metric-card__value,
  .dashboard-status-card__value {
    font-size: 1.7rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 0.35rem;
  }

  .dashboard-metric-card__desc,
  .dashboard-status-card__desc {
    color: #64748b;
    font-size: 0.88rem;
  }

  .dashboard-mini-stat {
    border-radius: 20px;
  }

  .dashboard-mini-chart__empty {
    min-height: 250px;
  }

  .report-status--info {
    color: #155e75;
    background: #ecfeff;
  }

  .dashboard-overview-empty {
    min-height: 280px;
  }

  .dashboard-queue .list-group-item {
    border-color: #eef2f7;
    padding: 1rem 1.4rem;
  }

  @media (max-width: 767.98px) {
    .dashboard-metric-card__value,
    .dashboard-status-card__value {
      font-size: 1.45rem;
    }

    .dashboard-section-head--padded {
      padding: 1.15rem 1.15rem 0;
    }

    .dashboard-queue .list-group-item {
      padding: 0.95rem 1.15rem;
    }
  }
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

  const overviewChart = document.querySelector('#chart-overview');
  if (overviewChart) {
    new ApexCharts(overviewChart, {
      chart: { type: 'donut', height: 280 },
      labels: ['الخطط', 'الدول', 'المستخدمون'],
      series: [{{ $planCount }}, {{ $countriesCount }}, {{ $usersCount }}],
      legend: { position: 'bottom' },
      colors: ['#6366f1', '#22d3ee', '#22c55e'],
      stroke: { width: 0 }
    }).render();
  }

  const quickStatsChart = document.querySelector('#chart-quick-stats');
  if (quickStatsChart) {
    new ApexCharts(quickStatsChart, {
      chart: { type: 'bar', height: 250, toolbar: { show: false }, foreColor: '#6b7280' },
      series: [{
        name: 'المؤشر',
        data: @json($overviewStatsSeries),
      }],
      plotOptions: {
        bar: {
          horizontal: true,
          borderRadius: 8,
          barHeight: '52%',
          distributed: true,
        }
      },
      dataLabels: { enabled: true },
      xaxis: {
        categories: @json($overviewStatsLabels),
        min: 0,
      },
      yaxis: {
        labels: {
          maxWidth: 180,
        }
      },
      legend: { show: false },
      colors: ['#6366f1', '#22c55e', '#06b6d4', '#f59e0b', '#64748b'],
      grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
      tooltip: {
        y: {
          formatter: function(value) {
            return value.toLocaleString('en-US');
          }
        }
      }
    }).render();
  }

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
    xaxis: { categories: ['الكورسات الملغاة', 'بانتظار العروض', 'قيد التدريب', 'مكتملة'] },
    colors: ['#ef4444', '#f59e0b', '#0ea5e9', '#22c55e'],
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 }
  }).render();
});
</script>
@endpush
@endsection
