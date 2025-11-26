@extends('admin.layouts.app')
@section('title','الأدوار والصلاحيات')
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

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header border-0">
        <h5 class="mb-1">إنشاء دور جديد</h5>
        <small class="text-muted">أدخل اسم الدور ثم أضف صلاحياته من الجدول.</small>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.roles.store') }}">@csrf
          <div class="mb-3">
            <label class="form-label">اسم الدور</label>
            <input class="form-control" name="name" placeholder="مثال: SUPERVISOR" required>
          </div>
          <button class="btn btn-primary w-100">إنشاء الدور</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">إدارة الأدوار</h5>
          <small class="text-muted">تعديل الصلاحيات أو عرض المستخدمين لكل دور.</small>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>الدور</th>
                <th>الصلاحيات</th>
                <th class="text-center">المستخدمون</th>
                <th class="text-center">الإجراء</th>
              </tr>
            </thead>
            <tbody>
              @foreach($roles as $role)
                <tr>
                  <td class="fw-semibold">{{ $role->name }}</td>
                  <td>
                    <form method="post" action="{{ route('admin.roles.update',$role->id) }}" class="d-flex flex-column flex-lg-row gap-2 align-items-start align-items-lg-center">
                      @csrf @method('put')
                      <select multiple size="6" name="permissions[]" class="form-select" style="min-width: 240px;">
                        @foreach($perms as $p)
                          <option value="{{ $p->name }}" @selected($role->hasPermissionTo($p->name))>{{ $p->name }}</option>
                        @endforeach
                      </select>
                      <button class="btn btn-sm btn-primary">حفظ الصلاحيات</button>
                    </form>
                  </td>
                  <td class="text-center">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.index') }}?role={{ $role->name }}">عرض المستخدمين</a>
                  </td>
                  <td class="text-center">
                    @if($role->name !== 'ADMIN')
                      <form method="post" action="{{ route('admin.roles.destroy',$role->id) }}" onsubmit="return confirm('حذف هذا الدور؟');">
                        @csrf @method('delete')
                        <button class="btn btn-sm btn-outline-danger">حذف</button>
                      </form>
                    @else
                      <span class="badge bg-label-secondary">محمي</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
