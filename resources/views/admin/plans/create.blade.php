@extends('admin.layouts.app')
@section('title','إضافة خطة')
@section('content')
<form method="post" action="{{ route('admin.plans.store') }}">
  @csrf
  <div class="row g-6">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">تفاصيل الخطة</h5>
        </div>
        <div class="card-body">
          @include('admin.plans.form', ['plan' => null])
        </div>
      </div>

      @include('admin.plans.features_card', ['plan' => null])

      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
  </div>
</form>
@endsection
