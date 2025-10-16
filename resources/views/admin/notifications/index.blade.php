@extends('admin.layouts.app')
@section('title','الإشعارات')
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <h5 class="mb-0">إرسال إشعار</h5>
      <small class="text-body-secondary">أرسل إشعارًا لمستخدم محدد أو لجميع المدربين</small>
    </div>
  </div>
  <div class="card-body">
    <form method="post" action="{{ route('admin.notifications.send') }}">@csrf
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">الجمهور</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-megaphone"></i></span>
            <select class="form-select js-audience" name="audience" required>
              <option value="trainers">جميع المدربين</option>
              <option value="user">مستخدم واحد</option>
            </select>
          </div>
        </div>
        <div class="col-md-8 js-user-select d-none">
          <label class="form-label">اختر المستخدم</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-user"></i></span>
            <select name="user_id" class="form-select select2" style="width:100%">
              <option value="">— اختر مستخدمًا —</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name ?? 'بدون اسم' }} — {{ $u->phone_with_cc }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-4">
          <label class="form-label">عنوان الإشعار</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-bell"></i></span>
            <input type="text" class="form-control" name="title" maxlength="120" required placeholder="عنوان مختصر">
          </div>
        </div>
        <div class="col-md-8">
          <label class="form-label">نص الإشعار</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-message"></i></span>
            <input type="text" class="form-control" name="message" maxlength="1000" required placeholder="اكتب الرسالة هنا">
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary">إرسال</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.jQuery && $.fn.select2) {
    const dir = @json(app()->getLocale() === 'en' ? 'ltr' : 'rtl');
    $('.select2').select2({ dir: dir, width: '100%' });
  }
  function toggleUserSelect(){
    const val = document.querySelector('.js-audience').value;
    const box = document.querySelector('.js-user-select');
    if (val === 'user') { box.classList.remove('d-none'); } else { box.classList.add('d-none'); }
  }
  document.querySelector('.js-audience').addEventListener('change', toggleUserSelect);
  toggleUserSelect();
});
</script>
@endpush

@endsection
