@extends('admin.layouts.app')
@section('title', 'تفاصيل طلب السحب')
@section('content')

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.withdrawal-requests.index') }}">طلبات السحب</a></li>
    <li class="breadcrumb-item active" aria-current="page">تفاصيل طلب السحب</li>
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
                <h5 class="mb-0">تفاصيل طلب السحب</h5>
                <a href="{{ route('admin.withdrawal-requests.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>بيانات المستخدم</h6>
                        <p class="mb-0">
                            <strong>{{ $withdrawalRequest->user?->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $withdrawalRequest->user?->phone_with_cc ?? '' }}</small><br>
                            <small class="text-muted">الاسم الحقيقي: {{ $withdrawalRequest->user?->bank_account_name ?? '-' }}</small><br>
                            <small class="text-muted">الدولة: {{ $withdrawalRequest->user?->country?->name ?? $withdrawalRequest->user?->bankCountry?->name ?? '-' }}</small><br>
                            @php
                                $isTrainer = ($withdrawalRequest->user?->user_type?->value ?? null) === 'captain';
                            @endphp
                            <small class="text-muted">نوع الحساب: {{ $isTrainer ? 'مدرب' : 'طالب' }}</small><br>
                            <small class="text-muted">رصيد المحفظة الحالي: {{ number_format($withdrawalRequest->user?->points_balance ?? 0, 2) }}</small><br>
                            <small class="text-muted">اسم البنك: {{ $withdrawalRequest->user?->bank_name ?? '-' }}</small><br>
                            <small class="text-muted">رقم الحساب: <span dir="ltr">{{ $withdrawalRequest->user?->bank_account ?? '-' }}</span></small><br>
                            <small class="text-muted">IBAN: <span dir="ltr">{{ $withdrawalRequest->user?->iban ?? '-' }}</span></small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>بيانات الطلب</h6>
                        <p class="mb-0">
                            <strong>المبلغ المطلوب:</strong> {{ number_format($withdrawalRequest->amountMajor(), 2) }}<br>
                            <strong>النوع:</strong> طلب سحب<br>
                            <strong>الحالة:</strong>
                            @if($withdrawalRequest->status === 'pending')
                                <span class="badge bg-label-warning">معلق</span>
                            @elseif($withdrawalRequest->status === 'approved')
                                <span class="badge bg-label-success">منفذ</span>
                            @else
                                <span class="badge bg-label-danger">مرفوض</span>
                            @endif
                            <br>
                            <strong>تاريخ الطلب:</strong> {{ $withdrawalRequest->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>

                @if($withdrawalRequest->notes)
                    <div class="mb-4">
                        <h6>ملاحظات المستخدم</h6>
                        <p>{{ $withdrawalRequest->notes }}</p>
                    </div>
                @endif

                @if($withdrawalRequest->status === 'rejected' && $withdrawalRequest->rejection_reason)
                    <div class="mb-4">
                        <h6>سبب الرفض</h6>
                        <p class="text-danger">{{ $withdrawalRequest->rejection_reason }}</p>
                    </div>
                @endif

                @if($withdrawalRequest->processed_by)
                    <div class="mb-4">
                        <h6>المعالج</h6>
                        <p class="mb-0">
                            <strong>{{ $withdrawalRequest->processedBy->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">تاريخ المعالجة: {{ $withdrawalRequest->processed_at?->format('Y-m-d H:i') }}</small>
                        </p>
                    </div>
                @endif

                @if($withdrawalRequest->status === 'pending')
                    <hr>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.withdrawal-requests.approve', $withdrawalRequest->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من تنفيذ طلب السحب؟ سيتم خصم الرصيد من المحفظة.')">
                                <i class="icon-base ti tabler-check me-1"></i> تنفيذ الطلب
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="icon-base ti tabler-x me-1"></i> رفض
                        </button>
                    </div>

                    <div class="modal fade" id="rejectModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.withdrawal-requests.reject', $withdrawalRequest->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">رفض طلب السحب</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">سبب الرفض</label>
                                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
