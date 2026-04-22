@extends('admin.layouts.app')
@section('title', 'حساب محفظة التطبيق')

@include('admin.reports.partials.theme')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم العميل أو المدرب أو رقم الطلب أو الملاحظات', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'direction', 'label' => 'نوع الحركة', 'type' => 'select', 'options' => $directionOptions, 'placeholder' => 'كل الحركات', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'source', 'label' => 'المصدر', 'type' => 'select', 'options' => $sourceOptions, 'placeholder' => 'كل المصادر', 'col' => 'col-xl-3 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];

  $sourceBreakdowns = collect($sourceOptions)
    ->map(function ($label, $key) use ($sourceTotalsMinor) {
      $amountMinor = (int) ($sourceTotalsMinor[$key] ?? 0);

      return [
        'label' => $label,
        'amount_minor' => $amountMinor,
        'tone' => str_starts_with($label, 'وارد') ? 'success' : 'danger',
      ];
    })
    ->filter(fn ($item) => $item['amount_minor'] > 0)
    ->values();
@endphp

@section('content')
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
      <li class="breadcrumb-item active" aria-current="page">حساب محفظة التطبيق</li>
    </ol>
  </nav>

  <div class="report-hero report-hero--primary mb-4">
    <div class="report-hero__body">
      <div class="report-hero__lead">
        <span class="report-hero__icon bg-label-primary">
          <i class="icon-base ti tabler-wallet"></i>
        </span>
        <div class="report-hero__text">
          <h2>حساب محفظة التطبيق</h2>
          <p>كشف حساب موحد لكل المدفوعات الواردة إلى محفظة التطبيق بكل أنواعها، وكل ما خرج منها كمصروفات تشغيلية أو مالية.</p>
          <div class="report-hero__tags">
            <span class="report-tag"><i class="icon-base ti tabler-arrow-down-left"></i> وارد وصادر في شاشة واحدة</span>
            <span class="report-tag"><i class="icon-base ti tabler-exchange"></i> المجاميع بالـ {{ $reportCurrency }}</span>
            <span class="report-tag"><i class="icon-base ti tabler-file-excel"></i> يدعم تصدير Excel</span>
          </div>
        </div>
      </div>

      <div class="report-hero__actions">
        <a
          href="{{ route('admin.app-wallet-account.index', array_merge(request()->query(), ['export' => 'excel'])) }}"
          class="btn btn-success"
        >
          <i class="icon-base ti tabler-file-excel me-1"></i>
          تصدير Excel
        </a>
      </div>
    </div>

    <div class="row g-3 report-stats mt-2">
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">إجمالي الوارد</div>
              <p class="report-stat__value">{{ number_format($incomingMinor / 100, 2) }} {{ $reportCurrency }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-arrow-down-left"></i>
            </span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">إجمالي الصادر</div>
              <p class="report-stat__value">{{ number_format($outgoingMinor / 100, 2) }} {{ $reportCurrency }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-arrow-up-right"></i>
            </span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">صافي المحفظة</div>
              <p class="report-stat__value">{{ number_format($netMinor / 100, 2) }} {{ $reportCurrency }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-wallet"></i>
            </span>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6">
        <div class="report-stat">
          <div class="d-flex align-items-center justify-content-between gap-2">
            <div>
              <div class="report-stat__label">عدد الحركات</div>
              <p class="report-stat__value">{{ number_format($entries->total()) }}</p>
            </div>
            <span class="avatar-initial rounded bg-label-warning">
              <i class="icon-base ti tabler-list-details"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.app-wallet-account.index'),
        'title' => 'فلترة كشف الحساب',
        'subtitle' => 'فلتر الوارد والصادر حسب المصدر أو التاريخ أو البحث النصي للوصول لأي حركة بسرعة.',
      ])

      <div class="report-note-box mb-4">
        <p>الوارد يشمل رسوم الحجز الثابتة ورسوم الحجز على الباقات والدفع الكلي بالكامل، مع بقاء فلتر مستقل لتحليل رسوم التطبيق فقط عند الحاجة. أما الصادر فيعكس مصروفات التطبيق المسجلة بالإضافة إلى مبالغ الاسترداد المعتمدة الناتجة عن الإلغاءات. كل القيم في هذه الشاشة معروضة بالـ {{ $reportCurrency }}.</p>
      </div>

      @if($sourceBreakdowns->isNotEmpty())
        <div class="row g-3 mb-4">
          @foreach($sourceBreakdowns as $item)
            <div class="col-xl-3 col-md-6">
              <div class="wallet-account-breakdown h-100">
                <div class="small text-muted mb-2">{{ $item['label'] }}</div>
                <div class="wallet-account-breakdown__value text-{{ $item['tone'] === 'success' ? 'success' : 'danger' }}">
                  {{ number_format($item['amount_minor'] / 100, 2) }} {{ $reportCurrency }}
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>المرجع</th>
              <th>الحركة</th>
              <th>المصدر</th>
              <th>الوصف / الطرف</th>
              <th>الطلب / الملاحظات</th>
              <th>المبلغ</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($entries as $entry)
              @php
                $directionTone = $entry->direction === 'in' ? 'success' : 'danger';
              @endphp
              <tr>
                <td><code class="text-primary">{{ $entry->reference_label }}</code></td>
                <td>
                  <span class="report-status report-status--{{ $directionTone }}">
                    {{ $entry->direction_label }}
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $entry->source_label }}</span>
                    <small class="text-muted">{{ $reportCurrency }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $entry->counterparty }}</span>
                    <small class="text-muted">{{ $entry->description }}</small>
                    <small class="text-muted">{{ $entry->details }}</small>
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span>{{ $entry->order_reference }}</span>
                    <small class="text-muted">{{ $entry->notes }}</small>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold text-{{ $directionTone }}">
                    {{ number_format($entry->report_amount_minor / 100, 2) }} {{ $entry->report_currency }}
                  </span>
                </td>
                <td><small class="text-muted">{{ $entry->occurred_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              <tr>
                <td colspan="7">
                  <div class="report-empty">
                    <span class="report-empty__icon"><i class="icon-base ti tabler-wallet-off"></i></span>
                    <div class="fw-semibold">لا توجد حركات مطابقة للفلترة الحالية</div>
                    <div class="text-muted small">جرّب تغيير التاريخ أو المصدر أو إزالة البحث النصي.</div>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($entries->hasPages())
      <div class="card-footer border-0 bg-white">{{ $entries->links() }}</div>
    @endif
  </div>
@endsection

@push('styles')
  <style>
    .wallet-account-breakdown {
      border: 1px solid rgba(106, 125, 156, 0.12);
      border-radius: 18px;
      padding: 1rem;
      background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
      box-shadow: 0 10px 24px rgba(47, 43, 61, 0.05);
    }

    .wallet-account-breakdown__value {
      font-size: 1.25rem;
      font-weight: 700;
    }
  </style>
@endpush
