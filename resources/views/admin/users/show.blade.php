@extends('admin.layouts.app')
@section('title','بيانات المستخدم')
@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">بيانات المستخدم</h5>
      <a class="btn btn-sm btn-primary" href="{{ route('admin.users.edit',$user->id) }}">تعديل</a>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الاسم</div>
          <div class="text-heading">{{ $user->name ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">البريد الإلكتروني</div>
          <div class="text-heading">{{ $user->email ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الهاتف</div>
          <div class="text-heading">{{ $user->phone_with_cc }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الأدوار</div>
          <div class="text-heading">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الحالة</div>
          <div class="text-heading">{{ $user->isBanned() ? 'محظور' : 'نشط' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">محظور حتى</div>
          <div class="text-heading">{{ $user->banned_until?->format('Y-m-d H:i') ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">سبب الحظر</div>
          <div class="text-heading">{{ $user->banned_reason ?: '-' }}</div>
        </div>
      </div>
    </div>
  </div>
@endsection

