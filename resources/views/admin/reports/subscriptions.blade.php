@extends('admin.layouts.app')
@section('title','تقارير الاشتراكات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقارير الاشتراكات</li>
  </ol>
</nav>

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-info">
        <i class="icon-base ti tabler-calendar-event"></i>
      </span>
      <div>
        <h5 class="mb-0">الاشتراكات</h5>
        <small class="text-body-secondary">جميع الاشتراكات والطلبات</small>
      </div>
    </div>
    <a href="{{ route('admin.reports.subscriptions', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body">
    <form class="row g-3 mb-4" method="get">
      <div class="col-md-6">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select select2">
          <option value="">جميع الحالات</option>
          @foreach(['pending_payment','awaiting_offers','offer_selected','paid','in_training','completed','cancelled'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_',' ',$s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <button class="btn btn-primary w-100">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
      </div>
    </form>
    
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th><i class="icon-base ti tabler-hash me-1"></i> المعرف</th>
            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
            <th><i class="icon-base ti tabler-package me-1"></i> الخطة</th>
            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
            <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ البدء</th>
          </tr>
        </thead>
        <tbody>
          @forelse($subs as $r)
            <tr>
              <td><code class="text-primary">{{ substr($r->id, 0, 8) }}</code></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $r->user?->name ?? 'غير معروف' }}</span>
                  <small class="text-muted">{{ substr($r->user_id, 0, 8) }}</small>
                </div>
              </td>
              <td>
                <span class="fw-semibold">{{ $r->plan?->title ?? 'غير محدد' }}</span>
              </td>
              <td>
                <span class="badge bg-label-{{ $r->status === 'completed' ? 'success' : ($r->status === 'cancelled' ? 'danger' : 'warning') }}">
                  {{ str_replace('_', ' ', $r->status) }}
                </span>
              </td>
              <td>
                <small class="text-muted">{{ $r->start_date?->toDateString() ?? '—' }}</small>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                    <i class="icon-base ti tabler-calendar-event" style="font-size: 32px;"></i>
                  </span>
                  <p class="text-muted mb-0">لا توجد بيانات</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($subs->hasPages())
      <div class="card-footer border-0">{{ $subs->withQueryString()->links() }}</div>
    @endif
  </div>
</div>
@endsection

