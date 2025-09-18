@extends('admin.layouts.app')
@section('title','Ratings')
@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Ratings</h5></div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>User</th><th>Trainer</th><th>Request</th><th>Stars</th><th>Comment</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($ratings as $r)
          <tr>
            <td>{{ $r->user_id }}</td>
            <td>{{ $r->trainer_id }}</td>
            <td>{{ $r->user_request_id }}</td>
            <td>{{ $r->stars }}</td>
            <td>{{ Str::limit($r->comment, 60) }}</td>
            <td>{{ $r->created_at }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $ratings->links() }}</div>
</div>
@endsection

