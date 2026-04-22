@extends('admin.layouts.app')
@section('title','الدول')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الدول</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-world"></i>
      </span>
      <div>
        <h5 class="mb-0">الدول</h5>
        <small class="text-body-secondary">إدارة الدول</small>
      </div>
    </div>
    <div class="d-flex align-items-end gap-2 flex-wrap">
      <form method="get" class="d-flex align-items-end gap-2">
        <div>
          <label class="form-label">بحث</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الاسم / ISO2 / العملة">
        </div>
        <div class="d-flex align-items-end gap-2">
          <button class="btn btn-outline-secondary">تصفية</button>
          <a href="{{ route('admin.geo.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
        </div>
      </form>
      <a href="{{ route('admin.geo.countries.create') }}" class="btn btn-primary">إضافة دولة</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><i class="icon-base ti tabler-world me-1"></i> ISO</th>
          <th><i class="icon-base ti tabler-map-pin me-1"></i> الاسم</th>
          <th><i class="icon-base ti tabler-currency-dollar me-1"></i> العملة</th>
          <th><i class="icon-base ti tabler-exchange me-1"></i> سعر 1 عملة بالـ SAR</th>
          <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($countries as $c)
          <tr>
            <td>{{ $c->iso2 }}</td>
            <td>{{ $c->name }}</td>
            <td>{{ $c->currency }}</td>
            <td>
              @if($c->currency === \App\Support\ReportCurrencyConverter::REPORT_CURRENCY)
                <span class="badge bg-label-success">1.000000</span>
              @elseif(isset($exchangeRates[$c->currency]))
                <span class="fw-semibold">{{ number_format((float) $exchangeRates[$c->currency], 6, '.', '') }}</span>
              @else
                <span class="badge bg-label-warning">غير محدد</span>
              @endif
            </td>
            <td>
              <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('admin.geo.countries.edit', $c->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                  <i class="icon-base ti tabler-edit"></i>
                </a>
                <form method="post" action="{{ route('admin.geo.countries.destroy', $c->id) }}" data-confirm="delete" class="d-inline">@csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger" type="submit" title="حذف">
                    <i class="icon-base ti tabler-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $countries->links() }}</div>
</div>

@push('scripts')
@endpush

@endsection
