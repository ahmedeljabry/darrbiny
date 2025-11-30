@extends('admin.layouts.app')
@section('title','تقرير ضريبة القيمة المضافة')
@section('content')
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">ضريبة القيمة المضافة</h5>
      <small class="text-body-secondary">النسبة الحالية: {{ $vatPercent }}%</small>
    </div>
    <form class="d-flex flex-wrap gap-2" method="get">
      <input type="date" name="from" value="{{ request('from') }}" class="form-control">
      <input type="date" name="to" value="{{ request('to') }}" class="form-control">
      <button class="btn btn-primary">تصفية</button>
    </form>
  </div>
  <div class="card-body">
    <div class="mb-3">إجمالي ضريبة القيمة المضافة: <strong>{{ number_format(($vatTotalMinor ?? 0)/100, 2) }}</strong></div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>المعرف</th>
            <th>المستخدم</th>
            <th>المبلغ</th>
            <th>النوع</th>
            <th>ضريبة القيمة المضافة</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          @foreach($payments as $p)
            @php $vatMinor = (int) round($p->amount_minor * ($vatPercent/100)); @endphp
            <tr>
              <td>{{ $p->id }}</td>
              <td>{{ $p->user?->name ?? $p->user_id }}</td>
              <td>{{ number_format($p->amount_minor/100, 2) }} {{ $p->currency }}</td>
              <td>{{ $p->type }}</td>
              <td>{{ number_format($vatMinor/100, 2) }}</td>
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
