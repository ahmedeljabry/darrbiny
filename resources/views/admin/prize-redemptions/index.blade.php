@extends('admin.layouts.app')
@section('title', 'طلبات الجوائز')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">طلبات الجوائز</li>
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
                    <i class="icon-base ti tabler-gift"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">طلبات الجوائز</h5>
                    <small class="text-body-secondary">إدارة طلبات استبدال النقاط</small>
                  </div>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="اسم المستخدم أو رقم الهاتف">
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="pending" @selected(request('status') == 'pending')>معلق</option>
                                <option value="approved" @selected(request('status') == 'approved')>موافق عليه</option>
                                <option value="rejected" @selected(request('status') == 'rejected')>مرفوض</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button class="btn btn-outline-secondary">تصفية</button>
                            <a href="{{ route('admin.prize-redemptions.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
                            <th><i class="icon-base ti tabler-trophy me-1"></i> الجائزة</th>
                            <th><i class="icon-base ti tabler-coins me-1"></i> النقاط المستخدمة</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الطلب</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redemptions as $redemption)
                            <tr>
                                <td>
                                    {{ $redemption->user->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $redemption->user->phone_with_cc ?? '' }}</small>
                                </td>
                                <td><strong>{{ $redemption->reward->title ?? 'N/A' }}</strong></td>
                                <td><strong>{{ number_format($redemption->points_spent) }}</strong></td>
                                <td>
                                    @if($redemption->status === 'pending')
                                        <span class="badge bg-label-warning">معلق</span>
                                    @elseif($redemption->status === 'approved')
                                        <span class="badge bg-label-success">موافق عليه</span>
                                    @else
                                        <span class="badge bg-label-danger">مرفوض</span>
                                    @endif
                                </td>
                                <td>{{ $redemption->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.prize-redemptions.show', $redemption->id) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                                            <i class="icon-base ti tabler-gift" style="font-size: 32px;"></i>
                                        </span>
                                        <p class="text-muted mb-0">لا توجد طلبات</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $redemptions->links() }}</div>
        </div>
    </div>
</div>

@endsection

