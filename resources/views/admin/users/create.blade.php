@extends('admin.layouts.app')
@section('title','إنشاء مستخدم')
@section('content')
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

