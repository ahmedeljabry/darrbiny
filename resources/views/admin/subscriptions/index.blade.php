@extends('admin.layouts.app')
@section('title','الاشتراكات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الاشتراكات</li>
  </ol>
</nav>

@php
  $statusLabels = [
    'pending_payment' => 'بانتظار الدفع',
    'awaiting_offers' => 'بانتظار العروض',
    'offer_selected' => 'تم اختيار عرض',
    'paid' => 'مدفوع',
    'in_training' => 'جار التدريب',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى',
  ];
@endphp
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-calendar-event"></i>
      </span>
      <div>
        <h5 class="mb-0">اشتراكات المستخدمين</h5>
        <small class="text-body-secondary">عرض النشطة، المكتملة أو بانتظار العروض</small>
      </div>
    </div>
    <form class="d-flex flex-wrap gap-2 align-items-end" method="get">
      <div>
        <label class="form-label mb-1">النطاق</label>
        <select name="scope" class="form-select form-select-sm">
          <option value="">جميع الحالات</option>
          <option value="active" @selected(($scope ?? request('scope'))==='active')>نشطة</option>
          <option value="completed" @selected(($scope ?? request('scope'))==='completed')>مكتملة</option>
          <option value="awaiting_offers" @selected(($scope ?? request('scope'))==='awaiting_offers')>بانتظار العروض</option>
        </select>
      </div>
      <div>
        <label class="form-label mb-1">الحالة</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">حالة محددة</option>
          @foreach($statusLabels as $key => $label)
            <option value="{{ $key }}" @selected(($status ?? request('status'))===$key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-primary">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
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
          <th>الخطة</th>
          <th>الحالة</th>
          <th>تاريخ البدء</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @forelse($subs as $r)
          <tr>
            <td><span class="text-muted">#{{ substr($r->id, 0, 8) }}</span></td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($r->user)->name ?? 'غير معروف' }}</span>
                <small class="text-muted">{{ $r->user_id }}</small>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($r->plan)->title ?? '-' }}</span>
                @if($r->plan)
                  <small class="text-muted">{{ optional($r->plan->city)->name ?? '' }}, {{ optional($r->plan->country)->name ?? '' }}</small>
                @endif
              </div>
            </td>
            <td>
              @php
                $statusColors = [
                  'pending_payment' => 'warning',
                  'awaiting_offers' => 'info',
                  'offer_selected' => 'primary',
                  'paid' => 'success',
                  'in_training' => 'primary',
                  'completed' => 'success',
                  'cancelled' => 'danger',
                ];
                $color = $statusColors[$r->status] ?? 'secondary';
              @endphp
              <span class="badge bg-label-{{ $color }}">
                {{ $statusLabels[$r->status] ?? $r->status }}
              </span>
            </td>
            <td>
              @if($r->start_date)
                <div class="d-flex flex-column">
                  <span>{{ $r->start_date->format('Y-m-d') }}</span>
                  <small class="text-muted">{{ $r->start_date->diffForHumans() }}</small>
                </div>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              <a href="{{ route('admin.bookings.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="icon-base ti tabler-eye"></i>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center py-5">
              <div class="d-flex flex-column align-items-center">
                <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                  <i class="icon-base ti tabler-calendar-event" style="font-size: 32px;"></i>
                </span>
                <p class="text-muted mb-0">لا توجد اشتراكات</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($subs->hasPages())
    <div class="card-footer">{{ $subs->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
