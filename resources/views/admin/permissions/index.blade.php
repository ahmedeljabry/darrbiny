@extends('admin.layouts.app')
@section('title','الصلاحيات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">الأدوار والصلاحيات</a></li>
    <li class="breadcrumb-item active" aria-current="page">الصلاحيات</li>
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
          <i class="icon-base ti tabler-key"></i>
        </span>
        <div>
          <h5 class="mb-1">إنشاء صلاحية جديدة</h5>
          <small class="text-muted">أضف صلاحية جديدة للنظام</small>
        </div>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.permissions.store') }}">@csrf
          <div class="mb-3">
            <label class="form-label fw-semibold">اسم الصلاحية</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-key"></i></span>
              <input class="form-control" name="name" placeholder="مثال: manage_content" required>
            </div>
            <small class="text-muted d-block mt-1">استخدم أسماء بصيغة snake_case</small>
          </div>
          <button class="btn btn-primary w-100">
            <i class="icon-base ti tabler-plus me-1"></i> إنشاء الصلاحية
          </button>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center gap-2">
        <span class="avatar-initial rounded bg-label-success">
          <i class="icon-base ti tabler-key"></i>
        </span>
        <div>
          <h5 class="mb-1">جميع الصلاحيات</h5>
          <small class="text-muted">إدارة صلاحيات النظام</small>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><i class="icon-base ti tabler-key me-1"></i> اسم الصلاحية</th>
                <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الإنشاء</th>
                <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> الإجراء</th>
              </tr>
            </thead>
            <tbody>
              @forelse($perms as $p)
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-label-primary">
                        <i class="icon-base ti tabler-key me-1"></i>
                        {{ $p->name }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <small class="text-muted">{{ $p->created_at?->format('Y-m-d') ?? '—' }}</small>
                  </td>
                  <td class="text-center">
                    <form method="post" action="{{ route('admin.permissions.destroy',$p->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه الصلاحية؟');">
                      @csrf @method('delete')
                      <button class="btn btn-sm btn-outline-danger" type="submit">
                        <i class="icon-base ti tabler-trash me-1"></i> حذف
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                      <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                        <i class="icon-base ti tabler-key" style="font-size: 32px;"></i>
                      </span>
                      <p class="text-muted mb-0">لا توجد صلاحيات</p>
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

