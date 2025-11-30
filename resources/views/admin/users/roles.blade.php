@extends('admin.layouts.app')
@section('title','تعيين الأدوار')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active" aria-current="page">تعيين الأدوار</li>
  </ol>
</nav>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Assign Roles to {{ $user->name ?? $user->id }}</h5></div>
  <div class="card-body">
    <form method="post" action="{{ route('admin.users.roles.update',$user->id) }}">@csrf @method('put')
      <div class="mb-3">
        <label class="form-label">Roles</label>
        <select multiple name="roles[]" class="form-select" size="8">
          @foreach($roles as $r)
            <option value="{{ $r->name }}" @selected($user->hasRole($r->name))>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-primary">Save</button>
    </form>
  </div>
</div>
@endsection

