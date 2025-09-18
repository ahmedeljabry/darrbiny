@extends('admin.layouts.app')
@section('title','الخطط')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

<div class="row g-6">
  <div class="col-12 col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">إضافة خطة</h5>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.plans.store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">العنوان</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">الوصف</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">الساعات</label>
              <input type="number" min="1" name="hours_count" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">نوع التدريب</label>
              <input type="text" name="training_type" class="form-control" required>
            </div>
          </div>
          <div class="row g-3 mt-0">
            <div class="col-md-6">
              <label class="form-label">السعر الأساسي (بالعملة الفرعية)</label>
              <input type="number" min="0" name="base_price_minor" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">نشطة؟</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                <label class="form-check-label">نعم</label>
              </div>
            </div>
          </div>
          <div class="mt-4 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">قائمة الخطط</h5>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>العنوان</th>
              <th>النوع</th>
              <th>الساعات</th>
              <th>السعر</th>
              <th>الحالة</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($plans as $plan)
              <tr>
                <td>{{ $plan->title }}</td>
                <td>{{ $plan->training_type }}</td>
                <td>{{ $plan->hours_count }}</td>
                <td>{{ number_format($plan->base_price_minor/100, 2) }}</td>
                <td>
                  <span class="badge bg-label-{{ $plan->is_active ? 'success':'secondary' }}">{{ $plan->is_active ? 'نشطة' : 'مخفية' }}</span>
                </td>
                <td class="d-flex gap-2 flex-wrap">
                  <form method="post" action="{{ route('admin.plans.update', $plan->id) }}" class="d-flex align-items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="title" value="{{ $plan->title }}">
                    <input type="hidden" name="training_type" value="{{ $plan->training_type }}">
                    <input type="hidden" name="hours_count" value="{{ $plan->hours_count }}">
                    <input type="hidden" name="base_price_minor" value="{{ $plan->base_price_minor }}">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($plan->is_active) onchange="this.form.submit()">
                      <label class="form-check-label">نشطة</label>
                    </div>
                  </form>
                  <form method="post" action="{{ route('admin.plans.destroy', $plan->id) }}" onsubmit="return confirm('حذف الخطة؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer">{{ $plans->links() }}</div>
    </div>
  </div>
</div>
@endsection

