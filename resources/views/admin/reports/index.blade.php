@extends('admin.layouts.app')
@section('title','Reports')
@section('content')
<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Sales Report</h5></div>
  <div class="card-body">
    <form class="row g-3" method="get">
      <div class="col-auto"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
      <div class="col-auto"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
      <div class="col-auto align-self-end"><button class="btn btn-primary">Apply</button></div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Request</th><th>Amount</th><th>App Fee</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($payments as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->user_id }}</td>
            <td>{{ $p->user_request_id }}</td>
            <td>{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</td>
            <td>{{ number_format($p->app_fee_minor/100,2) }} {{ $p->currency }}</td>
            <td>{{ $p->type }}</td>
            <td>{{ $p->status }}</td>
            <td>{{ $p->created_at }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

