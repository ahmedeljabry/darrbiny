@extends('admin.layouts.app')
@section('title', 'تقارير المبيعات')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $paymentMethodOptions = $paymentMethods->mapWithKeys(fn ($method) => [$method => strtoupper((string) $method)])->all();
  $countryOptions = $countries->pluck('name', 'id')->all();
  $typeLabelFor = fn ($type) => $typeOptions[$type] ?? \App\Models\Payment::typeLabelFor($type);
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم المستخدم أو المدرب أو رقم الطلب', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'type', 'label' => 'نوع الدفع', 'type' => 'select', 'options' => $typeOptions, 'placeholder' => 'كل الأنواع', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'payment_method', 'label' => 'طريقة الدفع', 'type' => 'select', 'options' => $paymentMethodOptions, 'placeholder' => 'كل الوسائل', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'country_id', 'label' => 'الدولة', 'type' => 'select', 'options' => $countryOptions, 'placeholder' => 'كل الدول', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'تقارير المبيعات',
    'subtitle' => 'لوحة مبيعات أنظف مع فلترة حسب الدولة، نوع الدفع، الوسيلة، والبحث النصي.',
    'icon' => 'chart-line',
    'tone' => 'success',
    'tags' => [
      ['label' => 'المدفوعات الناجحة فقط', 'icon' => 'circle-check'],
      ['label' => 'فلاتر متعددة', 'icon' => 'adjustments-horizontal'],
      ['label' => 'المجاميع محولة إلى ' . $reportCurrency, 'icon' => 'exchange'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.sales', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي المبيعات', 'value' => number_format(($total ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'coins'],
      ['label' => 'عدد العمليات', 'value' => number_format($count ?? 0), 'icon' => 'receipt-2', 'tone' => 'primary'],
      ['label' => 'متوسط العملية', 'value' => number_format(($averageMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'chart-histogram', 'tone' => 'info'],
      ['label' => 'وسائل الدفع النشطة', 'value' => number_format(count($paymentMethodOptions)), 'icon' => 'credit-card', 'tone' => 'warning'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.sales'),
        'title' => 'فلترة المبيعات',
        'subtitle' => 'امزج بين التاريخ والدولة والوسيلة والبحث للوصول لأي دفعة بسرعة.',
      ])

      <div class="report-note-box mb-4">
        <p>إجماليات التقرير ومتوسطاته معروضة بالـ {{ $reportCurrency }} بعد تطبيق معدل التحويل، بينما يحتفظ كل صف بالعملة الأصلية الخاصة بالعملية.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المعرف</th>
              <th>المستخدم</th>
              <th>المدرب / الباقة</th>
              <th>الدولة</th>
              <th>الموقع</th>
              <th>المبلغ</th>
              <th>رسوم التطبيق</th>
              <th>النوع / الوسيلة</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $payment)
              <tr>
                <td><code class="text-primary">{{ substr($payment->id, 0, 8) }}</code></td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $payment->user?->name ?? 'غير معروف' }}</span>
                    <small class="text-muted">{{ $payment->user?->phone_with_cc ?? $payment->user_id }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $payment->userRequest?->trainer?->name ?? 'بدون مدرب' }}</span>
                    <small class="text-muted">{{ $payment->userRequest?->plan?->title ?? 'بدون باقة' }}</small>
                  </div>
                </td>
                <td>{{ $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '—' }}</td>
                <td>
                  <div class="d-flex flex-column">
                    <span>{{ $payment->userRequest?->area_level_1 ?? '—' }}</span>
                    <small class="text-muted">
                      {{ collect([$payment->userRequest?->area_level_2, $payment->userRequest?->area_level_3, $payment->userRequest?->locality])->filter()->implode(' / ') ?: 'بدون تفاصيل إضافية' }}
                    </small>
                  </div>
                </td>
                <td><span class="fw-semibold text-success">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</span></td>
                <td><span class="fw-semibold text-warning">{{ number_format($payment->app_fee_minor / 100, 2) }} {{ $payment->currency }}</span></td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-label-primary">{{ $typeLabelFor($payment->type) }}</span>
                    <small class="text-muted">{{ strtoupper((string) ($payment->payment_method ?? '-')) }}</small>
                  </div>
                </td>
                <td><small class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 9, 'icon' => 'chart-line', 'message' => 'لا توجد نتائج مطابقة للفلاتر الحالية'])
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($payments->hasPages())
      <div class="card-footer border-0 bg-white">{{ $payments->withQueryString()->links() }}</div>
    @endif
  </div>
@endsection
