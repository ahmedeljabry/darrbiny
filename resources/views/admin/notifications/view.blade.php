@extends('admin.layouts.app')
@section('title', 'الإشعارات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.notifications.index') }}">الإشعارات</a></li>
    <li class="breadcrumb-item active" aria-current="page">عرض الإشعار</li>
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
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">الإشعارات</h5>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">النوع</label>
                            <select name="type" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="SupportTicketCreated" @selected(request('type') == 'SupportTicketCreated')>تذاكر الدعم</option>
                                <option value="PrizeRequest" @selected(request('type') == 'PrizeRequest')>طلبات الجوائز</option>
                                <option value="WalletTopupRequest" @selected(request('type') == 'WalletTopupRequest')>طلبات المحفظة</option>
                                <option value="CancellationRequest" @selected(request('type') == 'CancellationRequest')>طلبات الإلغاء</option>
                                <option value="UserAccountDeleted" @selected(request('type') == 'UserAccountDeleted')>حذف الحسابات</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="read" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="unread" @selected(request('read') == 'unread')>غير مقروء</option>
                                <option value="read" @selected(request('read') == 'read')>مقروء</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button class="btn btn-outline-secondary">تصفية</button>
                            <a href="{{ route('admin.notifications.view') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                    @if(auth()->user()->unreadNotifications()->count() > 0)
                        <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="icon-base ti tabler-check me-1"></i> تحديد الكل كمقروء
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;"></th>
                                <th>الإشعار</th>
                                <th>النوع</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $notification)
                                <tr class="{{ $notification->read_at ? '' : 'table-active' }}">
                                    <td>
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-{{ $notification->read_at ? 'secondary' : 'primary' }}">
                                                <i class="icon-base ti tabler-{{ $notification->data['type'] === 'support_ticket_created' ? 'ticket' : ($notification->data['type'] === 'prize_request' ? 'gift' : ($notification->data['type'] === 'wallet_topup_request' ? 'wallet' : ($notification->data['type'] === 'cancellation_request' ? 'x' : ($notification->data['type'] === 'user_account_deleted' ? 'user' : 'bell')))) }}"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-1">{{ $notification->data['title'] ?? $notification->data['message'] ?? 'إشعار جديد' }}</h6>
                                            <p class="mb-0 text-muted">{{ Str::limit($notification->data['message'] ?? $notification->data['title'] ?? '', 100) }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $typeMap = [
                                                'support_ticket_created' => 'تذكرة دعم',
                                                'prize_request' => 'طلب جائزة',
                                                'wallet_topup_request' => 'طلب محفظة',
                                                'cancellation_request' => 'طلب إلغاء',
                                                'user_account_deleted' => 'حذف حساب',
                                            ];
                                            $typeName = $typeMap[$notification->data['type'] ?? ''] ?? 'عام';
                                        @endphp
                                        <span class="badge bg-label-info">{{ $typeName }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $notification->created_at->format('Y-m-d H:i') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($notification->read_at)
                                            <span class="badge bg-label-success">مقروء</span>
                                        @else
                                            <span class="badge bg-label-warning">غير مقروء</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$notification->read_at)
                                            <form action="{{ route('admin.notifications.mark-read', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="icon-base ti tabler-check"></i> تحديد كمقروء
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">لا توجد إشعارات</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $notifications->links() }}</div>
        </div>
    </div>
</div>

@endsection

