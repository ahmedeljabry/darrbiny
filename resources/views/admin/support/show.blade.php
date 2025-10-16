@extends('admin.layouts.app')
@section('title','تذكرة دعم')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0">{{ $ticket->subject }}</h5>
          <small class="text-body-secondary">المستخدم: {{ optional($ticket->user)->name ?? '—' }}</small>
        </div>
        <span class="badge bg-label-{{ $ticket->status === 'open' ? 'success' : ($ticket->status==='pending' ? 'warning' : 'secondary') }}">{{ $ticket->status }}</span>
      </div>
      <div class="card-body">
        <div class="d-flex flex-column gap-3">
          @forelse($ticket->messages as $msg)
            <div class="border rounded p-3 {{ $msg->author_type === 'admin' ? 'bg-white' : 'bg-white/50' }}">
              <div class="d-flex justify-content-between mb-1">
                <strong>{{ $msg->author_type === 'admin' ? 'الإدارة' : (optional($msg->user)->name ?? 'مستخدم') }}</strong>
                <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
              </div>
              <div>{{ $msg->message }}</div>
            </div>
          @empty
            <div class="text-muted">لا توجد رسائل بعد.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header"><h6 class="mb-0">إضافة رد</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.support.reply', $ticket->id) }}">@csrf
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">الرسالة</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-message"></i></span>
                <textarea name="message" class="form-control" rows="3" required placeholder="اكتب الرد هنا..."></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">تحديث الحالة</label>
              <select name="status" class="form-select">
                <option value="">— دون تغيير —</option>
                <option value="open" @selected($ticket->status==='open')>مفتوحة</option>
                <option value="pending" @selected($ticket->status==='pending')>قيد المعالجة</option>
                <option value="closed" @selected($ticket->status==='closed')>مغلقة</option>
              </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary">عودة</a>
              <button class="btn btn-primary">إرسال الرد</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

