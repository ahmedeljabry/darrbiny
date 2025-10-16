@extends('admin.layouts.app')
@section('title','تعديل خطة')
@section('content')
<form method="post" action="{{ route('admin.plans.update', $plan->id) }}">
  @csrf
  @method('PUT')
  <div class="row g-6">
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">تعديل الخطة</h5>
        </div>
        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          @include('admin.plans.form', ['plan' => $plan])
        </div>
      </div>
      @include('admin.plans.features_card', ['plan' => $plan])
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
  </div>
</form>
@endsection
