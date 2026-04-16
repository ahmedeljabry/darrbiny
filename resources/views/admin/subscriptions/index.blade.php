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
<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3">
    <div class="d-flex align-items-center gap-3">
      <span class="avatar-initial rounded bg-label-info" style="width: 48px; height: 48px;">
        <i class="icon-base ti tabler-calendar-event" style="font-size: 24px;"></i>
      </span>
      <div>
        <h5 class="mb-0 fw-bold">الاشتراكات</h5>
        <small class="text-muted">عرض وإدارة جميع الاشتراكات</small>
      </div>
    </div>
    <a href="{{ route('admin.subscriptions.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body pt-0">
    <form class="row g-3 mb-4" method="get">
      <div class="col-md-4">
        <label class="form-label fw-semibold small">النطاق</label>
        <select name="scope" class="form-select form-select-sm">
          <option value="">جميع الحالات</option>
          <option value="active" @selected(($scope ?? request('scope'))==='active')>نشطة</option>
          <option value="completed" @selected(($scope ?? request('scope'))==='completed')>مكتملة</option>
          <option value="awaiting_offers" @selected(($scope ?? request('scope'))==='awaiting_offers')>بانتظار العروض</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">الحالة</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">حالة محددة</option>
          @foreach($statusLabels as $key => $label)
            <option value="{{ $key }}" @selected(($status ?? request('status'))===$key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary btn-sm w-100" type="submit">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary btn-sm w-100">
          <i class="icon-base ti tabler-refresh me-1"></i> إعادة تعيين
        </a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 150px;"><i class="icon-base ti tabler-hash me-1"></i> رقم الطلب</th>
          <th style="width: 200px;"><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
          <th style="width: 200px;"><i class="icon-base ti tabler-file-check me-1"></i> الخطة</th>
          <th style="width: 130px;"><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
          <th style="width: 150px;"><i class="icon-base ti tabler-calendar me-1"></i> تاريخ البدء</th>
          <th style="width: 100px;" class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @forelse($subs as $r)
          <tr>
            <td><code class="text-primary">#{{ $r->formatted_order_number ?? $r->order_number ?? '—' }}</code></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                @if($r->user?->profile_picture_url)
                  <img src="{{ $r->user->profile_picture_url }}" alt="{{ $r->user->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                @else
                  <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                    {{ substr($r->user->name ?? 'U', 0, 1) }}
                  </span>
                @endif
                <div>
                  <div class="fw-semibold">{{ optional($r->user)->name ?? 'غير معروف' }}</div>
                  <small class="text-muted">{{ substr($r->user_id, 0, 8) }}</small>
                </div>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($r->plan)->title ?? '-' }}</span>
                @if($r->plan)
                  @php
                    $subLocation = implode(' ، ', array_filter([
                      $r->locality,
                      $r->area_level_3,
                      $r->area_level_2,
                      $r->area_level_1,
                      $r->country?->name ?? optional($r->plan->country)->name,
                    ]));
                  @endphp
                  <small class="text-muted">{{ $subLocation !== '' ? $subLocation : '-' }}</small>
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
            <td class="text-center">
              <a href="{{ route('admin.bookings.show', $r->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="عرض التفاصيل">
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
    <div class="card-footer border-top">{{ $subs->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
