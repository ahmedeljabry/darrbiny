@extends('admin.layouts.app')
@section('title','إنشاء مستخدم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active" aria-current="page">إنشاء مستخدم</li>
  </ol>
</nav>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">إنشاء مستخدم</h5>
    </div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.users.store') }}">
        @csrf
        @include('admin.users._form')
      </form>
    </div>
  </div>
@endsection

