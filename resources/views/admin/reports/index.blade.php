@extends('admin.layouts.app')
@section('title', 'كل التقارير')

@php
  $reports = [
    'تقارير مالية' => [
      ['title' => 'المبيعات', 'desc' => 'المدفوعات الناجحة وإجمالي المبيعات مع فلترة بالنوع والوسيلة والدولة.', 'route' => 'admin.reports.sales', 'icon' => 'chart-line', 'color' => 'success'],
      ['title' => 'المدفوعات', 'desc' => 'كل المدفوعات مع فلاتر متعددة تشمل الحالة والباقة وطريقة الدفع.', 'route' => 'admin.reports.payments', 'icon' => 'credit-card', 'color' => 'primary'],
      ['title' => 'ضريبة القيمة المضافة', 'desc' => 'تتبع الضريبة المحتسبة على المعاملات المكتملة والقابلة للتصفية.', 'route' => 'admin.reports.vat', 'icon' => 'receipt-tax', 'color' => 'danger'],
    ],
    'تقارير تشغيلية' => [
      ['title' => 'الاشتراكات', 'desc' => 'حالات الطلبات والاشتراكات مع فلترة بالباقة والدولة والتاريخ.', 'route' => 'admin.reports.subscriptions', 'icon' => 'calendar-event', 'color' => 'info'],
      ['title' => 'مستحقات المدربين', 'desc' => 'صافي مستحقات الكورسات المكتملة مع فلترة بالاسم والجوال والتاريخ.', 'route' => 'admin.reports.completed-payouts', 'icon' => 'wallet', 'color' => 'success'],
      ['title' => 'رفض الإنجاز اليومي', 'desc' => 'الإنجازات اليومية المرفوضة مع فلترة بالاسم والجوال والنطاق الزمني.', 'route' => 'admin.reports.rejected-progress', 'icon' => 'alert-circle', 'color' => 'danger'],
    ],
    'تقارير المحافظ' => [
      ['title' => 'أرصدة المحافظ', 'desc' => 'المستخدمون الذين لديهم رصيد حالي في المحفظة.', 'route' => 'admin.reports.wallet-balances', 'icon' => 'wallet', 'color' => 'success'],
    ],
  ];

  $recentPaymentFilters = [
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'],
  ];

  $successfulCount = $payments->where('status', \App\Models\Payment::STATUS_SUCCEEDED)->count();
  $totalMinor = (int) $payments->sum('amount_minor');
  $rangeValues = ['from' => $from, 'to' => $to];
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'مركز التقارير',
    'subtitle' => 'واجهة موحدة للوصول السريع إلى كل تقارير الإدارة، مع معاينة مباشرة لآخر المدفوعات وإمكانية فلترتها بالتاريخ.',
    'icon' => 'chart-donut-3',
    'tone' => 'primary',
    'tags' => [
      ['label' => 'تقارير مالية وتشغيلية', 'icon' => 'layout-grid'],
      ['label' => 'تصميم موحد وسريع', 'icon' => 'stars'],
    ],
    'stats' => [
      ['label' => 'عدد التقارير', 'value' => number_format(collect($reports)->flatten(1)->count()), 'icon' => 'layout-grid'],
      ['label' => 'المدفوعات المعروضة', 'value' => number_format($payments->count()), 'icon' => 'credit-card', 'tone' => 'info'],
      ['label' => 'العمليات الناجحة', 'value' => number_format($successfulCount), 'icon' => 'circle-check', 'tone' => 'success'],
      ['label' => 'إجمالي المعاينة', 'value' => number_format($totalMinor / 100, 2) . ' ' . ($payments->first()?->currency ?? 'SAR'), 'icon' => 'coins', 'tone' => 'warning'],
    ],
  ])

  @foreach($reports as $groupTitle => $groupReports)
    <div class="card report-panel mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1">{{ $groupTitle }}</h5>
            <p class="text-muted mb-0 small">اختر التقرير المناسب ثم استخدم الفلاتر المخصصة داخل الصفحة.</p>
          </div>
          <span class="report-tag">
            <i class="icon-base ti tabler-layout-grid"></i>
            {{ count($groupReports) }} تقارير
          </span>
        </div>

        <div class="row g-3">
          @foreach($groupReports as $report)
            <div class="col-xl-4 col-lg-6">
              <a href="{{ route($report['route']) }}" class="report-directory-card">
                <div class="report-directory-card__top">
                  <span class="report-directory-card__icon bg-label-{{ $report['color'] }}">
                    <i class="icon-base ti tabler-{{ $report['icon'] }}"></i>
                  </span>
                  <span class="report-tag">{{ $report['title'] }}</span>
                </div>
                <div class="report-directory-card__title">{{ $report['title'] }}</div>
                <p class="report-directory-card__desc">{{ $report['desc'] }}</p>
                <div class="report-directory-card__foot">
                  <span>فتح التقرير</span>
                  <i class="icon-base ti tabler-arrow-up-left"></i>
                </div>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endforeach

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $recentPaymentFilters,
        'values' => $rangeValues,
        'resetUrl' => route('admin.reports.index'),
        'title' => 'آخر المدفوعات',
        'subtitle' => 'فلترة المعاينة الزمنية لآخر المدفوعات الظاهرة في هذه الصفحة.',
      ])

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المعرف</th>
              <th>المستخدم</th>
              <th>المبلغ</th>
              <th>النوع</th>
              <th>الحالة</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $payment)
              @php
                $statusTone = match ($payment->status) {
                  \App\Models\Payment::STATUS_SUCCEEDED => 'success',
                  \App\Models\Payment::STATUS_PENDING => 'warning',
                  \App\Models\Payment::STATUS_FAILED => 'danger',
                  default => 'secondary',
                };
              @endphp
              <tr>
                <td><code class="text-primary">{{ substr($payment->id, 0, 8) }}</code></td>
                <td>{{ $payment->user?->name ?? 'غير معروف' }}</td>
                <td>{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</td>
                <td>{{ $payment->typeLabel() }}</td>
                <td><span class="report-status report-status--{{ $statusTone }}">{{ $payment->statusLabel() }}</span></td>
                <td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 6, 'icon' => 'credit-card', 'message' => 'لا توجد مدفوعات في هذا النطاق الزمني'])
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
