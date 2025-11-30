@extends('admin.layouts.app')
@section('title','تقارير المبيعات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">تقارير المبيعات</li>
  </ol>
</nav>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">المبيعات (المدفوعات الناجحة)</h5>
    <form class="d-flex" method="get">
      <input type="date" name="from" value="{{ request('from') }}" class="form-control me-2">
      <input type="date" name="to" value="{{ request('to') }}" class="form-control me-2">
      <button class="btn btn-primary">تصفية</button>
    </form>
  </div>
  <div class="card-body">
    <div class="mb-3">الإجمالي: <strong>{{ number_format(($total ?? 0)/100,2) }}</strong></div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>المعرف</th><th>المستخدم</th><th>المبلغ</th><th>رسوم التطبيق</th><th>النوع</th><th>التاريخ</th></tr></thead>
        <tbody>
          @foreach($payments as $p)
            <tr>
              <td>{{ $p->id }}</td>
              <td>{{ $p->user_id }}</td>
              <td>{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</td>
              <td>{{ number_format($p->app_fee_minor/100,2) }} {{ $p->currency }}</td>
              <td>{{ $p->type }}</td>
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

