@extends('admin.layouts.app')
@section('title', 'تفاصيل طلب الإلغاء')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.cancellation-requests.index') }}">طلبات الإلغاء</a></li>
    <li class="breadcrumb-item active" aria-current="page">تفاصيل طلب الإلغاء</li>
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تفاصيل طلب الإلغاء</h5>
                <a href="{{ route('admin.cancellation-requests.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات المستخدم</h6>
                        <p class="mb-1"><strong>الاسم:</strong> {{ $cancellation->user->name ?? 'غير معروف' }}</p>
                        <p class="mb-1"><strong>الهاتف:</strong> {{ $cancellation->user->phone_with_cc ?? '-' }}</p>
                        <p class="mb-1"><strong>البريد الإلكتروني:</strong> {{ $cancellation->user->email ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات الدورة</h6>
                        <p class="mb-1"><strong>رقم الدورة:</strong> #{{ $cancellation->userRequest->id }}</p>
                        <p class="mb-1"><strong>الخطة:</strong> {{ $cancellation->userRequest->plan->title ?? '-' }}</p>
                        <p class="mb-1"><strong>تاريخ البدء:</strong> {{ $cancellation->userRequest->start_date ? $cancellation->userRequest->start_date->format('Y-m-d') : '-' }}</p>
                        <p class="mb-1"><strong>الحالة الحالية:</strong> 
                            <span class="badge bg-label-{{ $cancellation->userRequest->status === 'cancelled' ? 'danger' : 'warning' }}">
                                {{ $cancellation->userRequest->status }}
                            </span>
                        </p>
                    </div>
                    <div class="col-12">
                        <h6 class="text-muted mb-2">سبب الإلغاء</h6>
                        <div class="card border">
                            <div class="card-body">
                                <p class="mb-0">{{ $cancellation->reason }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">معلومات الطلب</h6>
                        <p class="mb-1"><strong>الحالة:</strong> 
                            <span class="badge bg-label-{{ $cancellation->status === 'approved' ? 'success' : ($cancellation->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $cancellation->status === 'pending' ? 'قيد الانتظار' : ($cancellation->status === 'approved' ? 'مقبولة' : 'مرفوضة') }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>تاريخ الطلب:</strong> {{ $cancellation->created_at->format('Y-m-d H:i') }}</p>
                        @if($cancellation->processed_at)
                            <p class="mb-1"><strong>تاريخ المعالجة:</strong> {{ $cancellation->processed_at->format('Y-m-d H:i') }}</p>
                            <p class="mb-1"><strong>معالج بواسطة:</strong> {{ $cancellation->processedBy->name ?? '-' }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">المعلومات المالية</h6>
                        @php
                            $userRequest = $cancellation->userRequest;
                            $fullPayment = $userRequest->latestSuccessfulFullPayment();
                            $partialPayment = $userRequest->latestSuccessfulPartialPayment();
                            $successfulPaymentsMinor = $userRequest->totalSuccessfulPaymentsMinor();
                            $refundAmountMinor = (int) ($cancellation->refund_amount_minor ?? $successfulPaymentsMinor);
                        @endphp
                        @if($fullPayment)
                            <p class="mb-1"><strong>قيمة الباقة:</strong> {{ number_format($fullPayment->amount_minor / 100, 2) }} {{ $userRequest->currency }}</p>
                            <p class="mb-1"><strong>رسوم التطبيق:</strong> {{ number_format($fullPayment->app_fee_minor / 100, 2) }} {{ $userRequest->currency }}</p>
                        @endif
                        @if($partialPayment)
                            <p class="mb-1"><strong>{{ $partialPayment->typeLabel() }}:</strong> {{ number_format($partialPayment->amount_minor / 100, 2) }} {{ $userRequest->currency }}</p>
                        @endif
                        <p class="mb-1"><strong>إجمالي الدفعات الناجحة:</strong> {{ number_format($successfulPaymentsMinor / 100, 2) }} {{ $userRequest->currency }}</p>
                        <p class="mb-1"><strong>المبلغ المراد إرجاعه:</strong> {{ number_format($refundAmountMinor / 100, 2) }} {{ $userRequest->currency }}</p>
                    </div>
                    @if($cancellation->admin_notes)
                    <div class="col-12">
                        <h6 class="text-muted mb-2">ملاحظات الإدارة</h6>
                        <div class="card border">
                            <div class="card-body">
                                <p class="mb-0">{{ $cancellation->admin_notes }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($cancellation->status === 'pending')
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-header">
                                <h6 class="mb-0">معالجة الطلب</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <form method="POST" action="{{ route('admin.cancellation-requests.approve', $cancellation->id) }}" class="d-inline">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">ملاحظات (اختياري)</label>
                                                <textarea name="admin_notes" class="form-control" rows="2" placeholder="أضف ملاحظات حول الموافقة"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="icon-base ti tabler-check me-1"></i> قبول الطلب وإرجاع المبلغ
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="POST" action="{{ route('admin.cancellation-requests.reject', $cancellation->id) }}" class="d-inline">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                                                <textarea name="admin_notes" class="form-control" rows="2" placeholder="اكتب سبب رفض الطلب" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="icon-base ti tabler-x me-1"></i> رفض الطلب
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection


