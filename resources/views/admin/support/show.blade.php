@extends('admin.layouts.app')
@section('title','تذكرة دعم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.support.index') }}">تذاكر الدعم</a></li>
    <li class="breadcrumb-item active" aria-current="page">تذكرة دعم</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="avatar-initial rounded bg-label-primary">
            <i class="icon-base ti tabler-headset"></i>
          </span>
          <div>
            <h5 class="mb-0">{{ $ticket->subject }}</h5>
            <small class="text-body-secondary">
              <i class="icon-base ti tabler-user me-1"></i>
              المستخدم: {{ optional($ticket->user)->name ?? $ticket->name ?? '—' }}
            </small>
          </div>
        </div>
        @php
          $statusConfig = [
            'open' => ['label' => 'مفتوحة', 'class' => 'success', 'icon' => 'circle-check'],
            'pending' => ['label' => 'قيد المعالجة', 'class' => 'warning', 'icon' => 'clock'],
            'closed' => ['label' => 'مغلقة', 'class' => 'secondary', 'icon' => 'circle-x'],
          ];
          $config = $statusConfig[$ticket->status] ?? ['label' => $ticket->status, 'class' => 'secondary', 'icon' => 'circle'];
        @endphp
        <span class="badge bg-label-{{ $config['class'] }}">
          <i class="icon-base ti tabler-{{ $config['icon'] }} me-1"></i>
          {{ $config['label'] }}
        </span>
      </div>
      <div class="card-body">
        <div class="d-flex flex-column gap-3">
          @forelse($ticket->messages as $msg)
            <div class="border rounded p-3 {{ $msg->author_type === 'admin' ? 'bg-label-primary bg-opacity-10 border-primary' : 'bg-white' }}">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="avatar-initial rounded bg-label-{{ $msg->author_type === 'admin' ? 'primary' : 'secondary' }}" style="width: 32px; height: 32px;">
                    <i class="icon-base ti tabler-{{ $msg->author_type === 'admin' ? 'user-check' : 'user' }}"></i>
                  </span>
                  <div>
                    <strong class="d-block">{{ $msg->author_type === 'admin' ? 'الإدارة' : (optional($msg->user)->name ?? 'مستخدم') }}</strong>
                    <small class="text-muted">{{ $msg->created_at->format('Y-m-d H:i') }}</small>
                  </div>
                </div>
                <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
              </div>
              <div class="text-body">{{ $msg->message }}</div>
            </div>
          @empty
            <div class="text-center py-4 text-muted">
              <i class="icon-base ti tabler-message-off mb-2" style="font-size: 48px;"></i>
              <p class="mb-0">لا توجد رسائل بعد.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="card mt-4 border-0 shadow-sm">
      <div class="card-header border-0 d-flex align-items-center gap-2">
        <span class="avatar-initial rounded bg-label-success">
          <i class="icon-base ti tabler-message-plus"></i>
        </span>
        <h6 class="mb-0">إضافة رد</h6>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.support.reply', $ticket->id) }}">@csrf
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">الرسالة</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text align-items-start pt-2"><i class="icon-base ti tabler-message"></i></span>
                <textarea name="message" class="form-control" rows="5" required placeholder="اكتب الرد هنا..."></textarea>
              </div>
              <small class="text-muted">الحد الأقصى 2000 حرف</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">تحديث الحالة</label>
              <select name="status" class="form-select">
                <option value="">— دون تغيير —</option>
                <option value="open" @selected($ticket->status==='open')>مفتوحة</option>
                <option value="pending" @selected($ticket->status==='pending')>قيد المعالجة</option>
                <option value="closed" @selected($ticket->status==='closed')>مغلقة</option>
              </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <a href="{{ route('admin.support.index') }}" class="btn btn-outline-secondary">
                <i class="icon-base ti tabler-arrow-right me-1"></i> عودة
              </a>
              <button class="btn btn-primary">
                <i class="icon-base ti tabler-send me-1"></i> إرسال الرد
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0">
        <h6 class="mb-0">معلومات التذكرة</h6>
      </div>
      <div class="card-body">
        <div class="d-flex flex-column gap-3">
          <div>
            <small class="text-muted d-block mb-1">المستخدم</small>
            <div class="fw-semibold">{{ optional($ticket->user)->name ?? $ticket->name ?? '—' }}</div>
            <small class="text-muted">{{ optional($ticket->user)->phone_with_cc ?? $ticket->phone_with_cc ?? '—' }}</small>
          </div>
          <div>
            <small class="text-muted d-block mb-1">البريد الإلكتروني</small>
            <div class="fw-semibold">{{ optional($ticket->user)->email ?? $ticket->email ?? '—' }}</div>
          </div>
          <div>
            <small class="text-muted d-block mb-1">عدد الرسائل</small>
            <div class="fw-semibold">{{ $ticket->messages->count() }}</div>
          </div>
          <div>
            <small class="text-muted d-block mb-1">تاريخ الإنشاء</small>
            <div class="fw-semibold">{{ $ticket->created_at->format('Y-m-d H:i') }}</div>
          </div>
          <div>
            <small class="text-muted d-block mb-1">آخر تحديث</small>
            <div class="fw-semibold">{{ $ticket->updated_at->format('Y-m-d H:i') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

