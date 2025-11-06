@extends('admin.layouts.app')
@section('title', 'الجوائز')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">الجوائز</h5>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="اسم الجائزة">
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
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
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>الترتيب</th>
                            <th>العنوان</th>
                            <th>النقاط المطلوبة</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
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
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.prizes.show', $prize->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="icon-base ti tabler-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.prizes.edit', $prize->id) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="icon-base ti tabler-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.prizes.destroy', $prize->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الجائزة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="icon-base ti tabler-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد جوائز</td>
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

