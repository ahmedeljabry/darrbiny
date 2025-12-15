@extends('admin.layouts.app')
@section('title', 'تعديل جائزة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.prizes.index') }}">الجوائز</a></li>
    <li class="breadcrumb-item active" aria-current="page">تعديل جائزة</li>
  </ol>
</nav>

@if ($errors->any())
  <div class="alert alert-danger alert-dismissible" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تعديل جائزة</h5>
                <a href="{{ route('admin.prizes.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.prizes.update', $prize->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">العنوان <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $prize->title) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">النقاط المطلوبة <span class="text-danger">*</span></label>
                            <input type="number" name="required_points" class="form-control" value="{{ old('required_points', $prize->required_points) }}" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الترتيب</label>
                            <input type="number" name="order" class="form-control" value="{{ old('order', $prize->order) }}" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الصورة</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">الحد الأقصى: 5MB</small>
                            @if($prize->image)
                                <div class="mt-2">
                                    <img src="{{ \App\Support\StorageUrl::make($prize->image) }}" alt="{{ $prize->title }}" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                                </div>
                            @endif
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="active" id="active" value="1" {{ old('active', $prize->active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">نشط</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <a href="{{ route('admin.prizes.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
