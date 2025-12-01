@extends('admin.layouts.app')
@section('title', 'طلبات الإلغاء')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">طلبات الإلغاء</li>
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
                    <i class="icon-base ti tabler-x-circle"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">طلبات إلغاء الدورات</h5>
                    <small class="text-body-secondary">إدارة طلبات إلغاء الحجوزات</small>
                  </div>
                </div>
                <form method="get" class="d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select select2">
                            <option value="">الكل</option>
                            <option value="pending" @selected($status === 'pending')>قيد الانتظار</option>
                            <option value="approved" @selected($status === 'approved')>مقبولة</option>
                            <option value="rejected" @selected($status === 'rejected')>مرفوضة</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 align-items-end">
                        <button class="btn btn-outline-secondary">تصفية</button>
                        <a href="{{ route('admin.cancellation-requests.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
                            <th><i class="icon-base ti tabler-file-text me-1"></i> رقم الدورة</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> سبب الإلغاء</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الطلب</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $req->user->name ?? 'غير معروف' }}</span>
                                        <small class="text-muted">{{ $req->user->phone_with_cc ?? '-' }}</small>
                                    </div>
                                </td>
                                <td>#{{ $req->userRequest->id }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $req->reason }}">
                                        {{ Str::limit($req->reason, 50) }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'قيد الانتظار',
                                            'approved' => 'مقبولة',
                                            'rejected' => 'مرفوضة',
                                        ];
                                        $color = $statusColors[$req->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-label-{{ $color }}">{{ $statusLabels[$req->status] ?? $req->status }}</span>
                                </td>
                                <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.cancellation-requests.show', $req->id) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                                            <i class="icon-base ti tabler-x-circle" style="font-size: 32px;"></i>
                                        </span>
                                        <p class="text-muted mb-0">لا توجد طلبات إلغاء</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $requests->links() }}</div>
        </div>
    </div>
</div>

@endsection


