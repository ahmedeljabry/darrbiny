@extends('admin.layouts.app')
@section('title','المدفوعات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">المدفوعات</li>
  </ol>
</nav>

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3">
    <div class="d-flex align-items-center gap-3">
      <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
        <i class="icon-base ti tabler-credit-card" style="font-size: 24px;"></i>
      </span>
      <div>
        <h5 class="mb-0 fw-bold">المدفوعات</h5>
        <small class="text-muted">جميع المعاملات المالية</small>
      </div>
    </div>
    <a href="{{ route('admin.payments.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body pt-0">
    <form class="row g-3 mb-4" method="get">
      <div class="col-md-4">
        <label class="form-label fw-semibold small">النوع</label>
        <select name="type" class="form-select form-select-sm">
          <option value="">جميع الأنواع</option>
          @foreach(\App\Models\Payment::typeLabels() as $value => $label)
            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">الحالة</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">جميع الحالات</option>
          <option value="pending" @selected(request('status')==='pending')>قيد الانتظار</option>
          <option value="succeeded" @selected(request('status')==='succeeded')>نجحت</option>
          <option value="failed" @selected(request('status')==='failed')>فشلت</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary btn-sm w-100" type="submit">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary btn-sm w-100">
          <i class="icon-base ti tabler-refresh me-1"></i> إعادة تعيين
        </a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 130px;"><i class="icon-base ti tabler-hash me-1"></i> رقم الطلب</th>
          <th style="width: 200px;"><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
          <th style="width: 130px;"><i class="icon-base ti tabler-currency-dollar me-1"></i> المبلغ</th>
          <th style="width: 120px;"><i class="icon-base ti tabler-tag me-1"></i> النوع</th>
          <th style="width: 130px;"><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
          <th style="width: 120px;"><i class="icon-base ti tabler-building me-1"></i> المزود</th>
          <th style="width: 150px;"><i class="icon-base ti tabler-calendar me-1"></i> التاريخ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td>
              @if($p->userRequest)
                <a href="{{ route('admin.bookings.show', $p->user_request_id) }}" class="fw-semibold text-primary text-decoration-none">
                  #{{ $p->userRequest->formatted_order_number ?? $p->userRequest->order_number ?? '—' }}
                </a>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                @if($p->user?->profile_picture_url)
                  <img src="{{ $p->user->profile_picture_url }}" alt="{{ $p->user->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                @else
                  <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                    {{ substr($p->user->name ?? 'U', 0, 1) }}
                  </span>
                @endif
                <div>
                  <div class="fw-semibold">{{ optional($p->user)->name ?? 'غير معروف' }}</div>
                  <small class="text-muted">{{ substr($p->user_id, 0, 8) }}</small>
                </div>
              </div>
            </td>
            <td>
              <span class="fw-semibold">{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</span>
            </td>
            <td>
              <span class="badge bg-label-info">
                {{ $p->typeLabel() }}
              </span>
            </td>
            <td>
              @php
                $statusConfig = [
                  'succeeded' => ['label' => 'نجحت', 'class' => 'success'],
                  'pending' => ['label' => 'قيد الانتظار', 'class' => 'warning'],
                  'failed' => ['label' => 'فشلت', 'class' => 'danger'],
                ];
                $config = $statusConfig[$p->status] ?? ['label' => $p->status, 'class' => 'secondary'];
              @endphp
              <span class="badge bg-label-{{ $config['class'] }}">
                <i class="icon-base ti tabler-{{ $p->status === 'succeeded' ? 'check' : ($p->status === 'pending' ? 'clock' : 'x') }} me-1"></i>
                {{ $config['label'] }}
              </span>
            </td>
            <td>
              @if($p->payment_method)
                <span class="badge bg-label-primary">{{ $p->payment_method }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              <div class="d-flex flex-column">
                <span>{{ $p->created_at->format('Y-m-d') }}</span>
                <small class="text-muted">{{ $p->created_at->format('H:i') }}</small>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5">
              <div class="d-flex flex-column align-items-center">
                <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                  <i class="icon-base ti tabler-credit-card" style="font-size: 32px;"></i>
                </span>
                <p class="text-muted mb-0">لا توجد مدفوعات</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($payments->hasPages())
    <div class="card-footer border-top">{{ $payments->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
