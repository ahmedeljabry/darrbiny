@extends('admin.layouts.app')
@section('title','Permissions')
@section('content')
<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0">Create Permission</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.permissions.store') }}">@csrf
          <input class="form-control mb-3" name="name" placeholder="Permission name" required>
          <button class="btn btn-primary">Create</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h6 class="mb-0">All Permissions</h6></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Name</th><th>Action</th></tr></thead>
          <tbody>
            @foreach($perms as $p)
              <tr>
                <td>{{ $p->name }}</td>
                <td>
                  <form method="post" action="{{ route('admin.permissions.destroy',$p->id) }}">@csrf @method('delete')
                    <button class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

