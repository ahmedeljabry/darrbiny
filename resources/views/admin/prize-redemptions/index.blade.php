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
            <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3">
                <div class="d-flex align-items-center gap-3">
                  <span class="avatar-initial rounded bg-label-warning" style="width: 48px; height: 48px;">
                    <i class="icon-base ti tabler-gift" style="font-size: 24px;"></i>
                  </span>
                  <div>
                    <h5 class="mb-0 fw-bold">طلبات الجوائز</h5>
                    <small class="text-muted">إدارة طلبات استبدال النقاط</small>
                  </div>
                </div>
                <a href="{{ route('admin.prize-redemptions.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
                    <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
                </a>
            </div>
            <div class="card-body pt-0">
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">بحث</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="اسم المستخدم أو رقم الهاتف">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="pending" @selected(request('status') == 'pending')>معلق</option>
                            <option value="approved" @selected(request('status') == 'approved')>موافق عليه</option>
                            <option value="rejected" @selected(request('status') == 'rejected')>مرفوض</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            <i class="icon-base ti tabler-filter me-1"></i> تصفية
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('admin.prize-redemptions.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="icon-base ti tabler-refresh me-1"></i> إعادة تعيين
                        </a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;"><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
                            <th style="width: 250px;"><i class="icon-base ti tabler-trophy me-1"></i> الجائزة</th>
                            <th style="width: 130px;"><i class="icon-base ti tabler-coins me-1"></i> النقاط المستخدمة</th>
                            <th style="width: 130px;"><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th style="width: 150px;"><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الطلب</th>
                            <th style="width: 100px;" class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redemptions as $redemption)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($redemption->user?->profile_picture_url)
                                            <img src="{{ $redemption->user->profile_picture_url }}" alt="{{ $redemption->user->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                            <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                                                {{ substr($redemption->user->name ?? 'U', 0, 1) }}
                                            </span>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $redemption->user->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $redemption->user->phone_with_cc ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $redemption->reward->title ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-warning fw-semibold">{{ number_format($redemption->points_spent) }} نقطة</span>
                                </td>
                                <td>
                                    @if($redemption->status === 'pending')
                                        <span class="badge bg-label-warning">معلق</span>
                                    @elseif($redemption->status === 'approved')
                                        <span class="badge bg-label-success">موافق عليه</span>
                                    @else
                                        <span class="badge bg-label-danger">مرفوض</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $redemption->created_at->format('Y-m-d') }}</span>
                                        <small class="text-muted">{{ $redemption->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.prize-redemptions.show', $redemption->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="عرض التفاصيل">
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
            @if($redemptions->hasPages())
                <div class="card-footer border-top">
                    {{ $redemptions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

