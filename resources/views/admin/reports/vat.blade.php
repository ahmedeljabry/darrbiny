@extends('admin.layouts.app')
@section('title', 'تقرير ضريبة القيمة المضافة')

@php
  $paymentMethodOptions = $paymentMethods->mapWithKeys(fn ($method) => [$method => strtoupper((string) $method)])->all();
  $countryOptions = $countries->pluck('name', 'id')->all();
  $typeLabelFor = fn ($type) => $typeOptions[$type] ?? \App\Models\Payment::typeLabelFor($type);
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم العميل أو المدرب أو رقم الطلب', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'type', 'label' => 'نوع الدفع', 'type' => 'select', 'options' => $typeOptions, 'placeholder' => 'كل الأنواع', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'payment_method', 'label' => 'طريقة الدفع', 'type' => 'select', 'options' => $paymentMethodOptions, 'placeholder' => 'كل الوسائل', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'country_id', 'label' => 'الدولة', 'type' => 'select', 'options' => $countryOptions, 'placeholder' => 'كل الدول', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => 'تقرير ضريبة القيمة المضافة',
    'subtitle' => 'حساب مباشر لقيمة الضريبة على المدفوعات الناجحة مع فلترة حسب النوع، الوسيلة، الدولة، والفترة الزمنية.',
    'icon' => 'receipt-tax',
    'tone' => 'danger',
    'tags' => [
      ['label' => 'النسبة الحالية ' . number_format((float) $vatPercent, 2) . '%', 'icon' => 'percentage'],
      ['label' => 'فلاتر متعددة', 'icon' => 'adjustments-horizontal'],
    ],
    'actions' => [
      ['label' => 'تصدير Excel', 'url' => route('admin.reports.vat', array_merge(request()->query(), ['export' => 'excel'])), 'class' => 'btn btn-success', 'icon' => 'file-excel'],
    ],
    'stats' => [
      ['label' => 'إجمالي الضريبة', 'value' => number_format(($vatTotalMinor ?? 0) / 100, 2) . ' ' . ($payments->first()?->currency ?? 'SAR'), 'icon' => 'receipt-tax'],
      ['label' => 'عدد العمليات', 'value' => number_format($count ?? 0), 'icon' => 'receipt-2', 'tone' => 'primary'],
      ['label' => 'نسبة الضريبة', 'value' => number_format((float) $vatPercent, 2) . '%', 'icon' => 'percentage', 'tone' => 'warning'],
      ['label' => 'أنواع الدفع المتاحة', 'value' => number_format(count($typeOptions)), 'icon' => 'tags', 'tone' => 'secondary'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.reports.vat'),
        'title' => 'فلترة الضريبة',
        'subtitle' => 'استخدم النوع وطريقة الدفع والدولة والتاريخ للوصول إلى المعاملات المطلوبة.',
      ])

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المعرف</th>
              <th>المستخدم</th>
              <th>الباقة / الدولة</th>
              <th>المبلغ</th>
              <th>النوع</th>
              <th>ضريبة القيمة المضافة</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $payment)
              @php $vatMinor = (int) round($payment->amount_minor * ($vatPercent / 100)); @endphp
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
                    <span class="fw-semibold">{{ $payment->userRequest?->plan?->title ?? 'بدون باقة' }}</span>
                    <small class="text-muted">{{ $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '—' }}</small>
                  </div>
                </td>
                <td><span class="fw-semibold text-success">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</span></td>
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="badge bg-label-primary">{{ $typeLabelFor($payment->type) }}</span>
                    <small class="text-muted">{{ strtoupper((string) ($payment->payment_method ?? '-')) }}</small>
                  </div>
                </td>
                <td><span class="fw-semibold text-danger">{{ number_format($vatMinor / 100, 2) }} {{ $payment->currency }}</span></td>
                <td><small class="text-muted">{{ $payment->created_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => 7, 'icon' => 'receipt-tax', 'message' => 'لا توجد عمليات ضمن الفلاتر الحالية لحساب الضريبة'])
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
