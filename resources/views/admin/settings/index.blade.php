@extends('admin.layouts.app')
@section('title','الإعدادات')
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

<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button" role="tab" aria-controls="site" aria-selected="true">
      <i class="ti tabler-settings"></i> إعدادات الموقع
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab" aria-controls="videos" aria-selected="false">
      <i class="ti tabler-video"></i> فيديو التطبيق
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pages-tab" data-bs-toggle="tab" data-bs-target="#pages" type="button" role="tab" aria-controls="pages" aria-selected="false">
      <i class="ti tabler-file-text"></i> الصفحات
    </button>
  </li>
</ul>

<div class="tab-content mt-4" id="settingsTabsContent">
  <div class="tab-pane fade show active" id="site" role="tabpanel" aria-labelledby="site-tab">
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">العلامة التجارية</h6></div>
          <div class="card-body">
            <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf
          <div class="mb-3">
            <label class="form-label">اسم العلامة</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ti tabler-edit"></i></span>
              <input class="form-control" name="brand_name" value="{{ $settings['brand.name'] ?? '' }}">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">الشعار</label>
            <input type="file" name="logo" class="form-control">
            @if(!empty($settings['brand.logo_path']))
              <div class="mt-2"><img src="{{ Storage::disk(config('filesystems.default','public'))->url($settings['brand.logo_path']) }}" alt="logo" height="48"></div>
            @endif
          </div>
          <div class="mb-3">
            <label class="form-label">الأيقونة (Favicon)</label>
            <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png">
            @if(!empty($settings['brand.favicon_path']))
              <div class="mt-2"><img src="{{ Storage::disk(config('filesystems.default','public'))->url($settings['brand.favicon_path']) }}" alt="favicon" height="32"></div>
            @endif
          </div>
          <button class="btn btn-primary">حفظ</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">بوابة الدفع: TAP</h6></div>
          <div class="card-body">
            <form method="post" action="{{ route('admin.settings.update') }}">@csrf
              <div class="mb-3">
                <label class="form-label">المفتاح العام</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="ti tabler-key"></i></span>
                  <input class="form-control" name="tap_public_key" value="{{ $settings['payment.tap.public_key'] ?? '' }}">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">المفتاح السري</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="ti tabler-lock"></i></span>
                  <input class="form-control" name="tap_secret_key" value="{{ $settings['payment.tap.secret_key'] ?? '' }}">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">سر الويب هوك</label>
                <div class="input-group input-group-merge">
                  <span class="input-group-text"><i class="ti tabler-webhook"></i></span>
                  <input class="form-control" name="tap_webhook_secret" value="{{ $settings['payment.tap.webhook_secret'] ?? '' }}">
                </div>
              </div>
              <button class="btn btn-primary">حفظ</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="videos" role="tabpanel" aria-labelledby="videos-tab">
    <div class="card">
      <div class="card-header"><h6 class="mb-0">فيديو التطبيق</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">ملف فيديو التطبيق</label>
              <input type="file" name="video_app_file" accept="video/*" class="form-control">
            </div>
          </div>
          @if(!empty($settings['video.app.path']))
            <div class="mt-3">
              <label class="form-label d-block">المخزن الحالي</label>
              <video src="{{ Storage::disk(config('filesystems.default','public'))->url($settings['video.app.path']) }}" controls style="max-width:100%; height:auto;"></video>
            </div>
          @endif
          <div class="mt-3">
            <button class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </div>

<div class="tab-content mt-4" id="settingsTabsContentPages">
  <div class="tab-pane fade" id="pages" role="tabpanel" aria-labelledby="pages-tab">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h6 class="mb-0">صفحات الموقع</h6>
          <small class="text-body-secondary">تحكم في محتوى الصفحات القانونية والمساعدة</small>
        </div>
      </div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.settings.update') }}">@csrf
          <div class="mb-3">
            <label class="form-label">سياسة الاستخدام</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ti tabler-shield"></i></span>
              <textarea name="page_usage_policy" class="form-control" rows="6" placeholder="اكتب سياسة الاستخدام">{{ $settings['pages.usage'] ?? '' }}</textarea>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">سياسة الخصوصية</label>
            <input id="privacy_editor" type="hidden" name="page_privacy_policy" value="{{ $settings['pages.privacy'] ?? '' }}">
            <trix-editor input="privacy_editor" class="trix-content border rounded"></trix-editor>
          </div>
          @php($decodedFaqs = json_decode($settings['pages.faq'] ?? '[]', true) ?? [])
          <div class="mb-3">
            <label class="form-label d-block">الأسئلة الشائعة</label>
            <div id="faq-list" class="d-flex flex-column gap-2">
              @if(empty($decodedFaqs))
                @php($decodedFaqs = [[ 'question' => '', 'answer' => '' ]])
              @endif
              @foreach($decodedFaqs as $i => $faq)
                <div class="border rounded p-2 faq-row">
                  <div class="row g-2 align-items-start">
                    <div class="col-md-5">
                      <label class="form-label">السؤال</label>
                      <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti tabler-question-mark"></i></span>
                        <input type="text" name="faqs[{{ $i }}][question]" class="form-control" value="{{ $faq['question'] ?? '' }}" placeholder="اكتب السؤال">
                      </div>
                    </div>
                    <div class="col-md-7">
                      <label class="form-label">الإجابة</label>
                      <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti tabler-message"></i></span>
                        <textarea name="faqs[{{ $i }}][answer]" class="form-control" rows="2" placeholder="اكتب الإجابة">{{ $faq['answer'] ?? '' }}</textarea>
                      </div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-faq">حذف</button>
                  </div>
                </div>
              @endforeach
            </div>
            <div class="mt-2">
              <button type="button" class="btn btn-sm btn-outline-primary js-add-faq"><i class="ti tabler-plus"></i> إضافة سؤال</button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">تواصل معنا</label>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ti tabler-mail"></i></span>
              <textarea name="page_contact" class="form-control" rows="4" placeholder="بيانات التواصل، البريد، الهاتف...">{{ $settings['pages.contact'] ?? '' }}</textarea>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
  <script src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/trix@2.0.8/dist/trix.css" />
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    function addFaqRow(q='', a=''){
      const idx = document.querySelectorAll('#faq-list .faq-row').length;
      const html = `
        <div class="border rounded p-2 faq-row">
          <div class="row g-2 align-items-start">
            <div class="col-md-5">
              <label class="form-label">السؤال</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-question-mark"></i></span>
                <input type="text" name="faqs[${idx}][question]" class="form-control" placeholder="اكتب السؤال"/>
              </div>
            </div>
            <div class="col-md-7">
              <label class="form-label">الإجابة</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-message"></i></span>
                <textarea name="faqs[${idx}][answer]" class="form-control" rows="2" placeholder="اكتب الإجابة"></textarea>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-faq">حذف</button>
          </div>
        </div>`;
      const el = document.createElement('div');
      el.innerHTML = html.trim();
      const row = el.firstChild;
      row.querySelector('input').value = q || '';
      row.querySelector('textarea').value = a || '';
      document.getElementById('faq-list').appendChild(row);
    }
    document.addEventListener('click', function(e){
      if (e.target.closest('.js-add-faq')) { addFaqRow(); }
      if (e.target.closest('.js-remove-faq')) {
        const rows = document.querySelectorAll('#faq-list .faq-row');
        const row = e.target.closest('.faq-row');
        if (rows.length > 1) row.remove(); else row.querySelectorAll('input,textarea').forEach(f=>f.value='');
      }
    });
  });
  </script>
@endpush

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const hash = window.location.hash;
      if (hash) {
        const triggerEl = document.querySelector(`[data-bs-target="${hash}"]`);
        if (triggerEl) new bootstrap.Tab(triggerEl).show();
      }
    });
  </script>
@endpush

@endsection
