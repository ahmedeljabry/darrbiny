@extends('admin.layouts.app')
@section('title','الإشعارات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الإشعارات</li>
  </ol>
</nav>

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
  <div class="card-header d-flex align-items-center gap-2">
    <span class="avatar-initial rounded bg-label-primary">
      <i class="icon-base ti tabler-bell"></i>
    </span>
    <div>
      <h5 class="mb-0">إرسال إشعار</h5>
      <small class="text-body-secondary">جميع الإشعارات من هذه الصفحة يتم إرسال Push لها عبر Firebase Topics مع حفظ نسخة في قاعدة البيانات</small>
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
              <option value="trainees">جميع المتدربين</option>
              <option value="user">مستخدم واحد</option>
            </select>
          </div>
        </div>
        <div class="col-md-8 js-user-select d-none">
          <label class="form-label">اختر المستخدم</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-user"></i></span>
            <select
              name="user_id"
              class="form-select select2 js-user-picker"
              style="width:100%"
              data-search-url="{{ route('admin.notifications.users') }}"
            >
              <option value="">— اختر مستخدمًا —</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('user_id') === $u->id)>
                  {{ $u->name ?? 'بدون اسم' }} — {{ $u->phone_with_cc ?: $u->id }}
                </option>
              @endforeach
            </select>
          </div>
          <small class="text-muted">ابحث بالاسم أو رقم الجوال أو البريد الإلكتروني أو رقم المستخدم.</small>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-12">
          <label class="form-label">عنوان الإشعار</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-bell"></i></span>
            <input type="text" class="form-control" name="title" maxlength="120" required placeholder="عنوان مختصر">
          </div>
        </div>
        <div class="col-md-12">
          <label class="form-label">نص الإشعار</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text align-items-start pt-2"><i class="icon-base ti tabler-message"></i></span>
            <textarea class="form-control" name="message" rows="5" maxlength="1000" required placeholder="اكتب الرسالة هنا..."></textarea>
          </div>
          <small class="text-muted">الحد الأقصى 1000 حرف</small>
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
    const $userPicker = $('.js-user-picker');

    if ($userPicker.data('select2')) {
      $userPicker.select2('destroy');
    }

    $userPicker.select2({
      dir: dir,
      width: '100%',
      dropdownParent: $userPicker.closest('.js-user-select'),
      ajax: {
        url: $userPicker.data('search-url'),
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return {
            q: params.term || '',
            page: params.page || 1
          };
        },
        processResults: function(data, params) {
          params.page = params.page || 1;
          const payload = data.data || data;
          const results = payload.results || [];
          const pagination = payload.pagination || { more: false };

          return {
            results: results,
            pagination: pagination
          };
        }
      },
      placeholder: '— اختر مستخدمًا —',
      allowClear: true,
      minimumInputLength: 0,
      language: {
        inputTooShort: function() {
          return 'اكتب حرفًا واحدًا على الأقل للبحث';
        },
        noResults: function() {
          return 'لا توجد نتائج';
        },
        searching: function() {
          return 'جاري البحث...';
        }
      }
    });
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
