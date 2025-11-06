@extends('admin.layouts.app')
@section('title', 'طلبات الإلغاء')
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
                <h5 class="mb-0">طلبات إلغاء الدورات</h5>
                <form method="get" class="d-flex align-items-end gap-2">
                    <div>
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
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
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>رقم الدورة</th>
                            <th>سبب الإلغاء</th>
                            <th>الحالة</th>
                            <th>تاريخ الطلب</th>
                            <th>إجراءات</th>
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
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.cancellation-requests.show', $req->id) }}">
                                                <i class="icon-base ti tabler-eye me-1"></i> عرض التفاصيل
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">لا توجد طلبات إلغاء</p>
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


