@extends('admin.layouts.app')
@section('title','تقرير رسوم التطبيق')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقرير رسوم التطبيق</li>
  </ol>
</nav>

<div class="card mb-4 border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-warning">
        <i class="icon-base ti tabler-percentage"></i>
      </span>
      <div>
        <h5 class="mb-0">رسوم التطبيق</h5>
        <small class="text-body-secondary">رسوم التطبيق على المدفوعات المكتملة من نوع {{ \App\Models\Payment::TYPE_PLAN_FULL }}</small>
      </div>
    </div>
    <a href="{{ route('admin.reports.app-fees', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
      <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
    </a>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-4">
      <div class="col-md-12">
        <form class="d-flex gap-2 flex-wrap" method="get">
          <div class="flex-grow-1 d-flex gap-2">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" placeholder="من تاريخ">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" placeholder="إلى تاريخ">
          </div>
          <button class="btn btn-primary">
            <i class="icon-base ti tabler-filter me-1"></i> تصفية
          </button>
        </form>
      </div>
    </div>
    
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
      <i class="icon-base ti tabler-percentage"></i>
      <div>
        <strong>إجمالي الرسوم:</strong> <span class="fw-bold text-warning">{{ number_format(($total ?? 0)/100, 2) }} {{ $payments->first()?->currency ?? 'SAR' }}</span>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th><i class="icon-base ti tabler-hash me-1"></i> المعرف</th>
            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
            <th><i class="icon-base ti tabler-file-text me-1"></i> الطلب</th>
            <th><i class="icon-base ti tabler-percentage me-1"></i> رسوم التطبيق</th>
            <th><i class="icon-base ti tabler-tag me-1"></i> النوع</th>
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
                <span class="fw-semibold text-warning">{{ number_format($p->app_fee_minor/100, 2) }} {{ $p->currency }}</span>
              </td>
              <td><span class="badge bg-label-primary">{{ $p->type }}</span></td>
              <td><small class="text-muted">{{ $p->created_at?->format('Y-m-d H:i') }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                    <i class="icon-base ti tabler-percentage" style="font-size: 32px;"></i>
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
