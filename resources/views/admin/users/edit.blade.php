@extends('admin.layouts.app')
@section('title','تعديل مستخدم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active" aria-current="page">تعديل مستخدم</li>
  </ol>
</nav>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">تعديل مستخدم</h5>
    </div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.users._form')
      </form>
    </div>
  </div>
@endsection
