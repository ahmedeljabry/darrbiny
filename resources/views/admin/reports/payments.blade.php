@extends('admin.layouts.app')
@section('title','تقارير المدفوعات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقارير المدفوعات</li>
  </ol>
</nav>

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-credit-card"></i>
      </span>
      <div>
        <h5 class="mb-0">المدفوعات</h5>
        <small class="text-body-secondary">جميع المدفوعات في النظام</small>
      </div>
    </div>
    <a href="{{ route('admin.reports.payments', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body">
    <form class="row g-3 mb-4" method="get">
      <div class="col-md-4">
        <label class="form-label">النوع</label>
        <select name="type" class="form-select select2">
          <option value="">جميع الأنواع</option>
          @foreach(\App\Models\Payment::typeLabels() as $value => $label)
            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select select2">
          <option value="">جميع الحالات</option>
          @foreach(['pending','succeeded','failed'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end">
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
            <th><i class="icon-base ti tabler-file-text me-1"></i> الطلب</th>
            <th><i class="icon-base ti tabler-currency-dollar me-1"></i> المبلغ</th>
            <th><i class="icon-base ti tabler-tag me-1"></i> النوع</th>
            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
            <th><i class="icon-base ti tabler-building me-1"></i> المزود</th>
            <th><i class="icon-base ti tabler-calendar me-1"></i> التاريخ</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $p)
            <tr>
              <td><code class="text-primary">{{ substr($p->id, 0, 8) }}</code></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $p->user?->name ?? 'غير معروف' }}</span>
                  <small class="text-muted">{{ substr($p->user_id, 0, 8) }}</small>
                </div>
              </td>
              <td><code>{{ substr($p->user_request_id, 0, 8) }}</code></td>
              <td>
                <span class="fw-semibold text-success">{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</span>
              </td>
              <td><span class="badge bg-label-primary">{{ $p->typeLabel() }}</span></td>
              <td>
                @if($p->status === 'succeeded')
                  <span class="badge bg-label-success">نجح</span>
                @elseif($p->status === 'pending')
                  <span class="badge bg-label-warning">قيد الانتظار</span>
                @else
                  <span class="badge bg-label-danger">فشل</span>
                @endif
              </td>
              <td><span class="badge bg-label-info">{{ $p->payment_method ?? '-' }}</span></td>
              <td><small class="text-muted">{{ $p->created_at?->format('Y-m-d H:i') }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                    <i class="icon-base ti tabler-credit-card" style="font-size: 32px;"></i>
                  </span>
                  <p class="text-muted mb-0">لا توجد بيانات</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($payments->hasPages())
      <div class="card-footer border-0">{{ $payments->withQueryString()->links() }}</div>
    @endif
  </div>
</div>
@endsection
