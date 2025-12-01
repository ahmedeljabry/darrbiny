@extends('admin.layouts.app')
@section('title','تقرير ضريبة القيمة المضافة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقرير ضريبة القيمة المضافة</li>
  </ol>
</nav>

<div class="card mb-4 border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-danger">
        <i class="icon-base ti tabler-receipt-tax"></i>
      </span>
      <div>
        <h5 class="mb-0">ضريبة القيمة المضافة</h5>
        <small class="text-body-secondary">النسبة الحالية: {{ $vatPercent }}%</small>
      </div>
    </div>
    <a href="{{ route('admin.reports.vat', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
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
    
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
      <i class="icon-base ti tabler-receipt-tax"></i>
      <div>
        <strong>إجمالي ضريبة القيمة المضافة:</strong> <span class="fw-bold text-danger">{{ number_format(($vatTotalMinor ?? 0)/100, 2) }} {{ $payments->first()?->currency ?? 'SAR' }}</span>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th><i class="icon-base ti tabler-hash me-1"></i> المعرف</th>
            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
            <th><i class="icon-base ti tabler-currency-dollar me-1"></i> المبلغ</th>
            <th><i class="icon-base ti tabler-tag me-1"></i> النوع</th>
            <th><i class="icon-base ti tabler-receipt-tax me-1"></i> ضريبة القيمة المضافة</th>
            <th><i class="icon-base ti tabler-calendar me-1"></i> التاريخ</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $p)
            @php $vatMinor = (int) round($p->amount_minor * ($vatPercent/100)); @endphp
            <tr>
              <td><code class="text-primary">{{ substr($p->id, 0, 8) }}</code></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-semibold">{{ $p->user?->name ?? 'غير معروف' }}</span>
                  <small class="text-muted">{{ substr($p->user_id, 0, 8) }}</small>
                </div>
              </td>
              <td>
                <span class="fw-semibold text-success">{{ number_format($p->amount_minor/100, 2) }} {{ $p->currency }}</span>
              </td>
              <td><span class="badge bg-label-primary">{{ $p->type }}</span></td>
              <td>
                <span class="fw-semibold text-danger">{{ number_format($vatMinor/100, 2) }}</span>
              </td>
              <td><small class="text-muted">{{ $p->created_at?->format('Y-m-d H:i') }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                  <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                    <i class="icon-base ti tabler-receipt-tax" style="font-size: 32px;"></i>
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
