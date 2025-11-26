@extends('admin.layouts.app')
@section('title','التقييمات')
@section('content')
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
  <div class="card-header"><h5 class="mb-0">التقييمات</h5></div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>المستخدم</th><th>المدرب</th><th>الطلب</th><th>النجوم</th><th>الملاحظة</th><th>الإجراءات</th></tr></thead>
      <tbody>
        @foreach($ratings as $r)
          <tr class="align-middle">
            <td>{{ $r->user_id }}</td>
            <td>{{ $r->trainer_id }}</td>
            <td>{{ $r->user_request_id }}</td>
            <td>
              <form method="post" action="{{ route('admin.ratings.update', $r) }}" class="d-flex align-items-center gap-2">
                @csrf
                @method('PUT')
                <input type="number" name="stars" min="1" max="5" value="{{ $r->stars }}" class="form-control form-control-sm" style="width:90px">
                <button class="btn btn-sm btn-outline-primary">حفظ</button>
              </form>
            </td>
            <td>
              <form method="post" action="{{ route('admin.ratings.update', $r) }}">
                @csrf
                @method('PUT')
                <div class="input-group input-group-sm">
                  <input type="hidden" name="stars" value="{{ $r->stars }}">
                  <input type="text" name="comment" value="{{ $r->comment }}" class="form-control" placeholder="تعديل التعليق">
                  <button class="btn btn-outline-primary">تحديث</button>
                </div>
              </form>
              <small class="text-muted d-block mt-1">{{ $r->created_at }}</small>
            </td>
            <td>
              <form method="post" action="{{ route('admin.ratings.destroy', $r) }}" onsubmit="return confirm('حذف هذا التقييم؟');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">حذف</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $ratings->links() }}</div>
</div>
@endsection
