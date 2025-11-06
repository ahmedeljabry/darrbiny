@extends('admin.layouts.app')
@section('title', 'تفاصيل طلب المحفظة')
@section('content')

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
                <h5 class="mb-0">تفاصيل طلب المحفظة</h5>
                <a href="{{ route('admin.wallet-transactions.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>المستخدم</h6>
                        <p class="mb-0">
                            <strong>{{ $transaction->user->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $transaction->user->phone_with_cc ?? '' }}</small><br>
                            <small class="text-muted">رصيد المحفظة الحالي: {{ number_format($transaction->user->points_balance ?? 0) }}</small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>تفاصيل الطلب</h6>
                        <p class="mb-0">
                            <strong>المبلغ:</strong> {{ number_format($transaction->amount) }}<br>
                            <strong>النوع:</strong> {{ $transaction->type === 'topup_request' ? 'طلب إضافة' : $transaction->type }}<br>
                            <strong>الحالة:</strong> 
                            @if($transaction->status === 'pending')
                                <span class="badge bg-label-warning">معلق</span>
                            @elseif($transaction->status === 'approved')
                                <span class="badge bg-label-success">موافق عليه</span>
                            @else
                                <span class="badge bg-label-danger">مرفوض</span>
                            @endif
                            <br>
                            <strong>تاريخ الطلب:</strong> {{ $transaction->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>

                @if($transaction->notes)
                    <div class="mb-4">
                        <h6>ملاحظات</h6>
                        <p>{{ $transaction->notes }}</p>
                    </div>
                @endif

                @if($transaction->status === 'rejected' && $transaction->rejection_reason)
                    <div class="mb-4">
                        <h6>سبب الرفض</h6>
                        <p class="text-danger">{{ $transaction->rejection_reason }}</p>
                    </div>
                @endif

                @if($transaction->processed_by)
                    <div class="mb-4">
                        <h6>المعالج</h6>
                        <p class="mb-0">
                            <strong>{{ $transaction->processedBy->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">تاريخ المعالجة: {{ $transaction->processed_at?->format('Y-m-d H:i') }}</small>
                        </p>
                    </div>
                @endif

                @if($transaction->status === 'pending')
                    <hr>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.wallet-transactions.approve', $transaction->id) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من الموافقة على هذا الطلب؟')">
                                <i class="icon-base ti tabler-check me-1"></i> الموافقة
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="icon-base ti tabler-x me-1"></i> رفض
                        </button>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.wallet-transactions.reject', $transaction->id) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">رفض الطلب</h5>
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
                                        <button type="submit" class="btn btn-danger">رفض الطلب</button>
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

