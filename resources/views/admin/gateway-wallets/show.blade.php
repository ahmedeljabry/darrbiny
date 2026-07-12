@extends('admin.layouts.app')
@section('title', $gatewayConfig['title'] ?? 'محفظة بوابة الدفع')

@include('admin.reports.partials.theme')

@php
  $reportCurrency = \App\Support\ReportCurrencyConverter::REPORT_CURRENCY;
  $statCards = [
    ['label' => 'إجمالي المبيعات', 'value' => $summary['sales_minor'], 'tone' => 'primary', 'icon' => 'receipt'],
    ['label' => 'إجمالي الوارد', 'value' => $summary['incoming_minor'], 'tone' => 'success', 'icon' => 'arrow-down-left'],
    ['label' => 'رسوم بوابة الدفع', 'value' => $summary['gateway_fee_minor'], 'tone' => 'danger', 'icon' => 'receipt-tax'],
    ['label' => 'الضريبة', 'value' => $summary['vat_minor'], 'tone' => 'warning', 'icon' => 'percentage'],
    ['label' => 'المتبقي لدى البوابة', 'value' => $summary['remaining_gateway_minor'], 'tone' => 'info', 'icon' => 'building-bank'],
    ['label' => 'إجمالي التحويلات', 'value' => $summary['transfers_minor'], 'tone' => 'secondary', 'icon' => 'transfer'],
    ['label' => 'رصيد المحفظة', 'value' => $summary['wallet_balance_minor'], 'tone' => 'success', 'icon' => 'wallet'],
    ['label' => 'عدد العمليات', 'value' => $summary['operations_count'], 'tone' => 'dark', 'icon' => 'list-details', 'is_count' => true],
  ];
  $filterFields = [
    ['name' => 'search', 'label' => 'بحث سريع', 'placeholder' => 'اسم العميل أو المدرب أو رقم الطلب أو المرجع', 'col' => 'col-xl-4 col-md-6'],
    ['name' => 'direction', 'label' => 'نوع الحركة', 'type' => 'select', 'options' => $directionOptions, 'placeholder' => 'كل الحركات', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'source', 'label' => 'المصدر', 'type' => 'select', 'options' => $sourceOptions, 'placeholder' => 'كل المصادر', 'col' => 'col-xl-3 col-md-3'],
    ['name' => 'from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
    ['name' => 'to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-3'],
  ];
@endphp

@section('content')
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ $gatewayConfig['title'] }}</li>
    </ol>
  </nav>

  @if (session('status'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="report-hero report-hero--primary mb-4">
    <div class="report-hero__body">
      <div class="report-hero__lead">
        <span class="report-hero__icon bg-label-{{ $gatewayConfig['tone'] ?? 'primary' }}">
          <i class="icon-base ti tabler-credit-card"></i>
        </span>
        <div class="report-hero__text">
          <h2>{{ $gatewayConfig['title'] }}</h2>
          <p>كشف محفظة بوابة الدفع مطابق لملف Excel: مبيعات البوابة، الرسوم، الضريبة، الوارد اليدوي، والتحويلات.</p>
          <div class="report-hero__tags">
            @foreach($gateways as $gatewayKey => $config)
              <a
                href="{{ route('admin.gateway-wallets.show', $gatewayKey) }}"
                class="report-tag {{ $gatewayKey === $gateway ? 'bg-label-primary' : '' }}"
              >
                <i class="icon-base ti tabler-wallet"></i> {{ $config['label'] }}
              </a>
            @endforeach
          </div>
        </div>
      </div>
      <div class="report-hero__actions">
        <a
          href="{{ route('admin.gateway-wallets.show', array_merge(['gateway' => $gateway], request()->query(), ['export' => 'excel'])) }}"
          class="btn btn-success"
        >
          <i class="icon-base ti tabler-file-excel me-1"></i>
          تصدير Excel
        </a>
      </div>
    </div>

    <div class="row g-3 report-stats mt-2">
      @foreach($statCards as $card)
        <div class="col-xl-3 col-md-6">
          <div class="report-stat">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div>
                <div class="report-stat__label">{{ $card['label'] }}</div>
                <p class="report-stat__value">
                  @if($card['is_count'] ?? false)
                    {{ number_format((int) $card['value']) }}
                  @else
                    {{ number_format(((int) $card['value']) / 100, 2) }} {{ $reportCurrency }}
                  @endif
                </p>
              </div>
              <span class="avatar-initial rounded bg-label-{{ $card['tone'] }}">
                <i class="icon-base ti tabler-{{ $card['icon'] }}"></i>
              </span>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card report-panel">
    <div class="card-body">
      <div class="dashboard-section-head">
        <div>
          <h5 class="mb-1">تسجيل حركة يدوية</h5>
          <p class="text-muted mb-0 small">استخدمها لتسجيل إيداع البنك أو تحويل الرصيد من محفظة البوابة إلى محفظة التطبيق.</p>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-lg-6">
          <form method="post" action="{{ route('admin.gateway-wallets.transactions.store', $gateway) }}" class="border rounded-3 p-3 h-100">
            @csrf
            <input type="hidden" name="direction" value="{{ \App\Models\GatewayWalletTransaction::DIRECTION_IN }}">
            <h6 class="mb-3">الوارد</h6>
            <div class="mb-3">
              <label class="form-label">نوع الوارد</label>
              <select name="source" class="form-select" required>
                @foreach($incomingSourceOptions as $sourceKey => $sourceLabel)
                  <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">المبلغ</label>
              <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظات</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="رقم التحويل أو بيانات البنك"></textarea>
            </div>
            <button type="submit" class="btn btn-success">
              <i class="icon-base ti tabler-arrow-down-left me-1"></i>
              تسجيل وارد
            </button>
          </form>
        </div>
        <div class="col-lg-6">
          <form method="post" action="{{ route('admin.gateway-wallets.transactions.store', $gateway) }}" class="border rounded-3 p-3 h-100">
            @csrf
            <input type="hidden" name="direction" value="{{ \App\Models\GatewayWalletTransaction::DIRECTION_OUT }}">
            <h6 class="mb-3">التحويلات والمصروفات</h6>
            <div class="mb-3">
              <label class="form-label">نوع الحركة</label>
              <select name="source" class="form-select" required>
                @foreach($outgoingSourceOptions as $sourceKey => $sourceLabel)
                  <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">المبلغ</label>
              <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required>
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظات</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="تفاصيل التحويل أو المصروف"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">
              <i class="icon-base ti tabler-arrow-up-right me-1"></i>
              تسجيل صادر
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card report-panel">
    <div class="card-body">
      @include('admin.reports.partials.filter-fields', [
        'fields' => $filterFields,
        'values' => $filters,
        'resetUrl' => route('admin.gateway-wallets.show', $gateway),
        'title' => 'فلترة كشف محفظة البوابة',
        'subtitle' => 'فلتر العمليات حسب الحركة أو المصدر أو التاريخ أو رقم الطلب والمرجع.',
      ])

      <div class="report-note-box mb-4">
        <p>صفوف المبيعات تأتي تلقائيًا من المدفوعات الناجحة بطريقة دفع {{ strtoupper($gateway) }}. الرسوم والضريبة محسوبة لكل عملية مثل ملف Excel، والحركات اليدوية تستخدم للوارد والتحويلات.</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              <th>رقم المرجع</th>
              <th>الحركة</th>
              <th>المصدر</th>
              <th>الوصف / الطرف</th>
              <th>الدولة</th>
              <th>الطلب / الملاحظات</th>
              <th>المبلغ</th>
              <th>الرسوم</th>
              <th>الضريبة</th>
              <th>المستحق</th>
              <th>التاريخ</th>
            </tr>
          </thead>
          <tbody>
            @forelse($entries as $entry)
              @php
                $directionTone = $entry->direction === \App\Models\GatewayWalletTransaction::DIRECTION_IN ? 'success' : 'danger';
              @endphp
              <tr>
                <td><code class="text-primary">{{ $entry->reference_label }}</code></td>
                <td><span class="report-status report-status--{{ $directionTone }}">{{ $entry->direction_label }}</span></td>
                <td>{{ $entry->source_label }}</td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $entry->counterparty }}</span>
                    <small class="text-muted">{{ $entry->description }}</small>
                  </div>
                </td>
                <td>{{ $entry->country }}</td>
                <td>
                  <small class="text-muted" style="white-space: pre-line">{{ $entry->order_notes }}</small>
                </td>
                <td class="fw-semibold">{{ number_format($entry->amount_minor / 100, 2) }} {{ $entry->currency }}</td>
                <td>{{ number_format($entry->fee_minor / 100, 2) }} {{ $entry->currency }}</td>
                <td>{{ number_format($entry->vat_minor / 100, 2) }} {{ $entry->currency }}</td>
                <td class="fw-semibold text-{{ $entry->net_minor >= 0 ? 'success' : 'danger' }}">
                  {{ number_format($entry->net_minor / 100, 2) }} {{ $entry->currency }}
                </td>
                <td><small class="text-muted">{{ $entry->occurred_at?->format('Y-m-d H:i') }}</small></td>
              </tr>
            @empty
              <tr>
                <td colspan="11">
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
