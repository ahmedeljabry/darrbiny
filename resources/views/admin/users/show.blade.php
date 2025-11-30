@extends('admin.layouts.app')
@section('title','بيانات المستخدم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active" aria-current="page">بيانات المستخدم</li>
  </ol>
</nav>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">بيانات المستخدم</h5>
      <div class="d-flex gap-2">
        <a class="btn btn-sm btn-primary" href="{{ route('admin.users.edit',$user->id) }}">تعديل</a>
      </div>
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
  @if($user->trainerProfile)
    @php
      $profile = $user->trainerProfile;
      $profileComplete = filled($profile->bio) && filled($profile->car_type) && filled($profile->car_model_year) && $profile->has_driving_license && filled($profile->country_id) && filled($profile->city_id);
    @endphp
    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">بيانات الكابتن</h5>
        <span class="badge {{ $profileComplete ? 'bg-success' : 'bg-label-warning' }}">
          {{ $profileComplete ? 'مكتمل' : 'غير مكتمل' }}
        </span>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">نوع السيارة</div>
            <div class="text-heading">{{ $profile->car_type ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">موديل السيارة</div>
            <div class="text-heading">{{ $profile->car_model_year ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">رخصة القيادة</div>
            <div class="text-heading">{{ $profile->has_driving_license ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">توفر سيارة للتدريب</div>
            <div class="text-heading">{{ $profile->car_available ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">خدمة التوصيل</div>
            <div class="text-heading">{{ $profile->pickup_available ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">الدولة</div>
            <div class="text-heading">{{ $profile->country?->name ?? '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">المدينة / المحافظة</div>
            <div class="text-heading">{{ $profile->city?->name ?? '-' }}</div>
          </div>
        </div>
        <div class="mt-4">
          <div class="fw-medium text-body-secondary mb-2">النبذة التعريفية</div>
          <div class="border rounded p-3 bg-body-tertiary">
            @if($profile->bio)
              {!! nl2br(e($profile->bio)) !!}
            @else
              <span class="text-body-secondary">لا توجد نبذة</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection
