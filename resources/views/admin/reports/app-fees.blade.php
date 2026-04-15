@extends('admin.layouts.app')
@section('title', 'تقرير رسوم التطبيق')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $converter = app(\App\Support\ReportCurrencyConverter::class);
  $paymentMethodOptions = $paymentMethods->mapWithKeys(fn ($method) => [$method => strtoupper((string) $method)])->all();
  $countryOptions = $countries->pluck('name', 'id')->all();
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم العميل أو المدرب أو رقم الطلب', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'payment_method', 'label' => 'طريقة الدفع', 'type' => 'select', 'options' => $paymentMethodOptions, 'placeholder' => 'كل الوسائل', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'country_id', 'label' => 'الدولة', 'type' => 'select', 'options' => $countryOptions, 'placeholder' => 'كل الدول', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'تقرير رسوم التطبيق',
    'subtitle' => 'مراقبة رسوم التطبيق المستحقة على المدفوعات المكتملة الخاصة بالدفع الكلي مع دعم البحث والفلاتر المتعددة.',
    'icon' => 'percentage',
    'tone' => 'warning',
    'tags' => [
      ['label' => 'النوع plan_full فقط', 'icon' => 'filter'],
      ['label' => 'إجمالي الرسوم والمتوسط', 'icon' => 'calculator'],
      ['label' => 'المبالغ محولة إلى ' . $reportCurrency, 'icon' => 'exchange'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.app-fees', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي الرسوم', 'value' => number_format(($total ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'percentage'],
      ['label' => 'عدد العمليات', 'value' => number_format($count ?? 0), 'icon' => 'receipt-2', 'tone' => 'primary'],
      ['label' => 'متوسط الرسم', 'value' => number_format(($averageMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'chart-histogram', 'tone' => 'info'],
      ['label' => 'وسائل الدفع المتاحة', 'value' => number_format(count($paymentMethodOptions)), 'icon' => 'credit-card', 'tone' => 'secondary'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.app-fees'),
        'title' => 'فلترة رسوم التطبيق',
        'subtitle' => 'ركّز على الرسوم بحسب الدولة أو وسيلة الدفع أو الفترة أو البحث النصي.',
      ])

      <div class="report-note-box mb-4">
        <p>كل رسوم التطبيق في هذا التقرير معروضة بالـ {{ $reportCurrency }} بعد التحويل.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المعرف</th>
              <th>المستخدم</th>
              <th>المدرب / الطلب</th>
              <th>الباقة / الدولة</th>
              <th>رسوم التطبيق</th>
              <th>النوع</th>
              <th>طريقة الدفع</th>
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
                    <small class="text-muted">{{ $payment->user_request_id ? '#' . substr((string) $payment->user_request_id, 0, 8) : 'بدون طلب' }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $payment->userRequest?->plan?->title ?? 'بدون باقة' }}</span>
                    <small class="text-muted">{{ $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '—' }}</small>
                  </div>
                </td>
                <td><span class="fw-semibold text-warning">{{ $converter->formatConvertedMinor((int) $payment->app_fee_minor, $payment->currency) }}</span></td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-label-primary">{{ $payment->typeLabel() }}</span>
                    <small class="text-muted">{{ $payment->type }}</small>
                  </div>
                </td>
                <td>{{ strtoupper((string) ($payment->payment_method ?? '-')) }}</td>
                <td><small class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 8, 'icon' => 'percentage', 'message' => 'لا توجد رسوم تطبيق ضمن هذه الفلاتر'])
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
