@extends('admin.layouts.app')
@section('title','Sales Reports')
@section('content')
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Sales (Succeeded Payments)</h5>
    <form class="d-flex" method="get">
      <input type="date" name="from" value="{{ request('from') }}" class="form-control me-2">
      <input type="date" name="to" value="{{ request('to') }}" class="form-control me-2">
      <button class="btn btn-primary">Filter</button>
    </form>
  </div>
  <div class="card-body">
    <div class="mb-3">Total: <strong>{{ number_format(($total ?? 0)/100,2) }}</strong></div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>ID</th><th>User</th><th>Amount</th><th>App Fee</th><th>Type</th><th>Date</th></tr></thead>
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

