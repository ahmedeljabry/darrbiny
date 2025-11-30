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

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-credit-card"></i>
      </span>
      <div>
        <h5 class="mb-0">المدفوعات</h5>
        <small class="text-body-secondary">جميع المعاملات المالية</small>
      </div>
    </div>
    <form class="d-flex flex-wrap gap-2 align-items-end" method="get">
      <div>
        <label class="form-label mb-1">النوع</label>
        <select name="type" class="form-select form-select-sm">
          <option value="">جميع الأنواع</option>
          <option value="reservation_fee" @selected(request('type')==='reservation_fee')>رسوم الحجز</option>
          <option value="plan_full" @selected(request('type')==='plan_full')>دفعة كاملة</option>
        </select>
      </div>
      <div>
        <label class="form-label mb-1">الحالة</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">جميع الحالات</option>
          <option value="pending" @selected(request('status')==='pending')>قيد الانتظار</option>
          <option value="succeeded" @selected(request('status')==='succeeded')>نجحت</option>
          <option value="failed" @selected(request('status')==='failed')>فشلت</option>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="icon-base ti tabler-refresh me-1"></i> إعادة تعيين
        </a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>المعرف</th>
          <th>المستخدم</th>
          <th>الطلب</th>
          <th>المبلغ</th>
          <th>النوع</th>
          <th>الحالة</th>
          <th>المزود</th>
          <th>التاريخ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td><span class="text-muted">#{{ substr($p->id, 0, 8) }}</span></td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($p->user)->name ?? 'غير معروف' }}</span>
                <small class="text-muted">{{ $p->user_id }}</small>
              </div>
            </td>
            <td>
              <a href="{{ route('admin.bookings.show', $p->user_request_id) }}" class="text-primary">
                #{{ substr($p->user_request_id, 0, 8) }}
              </a>
            </td>
            <td>
              <span class="fw-semibold">{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</span>
            </td>
            <td>
              <span class="badge bg-label-info">
                {{ $p->type === 'reservation_fee' ? 'رسوم الحجز' : 'دفعة كاملة' }}
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
              @if($p->provider)
                <span class="badge bg-label-primary">{{ $p->provider }}</span>
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
            <td colspan="8" class="text-center py-5">
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
    <div class="card-footer">{{ $payments->withQueryString()->links() }}</div>
  @endif
</div>
@endsection

