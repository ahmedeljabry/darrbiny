@extends('admin.layouts.app')
@section('title', 'تقارير الاشتراكات')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $converter = app(\App\Support\ReportCurrencyConverter::class);
  $countryOptions = $countries->pluck('name', 'id')->all();
  $planOptions = $plans->pluck('title', 'id')->all();
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم العميل أو المدرب أو رقم الطلب', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => $statusOptions, 'placeholder' => 'كل الحالات', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'country_id', 'label' => 'الدولة', 'type' => 'select', 'options' => $countryOptions, 'placeholder' => 'كل الدول', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'plan_id', 'label' => 'الباقة', 'type' => 'select', 'options' => $planOptions, 'placeholder' => 'كل الباقات', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ البدء', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ البدء', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];

  $completedCount = $subs->getCollection()->where('status', \App\Models\UserRequest::STATUS_COMPLETED)->count();
  $trainingCount = $subs->getCollection()->where('status', \App\Models\UserRequest::STATUS_IN_TRAINING)->count();
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'تقارير الاشتراكات',
    'subtitle' => 'عرض موحد لحالة الاشتراكات والطلبات مع فلترة حسب الباقة، الدولة، حالة الطلب، والبحث النصي.',
    'icon' => 'calendar-event',
    'tone' => 'info',
    'tags' => [
      ['label' => 'حالات الطلبات', 'icon' => 'timeline'],
      ['label' => 'فلترة بالتاريخ والباقة', 'icon' => 'calendar'],
      ['label' => 'المبالغ محولة إلى ' . $reportCurrency, 'icon' => 'exchange'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.subscriptions', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي النتائج', 'value' => number_format($count ?? 0), 'icon' => 'list-details'],
      ['label' => 'مكتملة في الصفحة', 'value' => number_format($completedCount), 'icon' => 'circle-check', 'tone' => 'success'],
      ['label' => 'قيد التدريب في الصفحة', 'value' => number_format($trainingCount), 'icon' => 'activity', 'tone' => 'primary'],
      ['label' => 'الباقات المتاحة', 'value' => number_format(count($planOptions)), 'icon' => 'package', 'tone' => 'warning'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.subscriptions'),
        'title' => 'فلترة الاشتراكات',
        'subtitle' => 'ابحث عن أي طلب بحسب العميل أو المدرب أو الباقة أو فترة البدء.',
      ])

      <div class="report-note-box mb-4">
        <p>كل مبالغ الاشتراكات في هذا التقرير معروضة بالـ {{ $reportCurrency }} بعد التحويل.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>رقم الطلب</th>
              <th>المستخدم</th>
              <th>المدرب</th>
              <th>الباقة / الدولة</th>
              <th>المبلغ</th>
              <th>الحالة</th>
              <th>تاريخ البدء</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subs as $subscription)
              @php
                $subscriptionAmountMinor = max(
                  (int) ($subscription->total_paid_minor ?? 0),
                  $subscription->totalSuccessfulPaymentsMinor()
                );
                $statusTone = match ($subscription->status) {
                  \App\Models\UserRequest::STATUS_COMPLETED => 'success',
                  \App\Models\UserRequest::STATUS_CANCELLED => 'danger',
                  \App\Models\UserRequest::STATUS_IN_TRAINING => 'primary',
                  \App\Models\UserRequest::STATUS_PENDING_PAYMENT,
                  \App\Models\UserRequest::STATUS_AWAITING_OFFERS,
                  \App\Models\UserRequest::STATUS_OFFER_SELECTED,
                  \App\Models\UserRequest::STATUS_PAID => 'warning',
                  default => 'secondary',
                };
              @endphp
              <tr>
                <td><code class="text-primary">#{{ $subscription->formatted_order_number ?? $subscription->order_number ?? '—' }}</code></td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $subscription->user?->name ?? 'غير معروف' }}</span>
                    <small class="text-muted">{{ $subscription->user?->phone_with_cc ?? $subscription->user_id }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $subscription->trainer?->name ?? 'بدون مدرب' }}</span>
                    <small class="text-muted">{{ $subscription->trainer?->phone_with_cc ?? '—' }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $subscription->plan?->title ?? 'غير محدد' }}</span>
                    <small class="text-muted">{{ $subscription->country?->name ?? $subscription->plan?->country?->name ?? '—' }}</small>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold">{{ $converter->formatConvertedMinor($subscriptionAmountMinor, $subscription->currency ?? 'SAR') }}</span>
                </td>
                <td><span class="report-status report-status--{{ $statusTone }}">{{ $statusOptions[$subscription->status] ?? $subscription->status }}</span></td>
                <td><small class="text-muted">{{ $subscription->start_date?->toDateString() ?? '—' }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 7, 'icon' => 'calendar-off', 'message' => 'لا توجد اشتراكات مطابقة للفلاتر الحالية'])
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($subs->hasPages())
      <div class="card-footer border-0 bg-white">{{ $subs->withQueryString()->links() }}</div>
    @endif
  </div>
@endsection
