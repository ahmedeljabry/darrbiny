@extends('admin.layouts.app')
@section('title','Roles & Permissions')
@section('content')
<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0">Create Role</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.roles.store') }}">@csrf
          <input class="form-control mb-3" name="name" placeholder="Role name" required>
          <button class="btn btn-primary">Create</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h6 class="mb-0">Roles</h6></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Name</th><th>Permissions</th><th>Assign</th><th>Action</th></tr></thead>
          <tbody>
            @foreach($roles as $role)
              <tr>
                <td class="align-middle">{{ $role->name }}</td>
                <td class="align-middle">
                  <form method="post" action="{{ route('admin.roles.update',$role->id) }}" class="d-flex gap-2 align-items-center">@csrf @method('put')
                    <select multiple size="5" name="permissions[]" class="form-select" style="min-width: 220px;">
                      @foreach($perms as $p)
                        <option value="{{ $p->name }}" @selected($role->hasPermissionTo($p->name))>{{ $p->name }}</option>
                      @endforeach
                    </select>
                    <button class="btn btn-sm btn-primary">Save</button>
                  </form>
                </td>
                <td class="align-middle"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.index') }}?role={{ $role->name }}">View Users</a></td>
                <td class="align-middle">
                  @if($role->name !== 'ADMIN')
                  <form method="post" action="{{ route('admin.roles.destroy',$role->id) }}">@csrf @method('delete')
                    <button class="btn btn-sm btn-danger">Delete</button>
                  </form>
                  @endif
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

