@extends('admin.layouts.app')
@section('title','المحافظ')
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
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">أرصدة المحافظ</h5>
    <form class="d-flex align-items-end gap-2" method="post" action="{{ route('admin.wallets.store') }}">
      @csrf
      <div>
        <label class="form-label mb-1">المستخدم</label>
        <input type="text" name="user_id" class="form-control form-control-sm" placeholder="ID المستخدم" required>
      </div>
      <div>
        <label class="form-label mb-1">المبلغ</label>
        <input type="number" name="amount" class="form-control form-control-sm" min="1" step="1" required>
      </div>
      <div>
        <label class="form-label mb-1">ملاحظة (اختياري)</label>
        <input type="text" name="notes" class="form-control form-control-sm" placeholder="سبب الإضافة">
      </div>
      <button class="btn btn-sm btn-primary">إضافة رصيد</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>المستخدم</th><th>رقم الجوال</th><th>الرصيد</th></tr></thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name ?? $u->id }}</td>
            <td>{{ $u->phone_with_cc }}</td>
            <td>{{ $u->points_balance }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
