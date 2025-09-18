@extends('admin.layouts.app')
@section('title','Subscription Reports')
@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Subscriptions</h5>
    <form class="d-flex" method="get">
      <select name="status" class="form-select me-2">
        <option value="">All statuses</option>
        @foreach(['pending_payment','awaiting_offers','offer_selected','paid','in_training','completed','cancelled'] as $s)
          <option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_',' ',$s) }}</option>
        @endforeach
      </select>
      <button class="btn btn-primary">Filter</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Status</th><th>Start</th></tr></thead>
      <tbody>
        @foreach($subs as $r)
          <tr>
            <td>{{ $r->id }}</td>
            <td>{{ optional($r->user)->name ?? $r->user_id }}</td>
            <td>{{ optional($r->plan)->title ?? $r->plan_id }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ optional($r->start_date)->toDateString() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $subs->withQueryString()->links() }}</div>
</div>
@endsection

