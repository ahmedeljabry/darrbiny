@extends('admin.layouts.app')
@section('title','Payment Reports')
@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Payments</h5>
    <form class="d-flex" method="get">
      <select name="type" class="form-select me-2">
        <option value="">All types</option>
        @foreach(['reservation_fee','plan_full'] as $t)
          <option value="{{ $t }}" @selected(request('type')===$t)>{{ $t }}</option>
        @endforeach
      </select>
      <select name="status" class="form-select me-2">
        <option value="">All status</option>
        @foreach(['pending','succeeded','failed'] as $s)
          <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
        @endforeach
      </select>
      <button class="btn btn-primary">Filter</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Request</th><th>Amount</th><th>Type</th><th>Status</th><th>Provider</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($payments as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->user_id }}</td>
            <td>{{ $p->user_request_id }}</td>
            <td>{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</td>
            <td>{{ $p->type }}</td>
            <td>{{ $p->status }}</td>
            <td>{{ $p->provider }}</td>
            <td>{{ $p->created_at }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection

