@extends('admin.layouts.app')
@section('title','الأدوار والصلاحيات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الأدوار والصلاحيات</li>
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

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header border-0 d-flex align-items-center gap-2">
        <span class="avatar-initial rounded bg-label-primary">
          <i class="icon-base ti tabler-user-plus"></i>
        </span>
        <div>
          <h5 class="mb-1">إنشاء دور جديد</h5>
          <small class="text-muted">أدخل اسم الدور ثم أضف صلاحياته من الجدول</small>
        </div>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.roles.store') }}">@csrf
          <div class="mb-3">
            <label class="form-label fw-semibold">اسم الدور</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-shield"></i></span>
              <input class="form-control" name="name" placeholder="مثال: SUPERVISOR" required>
            </div>
            <small class="text-muted d-block mt-1">استخدم أسماء بالأحرف الكبيرة</small>
          </div>
          <button class="btn btn-primary w-100">
            <i class="icon-base ti tabler-plus me-1"></i> إنشاء الدور
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center gap-2">
        <span class="avatar-initial rounded bg-label-success">
          <i class="icon-base ti tabler-shield-check"></i>
        </span>
        <div>
          <h5 class="mb-1">إدارة الأدوار</h5>
          <small class="text-muted">تعديل الصلاحيات أو عرض المستخدمين لكل دور</small>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><i class="icon-base ti tabler-shield me-1"></i> الدور</th>
                <th><i class="icon-base ti tabler-key me-1"></i> الصلاحيات</th>
                <th class="text-center"><i class="icon-base ti tabler-users me-1"></i> المستخدمون</th>
                <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> الإجراء</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $role)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-label-primary">{{ $role->name }}</span>
                      @if($role->name === 'ADMIN')
                        <span class="badge bg-label-warning">
                          <i class="icon-base ti tabler-shield-check me-1"></i> محمي
                        </span>
                      @endif
                    </div>
                  </td>
                  <td>
                    <form method="post" action="{{ route('admin.roles.update',$role->id) }}" class="d-flex flex-column flex-lg-row gap-2 align-items-start align-items-lg-center">
                      @csrf @method('put')
                      <select multiple size="6" name="permissions[]" class="form-select" style="min-width: 280px;">
                        @foreach($perms as $p)
                          <option value="{{ $p->name }}" @selected($role->hasPermissionTo($p->name))>{{ $p->name }}</option>
                        @endforeach
                      </select>
                      <button class="btn btn-sm btn-primary">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                      </button>
                    </form>
                    <small class="text-muted d-block mt-1">
                      <i class="icon-base ti tabler-info-circle me-1"></i>
                      اضغط Ctrl/Cmd لتحديد عدة صلاحيات
                    </small>
                  </td>
                  <td class="text-center">
                    <a class="btn btn-sm btn-outline-info" href="{{ route('admin.users.index') }}?role={{ $role->name }}">
                      <i class="icon-base ti tabler-users me-1"></i> عرض
                    </a>
                  </td>
                  <td class="text-center">
                    @if($role->name !== 'ADMIN')
                      <form method="post" action="{{ route('admin.roles.destroy',$role->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟');">
                        @csrf @method('delete')
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                          <i class="icon-base ti tabler-trash me-1"></i> حذف
                        </button>
                      </form>
                    @else
                      <span class="badge bg-label-secondary">
                        <i class="icon-base ti tabler-lock me-1"></i> محمي
                      </span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                      <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                        <i class="icon-base ti tabler-shield" style="font-size: 32px;"></i>
                      </span>
                      <p class="text-muted mb-0">لا توجد أدوار</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
