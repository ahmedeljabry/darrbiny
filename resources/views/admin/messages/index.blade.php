@extends('admin.layouts.app')
@section('title', 'المحادثات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">المحادثات</li>
  </ol>
</nav>

<div class="row g-6">
    <div class="col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="icon-base ti tabler-message-circle"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">قائمة المحادثات</h5>
                    <small class="text-body-secondary">إدارة محادثات المستخدمين</small>
                  </div>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="ابحث في الرسائل">
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
                            <label class="form-label">غير مقروءة</label>
                            <select name="unread" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="1" @selected(request('unread') == '1')>غير مقروءة فقط</option>
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
                            <a href="{{ route('admin.messages.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                    <a class="btn btn-primary" href="{{ route('admin.messages.messages') }}">عرض جميع الرسائل</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم الأول</th>
                            <th><i class="icon-base ti tabler-user me-1"></i> المستخدم الثاني</th>
                            <th><i class="icon-base ti tabler-message me-1"></i> آخر رسالة</th>
                            <th><i class="icon-base ti tabler-bell me-1"></i> غير مقروء</th>
                            <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ آخر رسالة</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversations as $conversation)
                            <tr>
                                <td>
                                    {{ $conversation->userOne->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $conversation->userOne->phone_with_cc ?? '' }}</small>
                                </td>
                                <td>
                                    {{ $conversation->userTwo->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $conversation->userTwo->phone_with_cc ?? '' }}</small>
                                </td>
                                <td>
                                    @if($conversation->messages->first())
                                        <div class="text-truncate" style="max-width: 300px;">
                                            {{ Str::limit($conversation->messages->first()->message, 50) }}
                                        </div>
                                    @else
                                        <span class="text-muted">لا توجد رسائل</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $unreadCount = $conversation->messages->where('is_read', false)->count();
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="badge bg-label-warning">{{ $unreadCount }}</span>
                                    @else
                                        <span class="badge bg-label-success">مقروء</span>
                                    @endif
                                </td>
                                <td>{{ $conversation->last_message_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.messages.show', $conversation->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="icon-base ti tabler-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد محادثات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $conversations->links() }}</div>
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

