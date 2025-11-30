@extends('admin.layouts.app')
@section('title','تقرير مبيعات الباقات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقرير مبيعات الباقات</li>
  </ol>
</nav>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">تقرير مبيعات الباقات</h5>
      <small class="text-body-secondary">يشمل المدفوعات المكتملة فقط</small>
    </div>
    <form class="d-flex flex-wrap gap-2" method="get">
      <input type="date" name="from" value="{{ request('from') }}" class="form-control">
      <input type="date" name="to" value="{{ request('to') }}" class="form-control">
      <button class="btn btn-primary">تصفية</button>
    </form>
  </div>
  <div class="card-body">
    <div class="mb-3">إجمالي المبيعات: <strong>{{ number_format(($total ?? 0)/100, 2) }}</strong></div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>المدرب</th>
            <th>الباقة</th>
            <th>المبلغ</th>
            <th>العمولة</th>
            <th>تاريخ/وقت</th>
          </tr>
        </thead>
        <tbody>
          @foreach($payments as $p)
            <tr>
              <td>{{ $p->id }}</td>
              <td>{{ $p->user?->name ?? $p->user_id }}</td>
              <td>{{ optional($p->userRequest?->trainer)->name ?? '-' }}</td>
              <td>{{ optional($p->userRequest?->plan)->title ?? '-' }}</td>
              <td>{{ number_format($p->amount_minor/100, 2) }} {{ $p->currency }}</td>
              <td>{{ number_format($p->app_fee_minor/100, 2) }} {{ $p->currency }}</td>
              <td>{{ $p->created_at }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $payments->withQueryString()->links() }}
  </div>
</div>
@endsection
