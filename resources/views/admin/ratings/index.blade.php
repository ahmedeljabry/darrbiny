@extends('admin.layouts.app')
@section('title','التقييمات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">التقييمات</li>
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
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-star"></i>
      </span>
      <div>
        <h5 class="mb-0">التقييمات</h5>
        <small class="text-body-secondary">إدارة تقييمات المستخدمين والمدربين</small>
      </div>
    </div>
    <a href="{{ route('admin.ratings.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> إضافة تقييم
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>المستخدم</th>
          <th>المدرب</th>
          <th>الطلب</th>
          <th>النجوم</th>
          <th>الملاحظة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        @forelse($ratings as $r)
          <tr>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($r->user)->name ?? 'غير معروف' }}</span>
                <small class="text-muted">{{ $r->user_id }}</small>
              </div>
            </td>
            <td>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ optional($r->trainer)->name ?? 'غير معروف' }}</span>
                <small class="text-muted">{{ $r->trainer_id }}</small>
              </div>
            </td>
            <td>
              <a href="{{ route('admin.bookings.show', $r->user_request_id) }}" class="text-primary">
                #{{ substr($r->user_request_id, 0, 8) }}
              </a>
            </td>
            <td>
              <div class="d-flex align-items-center gap-1">
                @for($i = 1; $i <= 5; $i++)
                  <i class="icon-base ti tabler-star{{ $i <= $r->stars ? '-filled' : '' }} text-warning"></i>
                @endfor
                <span class="ms-1 fw-semibold">{{ $r->stars }}</span>
              </div>
            </td>
            <td>
              <div class="text-break" style="max-width: 300px;">
                {{ $r->comment ?? '—' }}
              </div>
              <small class="text-muted d-block mt-1">{{ $r->created_at->format('Y-m-d H:i') }}</small>
            </td>
            <td>
              <div class="d-flex gap-1">
                <a href="{{ route('admin.ratings.edit', $r) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                  <i class="icon-base ti tabler-edit"></i>
                </a>
                <form method="post" action="{{ route('admin.ratings.destroy', $r) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم؟');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger" type="submit" title="حذف">
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
                  <i class="icon-base ti tabler-star" style="font-size: 32px;"></i>
                </span>
                <p class="text-muted mb-0">لا توجد تقييمات</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($ratings->hasPages())
    <div class="card-footer">{{ $ratings->links() }}</div>
  @endif
</div>
@endsection
