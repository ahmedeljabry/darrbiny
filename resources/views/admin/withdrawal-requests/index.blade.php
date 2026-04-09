@extends('admin.layouts.app')
@section('title', 'طلبات السحب')
@section('content')

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">طلبات السحب</li>
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
                  <span class="avatar-initial rounded bg-label-danger" style="width: 48px; height: 48px;">
                    <i class="icon-base ti tabler-arrow-up-right-circle" style="font-size: 24px;"></i>
                  </span>
                  <div>
                    <h5 class="mb-0 fw-bold">طلبات السحب</h5>
                    <small class="text-muted">عرض وتنفيذ طلبات السحب للطالب والمدرب</small>
                  </div>
                </div>
                <a href="{{ route('admin.withdrawal-requests.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
                    <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
                </a>
            </div>
            <div class="card-body pt-0">
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">بحث</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="اسم المستخدم أو رقم الهاتف">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">المستخدم</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                    {{ $user->name }} ({{ $user->phone_with_cc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="pending" @selected(request('status') == 'pending')>معلق</option>
                            <option value="approved" @selected(request('status') == 'approved')>منفذ</option>
                            <option value="rejected" @selected(request('status') == 'rejected')>مرفوض</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">من تاريخ</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">إلى تاريخ</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" type="submit">
                            <i class="icon-base ti tabler-filter"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 260px;">المستخدم</th>
                            <th style="width: 130px;">نوع الحساب</th>
                            <th style="width: 160px;">اسم البنك</th>
                            <th style="width: 170px;">رقم الحساب</th>
                            <th style="width: 200px;">IBAN</th>
                            <th style="width: 120px;">المبلغ</th>
                            <th style="width: 120px;">الحالة</th>
                            <th style="width: 160px;">تاريخ الطلب</th>
                            <th style="width: 100px;" class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $withdrawal)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                                            {{ substr($withdrawal->user?->name ?? 'U', 0, 1) }}
                                        </span>
                                        <div>
                                            <div class="fw-semibold">{{ $withdrawal->user?->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $withdrawal->user?->phone_with_cc ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $isTrainer = ($withdrawal->user?->user_type?->value ?? null) === 'captain';
                                    @endphp
                                    <span class="badge bg-label-{{ $isTrainer ? 'info' : 'secondary' }}">
                                        {{ $isTrainer ? 'مدرب' : 'طالب' }}
                                    </span>
                                </td>
                                <td>{{ $withdrawal->user?->bank_name ?? '-' }}</td>
                                <td><span dir="ltr">{{ $withdrawal->user?->bank_account ?? '-' }}</span></td>
                                <td><span dir="ltr">{{ $withdrawal->user?->iban ?? '-' }}</span></td>
                                <td>
                                    <span class="fw-semibold">{{ number_format($withdrawal->amountMajor(), 2) }} نقطة</span>
                                </td>
                                <td>
                                    @if($withdrawal->status === 'pending')
                                        <span class="badge bg-label-warning">معلق</span>
                                    @elseif($withdrawal->status === 'approved')
                                        <span class="badge bg-label-success">منفذ</span>
                                    @else
                                        <span class="badge bg-label-danger">مرفوض</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $withdrawal->created_at->format('Y-m-d') }}</span>
                                        <small class="text-muted">{{ $withdrawal->created_at->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.withdrawal-requests.show', $withdrawal->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="عرض التفاصيل">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                                            <i class="icon-base ti tabler-arrow-up-right-circle" style="font-size: 32px;"></i>
                                        </span>
                                        <p class="text-muted mb-0">لا توجد طلبات سحب</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="card-footer border-top">
                    {{ $requests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
