@extends('admin.layouts.app')
@section('title','إضافة تقييم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.ratings.index') }}">التقييمات</a></li>
    <li class="breadcrumb-item active" aria-current="page">إضافة تقييم</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-header d-flex align-items-center gap-2">
    <span class="avatar-initial rounded bg-label-primary">
      <i class="icon-base ti tabler-star"></i>
    </span>
    <div>
      <h5 class="mb-0">إضافة تقييم جديد</h5>
      <small class="text-body-secondary">إنشاء تقييم جديد للمستخدم والمدرب</small>
    </div>
  </div>
  <div class="card-body">
    <form method="post" action="{{ route('admin.ratings.store') }}">
      @csrf
      
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">المستخدم <span class="text-danger">*</span></label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-user"></i></span>
            <select name="user_id" class="form-select" required>
              <option value="">— اختر المستخدم —</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id') === $user->id)>
                  {{ $user->name }} ({{ $user->phone }})
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">المدرب <span class="text-danger">*</span></label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-user-star"></i></span>
            <select name="trainer_id" class="form-select" required>
              <option value="">— اختر المدرب —</option>
              @foreach($trainers as $trainer)
                <option value="{{ $trainer->id }}" @selected(old('trainer_id') === $trainer->id)>
                  {{ $trainer->name }} ({{ $trainer->phone }})
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">الطلب <span class="text-danger">*</span></label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-file-text"></i></span>
            <select name="user_request_id" class="form-select" required>
              <option value="">— اختر الطلب —</option>
              @foreach($userRequests as $request)
                <option value="{{ $request->id }}" @selected(old('user_request_id') === $request->id)>
                  #{{ substr($request->id, 0, 8) }} - {{ $request->user->name ?? 'غير معروف' }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label">عدد النجوم <span class="text-danger">*</span></label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-star"></i></span>
            <input type="number" name="stars" class="form-control" min="1" max="5" value="{{ old('stars', 5) }}" required>
          </div>
          <small class="text-muted">من 1 إلى 5 نجوم</small>
        </div>
      </div>

      <div class="mb-3 mt-3">
        <label class="form-label">التعليق</label>
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-message"></i></span>
          <textarea name="comment" class="form-control" rows="4" placeholder="اكتب تعليق التقييم (اختياري)">{{ old('comment') }}</textarea>
        </div>
        <small class="text-muted">الحد الأقصى 1000 حرف</small>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.ratings.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

