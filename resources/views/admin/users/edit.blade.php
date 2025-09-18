@extends('admin.layouts.app')
@section('title','تعديل مستخدم')
@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">تعديل مستخدم</h5>
    </div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.users.update', $user->id) }}">
        @csrf
        @method('PUT')
        @include('admin.users._form')
      </form>
    </div>
  </div>
@endsection

