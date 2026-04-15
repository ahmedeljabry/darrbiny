@extends('admin.layouts.app')
@section('title', 'تقارير المدفوعات')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $converter = app(\App\Support\ReportCurrencyConverter::class);
  $paymentMethodOptions = $paymentMethods->mapWithKeys(fn ($method) => [$method => strtoupper((string) $method)])->all();
  $countryOptions = $countries->pluck('name', 'id')->all();
  $planOptions = $plans->pluck('title', 'id')->all();
  $typeLabelFor = fn ($type) => $typeOptions[$type] ?? \App\Models\Payment::typeLabelFor($type);
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'رقم العملية أو الطلب أو اسم العميل/المدرب', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'type', 'label' => 'نوع الدفع', 'type' => 'select', 'options' => $typeOptions, 'placeholder' => 'كل الأنواع', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => $statusOptions, 'placeholder' => 'كل الحالات', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'payment_method', 'label' => 'طريقة الدفع', 'type' => 'select', 'options' => $paymentMethodOptions, 'placeholder' => 'كل الوسائل', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'country_id', 'label' => 'الدولة', 'type' => 'select', 'options' => $countryOptions, 'placeholder' => 'كل الدول', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'plan_id', 'label' => 'الباقة', 'type' => 'select', 'options' => $planOptions, 'placeholder' => 'كل الباقات', 'col' => 'col-xl-3 col-md-4'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'],
  ];

  $successfulCount = $payments->getCollection()->where('status', \App\Models\Payment::STATUS_SUCCEEDED)->count();
  $pendingCount = $payments->getCollection()->where('status', \App\Models\Payment::STATUS_PENDING)->count();
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'تقارير المدفوعات',
    'subtitle' => 'واجهة موحدة لمراجعة كل المدفوعات مع فلترة بالنوع والحالة والباقات والدولة وطريقة الدفع.',
    'icon' => 'credit-card',
    'tone' => 'primary',
    'tags' => [
      ['label' => 'بحث نصي متعدد', 'icon' => 'search'],
      ['label' => 'فلترة بحسب الباقة والدولة', 'icon' => 'world'],
      ['label' => 'المجاميع محولة إلى ' . $reportCurrency, 'icon' => 'exchange'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.payments', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي المدفوعات', 'value' => number_format(($totalMinor ?? 0) / 100, 2) . ' ' . $reportCurrency, 'icon' => 'coins'],
      ['label' => 'عدد العمليات', 'value' => number_format($count ?? 0), 'icon' => 'receipt-2', 'tone' => 'info'],
      ['label' => 'العمليات الناجحة في الصفحة', 'value' => number_format($successfulCount), 'icon' => 'circle-check', 'tone' => 'success'],
      ['label' => 'قيد الانتظار في الصفحة', 'value' => number_format($pendingCount), 'icon' => 'clock-hour-4', 'tone' => 'warning'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.payments'),
        'title' => 'فلترة المدفوعات',
        'subtitle' => 'استخدم أكثر من فلتر معًا لعزل المدفوعات المطلوبة بسرعة.',
      ])

      <div class="report-note-box mb-4">
        <p>كل مبالغ هذا التقرير معروضة بالـ {{ $reportCurrency }} حسب معدل التحويل المحدد في الإعدادات.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المعرف</th>
              <th>المستخدم</th>
              <th>المدرب / الطلب</th>
              <th>الباقة / الدولة</th>
              <th>المبلغ</th>
              <th>النوع</th>
              <th>الحالة</th>
              <th>طريقة الدفع</th>
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
                <td><span class="fw-semibold text-success">{{ $converter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency) }}</span></td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-label-primary">{{ $typeLabelFor($payment->type) }}</span>
                    <small class="text-muted">{{ $payment->type }}</small>
                  </div>
                </td>
                <td><span class="report-status report-status--{{ $statusTone }}">{{ $payment->statusLabel() }}</span></td>
                <td>{{ strtoupper((string) ($payment->payment_method ?? '-')) }}</td>
                <td><small class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 9, 'icon' => 'credit-card', 'message' => 'لا توجد مدفوعات مطابقة للفلترة الحالية'])
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
