@extends('admin.layouts.app')
@section('title','تذاكر الدعم')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">تذاكر الدعم</h5>
    <form method="get" class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label">بحث</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="الموضوع أو اسم المستخدم">
      </div>
      <div>
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" style="min-width:200px">
          <option value="">جميع الحالات</option>
          <option value="open" @selected($status==='open')>مفتوحة</option>
          <option value="pending" @selected($status==='pending')>قيد المعالجة</option>
          <option value="closed" @selected($status==='closed')>مغلقة</option>
        </select>
      </div>
      <div class="d-flex gap-2 align-items-end">
        <button class="btn btn-outline-secondary">تصفية</button>
        <a href="{{ route('admin.support.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
      </div>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>المستخدم</th>
          <th>الموضوع</th>
          <th>الحالة</th>
          <th>آخر تحديث</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($tickets as $t)
          <tr>
            <td>{{ optional($t->user)->name ?? '—' }}</td>
            <td>{{ $t->subject }}</td>
            <td><span class="badge bg-label-{{ $t->status === 'open' ? 'success' : ($t->status==='pending' ? 'warning' : 'secondary') }}">{{ $t->status }}</span></td>
            <td>{{ $t->updated_at->diffForHumans() }}</td>
            <td><a href="{{ route('admin.support.show', $t->id) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $tickets->withQueryString()->links() }}</div>
  
</div>
@endsection
