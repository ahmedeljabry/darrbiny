@extends('admin.layouts.app')
@section('title', 'تقرير إيرادات الباقات')

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
    'title' => 'تقرير إيرادات الباقات',
    'subtitle' => 'قراءة مركزة لعمليات الدفع الكلي الخاصة بالباقات مع معلومات الموقع والمدرب والرسوم في نفس الجدول.',
    'icon' => 'package',
    'tone' => 'info',
    'tags' => [
      ['label' => 'المدفوعات المكتملة فقط', 'icon' => 'circle-check'],
      ['label' => 'فلترة بالدولة والوسيلة', 'icon' => 'world'],
      ['label' => 'المبالغ محولة إلى ' . $reportCurrency, 'icon' => 'exchange'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.plan-sales', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي الإيرادات', 'value' => number_format(($total ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'coins'],
      ['label' => 'عدد العمليات', 'value' => number_format($count ?? 0), 'icon' => 'receipt-2', 'tone' => 'primary'],
      ['label' => 'متوسط العملية', 'value' => number_format(($averageMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'chart-histogram', 'tone' => 'success'],
      ['label' => 'وسائل الدفع المتاحة', 'value' => number_format(count($paymentMethodOptions)), 'icon' => 'credit-card', 'tone' => 'warning'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.plan-sales'),
        'title' => 'فلترة إيرادات الباقات',
        'subtitle' => 'فلترة مرنة بالبحث النصي أو طريقة الدفع أو الدولة أو المدة الزمنية.',
      ])

      <div class="report-note-box mb-4">
        <p>كل مبالغ إيرادات الباقات في هذا التقرير معروضة بالـ {{ $reportCurrency }} بعد التحويل، وإجمالي الإيرادات الصافي يخصم مبالغ الإلغاءات المعتمدة.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>رقم الطلب</th>
              <th>المستخدم</th>
              <th>المدرب / الباقة</th>
              <th>الدولة</th>
              <th>تفاصيل الموقع</th>
              <th>المبلغ</th>
              <th>رسوم التطبيق</th>
              <th>طريقة الدفع</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $payment)
              <tr>
                <td><code class="text-primary">#{{ $payment->userRequest?->formatted_order_number ?? $payment->userRequest?->order_number ?? '—' }}</code></td>
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
                    <small class="text-muted">{{ collect([$payment->userRequest?->area_level_2, $payment->userRequest?->area_level_3, $payment->userRequest?->locality])->filter()->implode(' / ') ?: 'بدون تفاصيل إضافية' }}</small>
                  </div>
                </td>
                <td><span class="fw-semibold text-success">{{ $converter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency) }}</span></td>
                <td><span class="fw-semibold text-warning">{{ $converter->formatConvertedMinor((int) $payment->app_fee_minor, $payment->currency) }}</span></td>
                <td>{{ strtoupper((string) ($payment->payment_method ?? '-')) }}</td>
                <td><small class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 9, 'icon' => 'package', 'message' => 'لا توجد إيرادات باقات ضمن هذه الفلاتر'])
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
