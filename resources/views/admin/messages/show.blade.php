@extends('admin.layouts.app')
@section('title', 'تفاصيل المحادثة')
@section('content')

<div class="row g-6">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تفاصيل المحادثة</h5>
                <a href="{{ route('admin.messages.index') }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>المستخدم الأول</h6>
                        <p class="mb-0">
                            <strong>{{ $conversation->userOne->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $conversation->userOne->phone_with_cc ?? '' }}</small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>المستخدم الثاني</h6>
                        <p class="mb-0">
                            <strong>{{ $conversation->userTwo->name ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $conversation->userTwo->phone_with_cc ?? '' }}</small>
                        </p>
                    </div>
                </div>

                <hr>

                <h6 class="mb-3">الرسائل</h6>
                <div class="messages-container" style="max-height: 600px; overflow-y: auto;">
                    @forelse($messages as $message)
                        <div class="card mb-3 {{ $message->sender_id === $conversation->user_one_id ? 'border-primary' : 'border-info' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $message->sender->name ?? 'N/A' }}</strong>
                                        <small class="text-muted ms-2">{{ $message->sender->phone_with_cc ?? '' }}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">{{ $message->created_at->format('Y-m-d H:i') }}</small>
                                        @if($message->is_read)
                                            <span class="badge bg-label-success ms-2">مقروء</span>
                                        @else
                                            <span class="badge bg-label-warning ms-2">غير مقروء</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-0">{{ $message->message }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">لا توجد رسائل</p>
                    @endforelse
                </div>

                <div class="mt-3">{{ $messages->links() }}</div>
            </div>
        </div>
    </div>
</div>

@endsection

