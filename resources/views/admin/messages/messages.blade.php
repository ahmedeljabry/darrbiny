@extends('admin.layouts.app')
@section('title', 'جميع الرسائل')
@section('content')

<div class="row g-6">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">جميع الرسائل</h5>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="ابحث في محتوى الرسائل">
                        </div>
                        <div>
                            <label class="form-label">المستخدم (مشارك)</label>
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
                            <label class="form-label">المرسل</label>
                            <select name="sender_id" class="form-select select2" style="min-width:180px">
                                <option value="">الكل</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('sender_id') == $user->id)>
                                        {{ $user->name }} ({{ $user->phone_with_cc }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">غير مقروءة</label>
                            <select name="unread" class="form-select">
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
                            <a href="{{ route('admin.messages.messages') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                    <a class="btn btn-outline-primary" href="{{ route('admin.messages.index') }}">عرض المحادثات</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>المرسل</th>
                            <th>المحادثة</th>
                            <th>الرسالة</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td>
                                    {{ $message->sender->name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $message->sender->phone_with_cc ?? '' }}</small>
                                </td>
                                <td>
                                    @if($message->conversation)
                                        <small>
                                            {{ $message->conversation->userOne->name ?? 'N/A' }} ↔ 
                                            {{ $message->conversation->userTwo->name ?? 'N/A' }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 300px;">
                                        {{ Str::limit($message->message, 100) }}
                                    </div>
                                </td>
                                <td>
                                    @if($message->is_read)
                                        <span class="badge bg-label-success">مقروء</span>
                                    @else
                                        <span class="badge bg-label-warning">غير مقروء</span>
                                    @endif
                                </td>
                                <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($message->conversation)
                                        <a href="{{ route('admin.messages.show', $message->conversation->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="icon-base ti tabler-eye"></i> عرض المحادثة
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد رسائل</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $messages->links() }}</div>
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

