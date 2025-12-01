@extends('admin.layouts.app')
@section('title', 'الجوائز')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الجوائز</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="icon-base ti tabler-trophy"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">الجوائز</h5>
                    <small class="text-body-secondary">إدارة جوائز المستخدمين</small>
                  </div>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="اسم الجائزة">
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="active" @selected(request('status') == 'active')>نشط</option>
                                <option value="inactive" @selected(request('status') == 'inactive')>غير نشط</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button class="btn btn-outline-secondary">تصفية</button>
                            <a href="{{ route('admin.prizes.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                    <a href="{{ route('admin.prizes.create') }}" class="btn btn-primary">
                        <i class="icon-base ti tabler-plus"></i> إضافة جائزة
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-photo me-1"></i> الصورة</th>
                            <th><i class="icon-base ti tabler-sort-ascending me-1"></i> الترتيب</th>
                            <th><i class="icon-base ti tabler-trophy me-1"></i> العنوان</th>
                            <th><i class="icon-base ti tabler-coins me-1"></i> النقاط المطلوبة</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prizes as $prize)
                            <tr>
                                <td>
                                    @if($prize->image)
                                        <img src="{{ Storage::disk(config('filesystems.default', 'public'))->url($prize->image) }}" alt="{{ $prize->title }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $prize->order }}</td>
                                <td><strong>{{ $prize->title }}</strong></td>
                                <td>{{ number_format($prize->required_points) }}</td>
                                <td>
                                    @if($prize->active)
                                        <span class="badge bg-label-success">نشط</span>
                                    @else
                                        <span class="badge bg-label-secondary">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('admin.prizes.show', $prize->id) }}" class="btn btn-sm btn-outline-info" title="عرض">
                                            <i class="icon-base ti tabler-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.prizes.edit', $prize->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                            <i class="icon-base ti tabler-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.prizes.destroy', $prize->id) }}" method="POST" data-confirm="delete" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                <i class="icon-base ti tabler-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                  <div class="d-flex flex-column align-items-center">
                                    <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                                      <i class="icon-base ti tabler-trophy" style="font-size: 32px;"></i>
                                    </span>
                                    <p class="text-muted mb-0">لا توجد جوائز</p>
                                  </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $prizes->links() }}</div>
        </div>
    </div>
</div>

@endsection

