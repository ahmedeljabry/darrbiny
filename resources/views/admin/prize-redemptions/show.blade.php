@extends('admin.layouts.app')
@section('title', 'تفاصيل طلب الجائزة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.prize-redemptions.index') }}">طلبات الجوائز</a></li>
    <li class="breadcrumb-item active" aria-current="page">تفاصيل طلب الجائزة</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger alert-dismissible" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تفاصيل طلب الجائزة</h5>
                <a href="{{ route('admin.prize-redemptions.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>المستخدم</h6>
                        <p class="mb-0">
                            <strong>{{ $redemption->user->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $redemption->user->phone_with_cc ?? '' }}</small><br>
                            <small class="text-muted">رصيد النقاط الحالي: {{ number_format($redemption->user->points_balance ?? 0, 2) }}</small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>تفاصيل الطلب</h6>
                        <p class="mb-0">
                            <strong>الجائزة:</strong> {{ $redemption->reward->title ?? 'N/A' }}<br>
                            <strong>النقاط المستخدمة:</strong> {{ number_format($redemption->points_spent) }}<br>
                            <strong>الحالة:</strong> 
                            @if($redemption->status === 'pending')
                                <span class="badge bg-label-warning">معلق</span>
                            @elseif($redemption->status === 'approved')
                                <span class="badge bg-label-success">موافق عليه</span>
                            @else
                                <span class="badge bg-label-danger">مرفوض</span>
                            @endif
                            <br>
                            <strong>تاريخ الطلب:</strong> {{ $redemption->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>

                @if($redemption->status === 'rejected' && $redemption->rejection_reason)
                    <div class="mb-4">
                        <h6>سبب الرفض</h6>
                        <p class="text-danger">{{ $redemption->rejection_reason }}</p>
                    </div>
                @endif

                @if($redemption->status === 'pending')
                    <div class="d-flex gap-2">
                        <form action="{{ route('admin.prize-redemptions.approve', $redemption->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الموافقة على هذا الطلب؟ سيتم خصم النقاط من حساب المستخدم.')">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="icon-base ti tabler-check me-1"></i> الموافقة
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="icon-base ti tabler-x me-1"></i> الرفض
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.prize-redemptions.reject', $redemption->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">رفض طلب الجائزة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="أدخل سبب رفض الطلب"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">رفض الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

