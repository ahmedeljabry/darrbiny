@extends('admin.layouts.app')
@section('title','الدول')
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
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">الدول</h5>
    <div class="d-flex align-items-end gap-2 flex-wrap">
      <form method="get" class="d-flex align-items-end gap-2">
        <div>
          <label class="form-label">بحث</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الاسم / ISO2 / العملة">
        </div>
        <div class="d-flex align-items-end gap-2">
          <button class="btn btn-outline-secondary">تصفية</button>
          <a href="{{ route('admin.geo.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
        </div>
      </form>
      <a href="{{ route('admin.geo.countries.create') }}" class="btn btn-primary">إضافة دولة</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>ISO</th>
          <th>الاسم</th>
          <th>العملة</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($countries as $c)
          <tr>
            <td>{{ $c->iso2 }}</td>
            <td>{{ $c->name }}</td>
            <td>{{ $c->currency }}</td>
            <td class="d-flex gap-2">
              <a href="{{ route('admin.geo.countries.edit', $c->id) }}" class="btn btn-sm btn-outline-secondary">تعديل</a>
              <form method="post" action="{{ route('admin.geo.countries.destroy', $c->id) }}" data-confirm="delete">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit">حذف</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $countries->links() }}</div>
</div>

@push('scripts')
@endpush

@endsection
