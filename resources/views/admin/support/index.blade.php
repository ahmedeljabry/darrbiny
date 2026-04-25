@extends('admin.layouts.app')
@section('title','تذاكر الدعم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">تذاكر الدعم</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-headset"></i>
      </span>
      <div>
        <h5 class="mb-0">تذاكر الدعم</h5>
        <small class="text-body-secondary">إدارة طلبات الدعم الفني</small>
      </div>
    </div>
    <a href="{{ route('admin.support.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body">
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label">بحث</label>
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الموضوع أو اسم المستخدم">
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select select2">
          <option value="">جميع الحالات</option>
          <option value="open" @selected($status==='open')>مفتوحة</option>
          <option value="pending" @selected($status==='pending')>قيد المعالجة</option>
          <option value="closed" @selected($status==='closed')>مغلقة</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">من تاريخ</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">إلى تاريخ</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-primary flex-grow-1">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
        <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary">
          <i class="icon-base ti tabler-refresh me-1"></i> إعادة تعيين
        </a>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
            <th><i class="icon-base ti tabler-file-text me-1"></i> الموضوع</th>
            <th><i class="icon-base ti tabler-message me-1"></i> الرسائل</th>
            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
            <th><i class="icon-base ti tabler-calendar me-1"></i> آخر تحديث</th>
            <th><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tickets as $t)
            <tr>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ optional($t->user)->name ?? $t->name ?? 'غير معروف' }}</span>
                  <small class="text-muted">{{ optional($t->user)->phone_with_cc ?? $t->phone_with_cc ?? '-' }}</small>
                </div>
              </td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $t->subject }}</span>
                  <small class="text-muted">{{ Str::limit($t->latestMessage?->message ?? '', 50) }}</small>
                </div>
              </td>
              <td>
                <span class="badge bg-label-info">
                  <i class="icon-base ti tabler-message me-1"></i>
                  {{ $t->messages_count ?? $t->messages->count() ?? 0 }}
                </span>
              </td>
              <td>
                @php
                  $statusConfig = [
                    'open' => ['label' => 'مفتوحة', 'class' => 'success', 'icon' => 'circle-check'],
                    'pending' => ['label' => 'قيد المعالجة', 'class' => 'warning', 'icon' => 'clock'],
                    'closed' => ['label' => 'مغلقة', 'class' => 'secondary', 'icon' => 'circle-x'],
                  ];
                  $config = $statusConfig[$t->status] ?? ['label' => $t->status, 'class' => 'secondary', 'icon' => 'circle'];
                @endphp
                <span class="badge bg-label-{{ $config['class'] }}">
                  <i class="icon-base ti tabler-{{ $config['icon'] }} me-1"></i>
                  {{ $config['label'] }}
                </span>
              </td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $t->updated_at->format('Y-m-d') }}</span>
                  <small class="text-muted">{{ $t->updated_at->diffForHumans() }}</small>
                </div>
              </td>
              <td>
                <a href="{{ route('admin.support.show', $t->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="icon-base ti tabler-eye me-1"></i> عرض
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                    <i class="icon-base ti tabler-headset" style="font-size: 32px;"></i>
                  </span>
                  <p class="text-muted mb-0">لا توجد تذاكر دعم</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($tickets->hasPages())
      <div class="card-footer border-0">{{ $tickets->withQueryString()->links() }}</div>
    @endif
  </div>
</div>
@endsection
