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

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-headset"></i>
      </span>
      <div>
        <h5 class="mb-0">تذاكر الدعم</h5>
        <small class="text-body-secondary">إدارة طلبات الدعم الفني</small>
      </div>
    </div>
    <form method="get" class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label">بحث</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الموضوع أو اسم المستخدم">
      </div>
      <div>
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" style="min-width:200px">
          <option value="">جميع الحالات</option>
          <option value="open" @selected($status==='open')>مفتوحة</option>
          <option value="pending" @selected($status==='pending')>قيد المعالجة</option>
          <option value="closed" @selected($status==='closed')>مغلقة</option>
        </select>
      </div>
      <div class="d-flex gap-2 align-items-end">
        <button class="btn btn-outline-secondary">تصفية</button>
        <a href="{{ route('admin.support.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>المستخدم</th>
          <th>الموضوع</th>
          <th>الحالة</th>
          <th>آخر تحديث</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tickets as $t)
          <tr>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($t->user)->name ?? 'غير معروف' }}</span>
                <small class="text-muted">{{ optional($t->user)->phone_with_cc ?? '-' }}</small>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ $t->subject }}</span>
                <small class="text-muted">{{ Str::limit($t->latestMessage?->content ?? '', 50) }}</small>
              </div>
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
                <span>{{ $t->updated_at->format('Y-m-d') }}</span>
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
            <td colspan="5" class="text-center py-5">
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
    <div class="card-footer">{{ $tickets->withQueryString()->links() }}</div>
  @endif
  
</div>
@endsection
