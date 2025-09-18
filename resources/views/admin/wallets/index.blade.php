@extends('admin.layouts.app')
@section('title','Wallets')
@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Wallet Balances</h5></div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>User</th><th>Phone</th><th>Points</th></tr></thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name ?? $u->id }}</td>
            <td>{{ $u->phone_with_cc }}</td>
            <td>{{ $u->points_balance }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection

