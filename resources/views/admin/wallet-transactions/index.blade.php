@extends('admin.layouts.app')
@section('title', 'طلبات المحفظة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">طلبات المحفظة</li>
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
                    <i class="icon-base ti tabler-wallet"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">طلبات المحفظة</h5>
                    <small class="text-body-secondary">إدارة طلبات إضافة الرصيد</small>
                  </div>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="اسم المستخدم أو رقم الهاتف">
                        </div>
                        <div>
                            <label class="form-label">المستخدم</label>
                            <select name="user_id" class="form-select select2" style="min-width:180px">
                                <option value="">الكل</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                        {{ $user->name }} ({{ $user->phone_with_cc }})
                                    </option>
                                @endforeach
                            </select>
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
                        <div>
                            <label class="form-label">من تاريخ</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button class="btn btn-outline-secondary">تصفية</button>
                            <a href="{{ route('admin.wallet-transactions.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                            <a href="{{ route('admin.wallet-transactions.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success">
                                <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
                            <th><i class="icon-base ti tabler-currency-dollar me-1"></i> المبلغ</th>
                            <th><i class="icon-base ti tabler-tag me-1"></i> النوع</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ الطلب</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>
                                    {{ $transaction->user->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $transaction->user->phone_with_cc ?? '' }}</small>
                                </td>
                                <td><strong>{{ number_format($transaction->amount) }}</strong></td>
                                <td>
                                    @if($transaction->type === 'topup_request')
                                        <span class="badge bg-label-info">طلب إضافة</span>
                                    @else
                                        <span class="badge bg-label-secondary">{{ $transaction->type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->status === 'pending')
                                        <span class="badge bg-label-warning">معلق</span>
                                    @elseif($transaction->status === 'approved')
                                        <span class="badge bg-label-success">موافق عليه</span>
                                    @else
                                        <span class="badge bg-label-danger">مرفوض</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.wallet-transactions.show', $transaction->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="icon-base ti tabler-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد طلبات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $transactions->links() }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $.fn.select2) {
            const dir = @json(app()->getLocale() === 'en' ? 'ltr' : 'rtl');
            $('.select2').select2({ dir: dir, width: '100%' });
        }
    });
</script>
@endpush

@endsection

