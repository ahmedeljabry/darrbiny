@extends('admin.layouts.app')
@section('title','الإعدادات')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الإعدادات</li>
  </ol>
</nav>

@if (session('status'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-center gap-2">
      <i class="icon-base ti tabler-check-circle" style="font-size: 20px;"></i>
      <span>{{ session('status') }}</span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <div class="d-flex align-items-start gap-2">
      <i class="icon-base ti tabler-alert-circle mt-1" style="font-size: 20px;"></i>
      <div class="flex-grow-1">
        <strong class="d-block mb-2">حدث خطأ في الإدخال:</strong>
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <span class="avatar-initial rounded bg-label-primary" style="width: 56px; height: 56px;">
        <i class="icon-base ti tabler-settings" style="font-size: 28px;"></i>
      </span>
      <div>
        <h4 class="mb-1 fw-bold">لوحة إعدادات المنصة</h4>
        <p class="mb-0 text-muted">تحكم بالعلامة التجارية، الدفع، الرسوم، المحتوى، الفيديوهات، والأدوار</p>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge bg-label-primary">
        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ فوري
      </span>
      <span class="badge bg-label-success">
        <i class="icon-base ti tabler-refresh me-1"></i> تحديث مباشر
      </span>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-12">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header border-0 pb-3">
        <ul class="nav nav-pills settings-tabs" id="settingsTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button" role="tab">
              <i class="icon-base ti tabler-world me-2"></i> الموقع
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab">
              <i class="icon-base ti tabler-video me-2"></i> الفيديو
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="pages-tab" data-bs-toggle="tab" data-bs-target="#pages" type="button" role="tab">
              <i class="icon-base ti tabler-file-text me-2"></i> الصفحات
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">
              <i class="icon-base ti tabler-shield me-2"></i> الأدوار والمحظورات
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="howitworks-tab" data-bs-toggle="tab" data-bs-target="#howitworks" type="button" role="tab">
              <i class="icon-base ti tabler-help me-2"></i> كيف تعمل الخدمة
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="settingsTabsContent">
          <div class="tab-pane fade show active" id="site" role="tabpanel" aria-labelledby="site-tab">
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                  <div class="card-header border-0 d-flex align-items-center gap-3 pb-3">
                    <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
                      <i class="icon-base ti tabler-brand-appgallery" style="font-size: 24px;"></i>
                    </span>
                    <div>
                      <h6 class="mb-0 fw-bold">العلامة التجارية</h6>
                      <small class="text-muted">الاسم والشعار والأيقونة</small>
                    </div>
                  </div>
                  <div class="card-body">
                    <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf
                      <div class="mb-3">
                        <label class="form-label fw-semibold">اسم العلامة</label>
                        <div class="input-group input-group-merge">
                          <span class="input-group-text"><i class="ti tabler-edit"></i></span>
                          <input class="form-control" name="brand_name" value="{{ old('brand_name', $settings['brand.name'] ?? '') }}" placeholder="اسم التطبيق">
                        </div>
                        @error('brand_name')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">الشعار</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if(!empty($settings['brand.logo_path']))
                          <div class="mt-2">
                            <img src="{{ \App\Support\StorageUrl::make($settings['brand.logo_path']) }}" alt="logo" class="rounded" style="max-height: 60px;">
                          </div>
                        @endif
                        @error('logo')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">الأيقونة (Favicon)</label>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png">
                        @if(!empty($settings['brand.favicon_path']))
                          <div class="mt-2">
                            <img src="{{ \App\Support\StorageUrl::make($settings['brand.favicon_path']) }}" alt="favicon" class="rounded" style="max-height: 32px;">
                          </div>
                        @endif
                        @error('favicon')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <button class="btn btn-primary w-100">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                  <div class="card-header border-0 d-flex align-items-center gap-3 pb-3">
                    <span class="avatar-initial rounded bg-label-success" style="width: 48px; height: 48px;">
                      <i class="icon-base ti tabler-credit-card" style="font-size: 24px;"></i>
                    </span>
                    <div>
                      <h6 class="mb-0 fw-bold">بوابة الدفع: TAP</h6>
                      <small class="text-muted">المفاتيح والويب هوك</small>
                    </div>
                  </div>
                  <div class="card-body">
                    <form method="post" action="{{ route('admin.settings.update') }}">@csrf
                      <div class="mb-3">
                        <label class="form-label fw-semibold">المفتاح العام</label>
                        <div class="input-group input-group-merge">
                          <span class="input-group-text"><i class="ti tabler-key"></i></span>
                          <input type="text" class="form-control" name="tap_public_key" value="{{ old('tap_public_key', $settings['payment.tap.public_key'] ?? '') }}" placeholder="pk_test_...">
                        </div>
                        @error('tap_public_key')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">المفتاح السري</label>
                        <div class="input-group input-group-merge">
                          <span class="input-group-text"><i class="ti tabler-lock"></i></span>
                          <input type="password" class="form-control" name="tap_secret_key" value="{{ old('tap_secret_key', $settings['payment.tap.secret_key'] ?? '') }}" placeholder="sk_test_...">
                        </div>
                        @error('tap_secret_key')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <div class="mb-3">
                        <label class="form-label fw-semibold">سر الويب هوك</label>
                        <div class="input-group input-group-merge">
                          <span class="input-group-text"><i class="ti tabler-webhook"></i></span>
                          <input type="text" class="form-control" name="tap_webhook_secret" value="{{ old('tap_webhook_secret', $settings['payment.tap.webhook_secret'] ?? '') }}" placeholder="whsec_...">
                        </div>
                        @error('tap_webhook_secret')
                          <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                      </div>
                      <button class="btn btn-primary w-100">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-lg-12">
                <div class="card h-100 border-0 shadow-sm">
                  <div class="card-header border-0 d-flex align-items-center gap-3 pb-3">
                    <span class="avatar-initial rounded bg-label-warning" style="width: 48px; height: 48px;">
                      <i class="icon-base ti tabler-currency-dollar" style="font-size: 24px;"></i>
                    </span>
                    <div>
                      <h6 class="mb-0 fw-bold">الرسوم والعمولات</h6>
                      <small class="text-muted">إدارة رسوم التطبيق ورسوم الحجز</small>
                    </div>
                  </div>
                  <div class="card-body">
                    <form method="post" action="{{ route('admin.settings.update') }}">@csrf
                      <div class="row g-4">
                        <div class="col-md-12">
                          <div class="card border border-primary h-100">
                            <div class="card-body">
                              <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                  <i class="icon-base ti tabler-currency-dollar"></i>
                                </span>
                                <div>
                                  <h6 class="mb-0">رسوم الحجز (الثابتة) لكل دولة</h6>
                                  <small class="text-muted">رسوم الحجز لكل دولة</small>
                                </div>
                              </div>
                              <p class="text-body-secondary small mb-3">
                                قيمة ثابتة تدفع في البداية وقبل إرسال أي طلب. يمكنك تحديد رسوم مختلفة لكل دولة.
                              </p>
                              <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                  <thead class="table-light">
                                    <tr>
                                      <th><i class="icon-base ti tabler-world me-1"></i> الدولة</th>
                                      <th><i class="icon-base ti tabler-currency-dollar me-1"></i> رسوم الحجز (بالقروش)</th>
                                      <th><i class="icon-base ti tabler-info-circle me-1"></i> القيمة الحالية</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    @forelse($countries as $country)
                                      <tr>
                                        <td>
                                          <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-label-info">{{ $country->iso2 }}</span>
                                            <span class="fw-semibold">{{ $country->name }}</span>
                                            <small class="text-muted">({{ $country->currency }})</small>
                                          </div>
                                        </td>
                                        <td>
                                          <div class="input-group input-group-merge" style="max-width: 300px;">
                                            <span class="input-group-text"><i class="icon-base ti tabler-currency-dollar"></i></span>
                                            <input type="number" step="50" min="0" class="form-control"
                                                   name="country_fees[{{ $country->id }}]"
                                                   value="{{ $country->reservation_fee_minor ?? $settings['fees.reservation_fee_minor'] ?? config('app.reservation_fee_minor', 1000) }}"
                                                   placeholder="مثال: 1000 = 10.00">
                                          </div>
                                        </td>
                                        <td>
                                          <span class="fw-semibold text-success">
                                            {{ number_format(($country->reservation_fee_minor ?? $settings['fees.reservation_fee_minor'] ?? config('app.reservation_fee_minor', 1000)) / 100, 2) }} {{ $country->currency }}
                                          </span>
                                        </td>
                                      </tr>
                                    @empty
                                      <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                          <i class="icon-base ti tabler-world mb-2" style="font-size: 32px;"></i>
                                          <p class="mb-0">لا توجد دول متاحة</p>
                                        </td>
                                      </tr>
                                    @endforelse
                                  </tbody>
                                </table>
                              </div>
                              <div class="alert alert-info mt-3 mb-0">
                                <div class="d-flex align-items-start gap-2">
                                  <i class="icon-base ti tabler-info-circle mt-1"></i>
                                  <div>
                                    <strong>ملاحظة:</strong> إذا لم يتم تحديد رسوم لدولة معينة، سيتم استخدام القيمة الافتراضية من الإعدادات العامة.
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="card border border-secondary h-100">
                            <div class="card-body">
                              <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                  <i class="icon-base ti tabler-settings"></i>
                                </span>
                                <div>
                                  <h6 class="mb-0">القيمة الافتراضية</h6>
                                  <small class="text-muted">القيمة الافتراضية المستخدمة للدول التي لم يتم تحديد رسوم خاصة بها.</small>
                                </div>
                              </div>
                              <p class="text-body-secondary small mb-3">
                                القيمة الافتراضية المستخدمة للدول التي لم يتم تحديد رسوم خاصة بها.
                              </p>
                              <div class="mb-3">
                                <label class="form-label fw-semibold">المبلغ (بالقروش)</label>
                          <div class="input-group input-group-merge">
                                  <span class="input-group-text"><i class="ti tabler-currency-dollar"></i></span>
                                  <input type="number" step="50" min="0" class="form-control" name="reservation_fee_minor" value="{{ $settings['fees.reservation_fee_minor'] ?? config('app.reservation_fee_minor', 1000) }}" placeholder="مثال: 1000 = 10.00">
                                </div>
                                <small class="text-muted d-block mt-1">
                                  القيمة الحالية: <strong>{{ number_format(($settings['fees.reservation_fee_minor'] ?? config('app.reservation_fee_minor', 1000)) / 100, 2) }}</strong>
                                </small>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="card border border-success h-100">
                            <div class="card-body">
                              <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="avatar-initial rounded bg-label-success">
                                  <i class="icon-base ti tabler-percentage"></i>
                                </span>
                                <div>
                                  <h6 class="mb-0">رسوم التطبيق (النسبة)</h6>
                                  <small class="text-muted">App Fee Percentage</small>
                                </div>
                              </div>
                              <p class="text-body-secondary small mb-3">
                                نسبة معينة (مثلاً 10%) تخصم مباشرة من قيمة الباقة المحولة للمدرب. يتم حسابها تلقائياً عند الدفع.
                              </p>
                              <div class="mb-3">
                                <label class="form-label fw-semibold">النسبة المئوية (%)</label>
                          <div class="input-group input-group-merge">
                                  <span class="input-group-text"><i class="ti tabler-percentage"></i></span>
                                  <input type="number" step="0.1" min="0" max="100" class="form-control" name="app_fee_percent" value="{{ $settings['fees.app_fee_percent'] ?? config('app.app_fee_percent', 10) }}" placeholder="مثال: 10 = 10%">
                                </div>
                                <small class="text-muted d-block mt-1">
                                  القيمة الحالية: <strong>{{ $settings['fees.app_fee_percent'] ?? config('app.app_fee_percent', 10) }}%</strong>
                                </small>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="alert alert-info mt-4 mb-0">
                        <div class="d-flex align-items-start gap-2">
                          <i class="icon-base ti tabler-info-circle mt-1"></i>
                          <div>
                            <strong>مثال توضيحي:</strong>
                            <ul class="mb-0 mt-2 small">
                              <li>إذا كانت <strong>رسوم الحجز = 10.00</strong> و <strong>رسوم التطبيق = 10%</strong></li>
                              <li>المستخدم يدفع <strong>10.00</strong> كرسوم حجز في البداية</li>
                              <li>عند اختيار باقة بقيمة <strong>1000.00</strong>، يتم خصم <strong>100.00</strong> (10%) كرسوم تطبيق</li>
                              <li>المدرب يستلم <strong>900.00</strong> والتطبيق يحصل على <strong>100.00</strong></li>
                            </ul>
                          </div>
                        </div>
                      </div>

                      <button class="btn btn-primary mt-3">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ الرسوم
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="videos" role="tabpanel" aria-labelledby="videos-tab">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-info">
                  <i class="icon-base ti tabler-video"></i>
                </span>
                <div>
                <h6 class="mb-0">فيديو التطبيق</h6>
                <small class="text-muted">تحميل فيديو واجهة المستخدم والكابتن</small>
                </div>
              </div>
              <div class="card-body">
                <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">فيديو واجهة المستخدمين</label>
                      <input type="file" name="video_app_file" accept="video/*" class="form-control">
                      <small class="text-body-secondary d-block mt-1">يظهر داخل تطبيق العميل.</small>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">فيديو واجهة الكباتن</label>
                      <input type="file" name="video_captain_file" accept="video/*" class="form-control">
                      <small class="text-body-secondary d-block mt-1">يظهر داخل تطبيق الكابتن.</small>
                    </div>
                  </div>
                  @if(!empty($settings['video.app.path']) || !empty($settings['video.captain.path']))
                    <div class="row g-4 mt-1">
                      @if(!empty($settings['video.app.path']))
                        <div class="col-md-6">
                          <label class="form-label d-block">الفيديو الحالي للمستخدمين</label>
                          <video src="{{ \App\Support\StorageUrl::make($settings['video.app.path']) }}" controls style="max-width:100%; height:auto;"></video>
                        </div>
                      @endif
                      @if(!empty($settings['video.captain.path']))
                        <div class="col-md-6">
                          <label class="form-label d-block">الفيديو الحالي للكباتن</label>
                          <video src="{{ \App\Support\StorageUrl::make($settings['video.captain.path']) }}" controls style="max-width:100%; height:auto;"></video>
                        </div>
                      @endif
                    </div>
                  @endif
                  <div class="mt-3">
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="pages" role="tabpanel" aria-labelledby="pages-tab">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-secondary">
                  <i class="icon-base ti tabler-file-text"></i>
                </span>
                <div>
                  <h6 class="mb-0">صفحات الموقع</h6>
                  <small class="text-body-secondary">الصفحات القانونية والمساعدة</small>
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
                  <div class="mb-3">
                    <label class="form-label">الشروط والأحكام</label>
                    <input id="terms_editor" type="hidden" name="page_terms" value="{{ $settings['pages.terms'] ?? '' }}">
                    <trix-editor input="terms_editor" class="trix-content border rounded"></trix-editor>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">عن التطبيق</label>
                    <input id="about_editor" type="hidden" name="page_about" value="{{ $settings['pages.about'] ?? '' }}">
                    <trix-editor input="about_editor" class="trix-content border rounded"></trix-editor>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">صفحة المبيعات</label>
                    <input id="sales_editor" type="hidden" name="page_sales" value="{{ $settings['pages.sales'] ?? '' }}">
                    <trix-editor input="sales_editor" class="trix-content border rounded"></trix-editor>
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
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="roles" role="tabpanel" aria-labelledby="roles-tab">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-shield"></i>
                </span>
                <div>
                  <h6 class="mb-0">الأدوار والمحظورات</h6>
                  <small class="text-body-secondary">إدارة مهام الكابتن والمتدرب داخل التطبيق</small>
                </div>
              </div>
              <div class="card-body">
                <form method="post" action="{{ route('admin.settings.update') }}">@csrf
                  <div class="row g-4">
                    <div class="col-lg-6">
                      <div class="card border border-primary h-100">
                        <div class="card-body">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-primary">
                              <i class="icon-base ti tabler-user-star"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">أدوار الكابتن</h6>
                              <small class="text-muted">المسؤوليات الأساسية للكابتن</small>
                            </div>
                          </div>
                          <div id="trainer-roles-list" class="d-flex flex-column gap-2 settings-list" data-name="trainer_roles[]" data-placeholder="مثال: الالتزام بالمواعيد" data-icon="tabler-user-star">
                            @foreach($trainerRoles as $role)
                              <div class="input-group settings-list-row">
                                <span class="input-group-text"><i class="ti tabler-user-star"></i></span>
                                <input type="text" class="form-control" name="trainer_roles[]" value="{{ $role }}" placeholder="مثال: الالتزام بالمواعيد">
                                <button type="button" class="btn btn-outline-danger js-remove-settings-item">حذف</button>
                              </div>
                            @endforeach
                          </div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary js-add-settings-item" data-target="#trainer-roles-list">
                              <i class="ti tabler-plus"></i> إضافة دور
                            </button>
                          </div>
                          <hr class="my-4">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-warning">
                              <i class="icon-base ti tabler-ban"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">محظورات الكابتن</h6>
                              <small class="text-muted">الأفعال غير المسموح بها أثناء التدريب</small>
                            </div>
                          </div>
                          <div id="trainer-restrictions-list" class="d-flex flex-column gap-2 settings-list" data-name="trainer_restrictions[]" data-placeholder="مثال: استخدام الهاتف أثناء التدريب" data-icon="tabler-ban">
                            @foreach($trainerRestrictions as $item)
                              <div class="input-group settings-list-row">
                                <span class="input-group-text"><i class="ti tabler-ban"></i></span>
                                <input type="text" class="form-control" name="trainer_restrictions[]" value="{{ $item }}" placeholder="مثال: استخدام الهاتف أثناء التدريب">
                                <button type="button" class="btn btn-outline-danger js-remove-settings-item">حذف</button>
                              </div>
                            @endforeach
                          </div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-warning js-add-settings-item" data-target="#trainer-restrictions-list">
                              <i class="ti tabler-plus"></i> إضافة محظور
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-6">
                      <div class="card border border-info h-100">
                        <div class="card-body">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-info">
                              <i class="icon-base ti tabler-user"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">أدوار المتدرب</h6>
                              <small class="text-muted">المهام المتوقعة من المتدرب</small>
                            </div>
                          </div>
                          <div id="user-roles-list" class="d-flex flex-column gap-2 settings-list" data-name="user_roles[]" data-placeholder="مثال: الالتزام بالحضور" data-icon="tabler-user">
                            @foreach($userRoles as $role)
                              <div class="input-group settings-list-row">
                                <span class="input-group-text"><i class="ti tabler-user"></i></span>
                                <input type="text" class="form-control" name="user_roles[]" value="{{ $role }}" placeholder="مثال: الالتزام بالحضور">
                                <button type="button" class="btn btn-outline-danger js-remove-settings-item">حذف</button>
                              </div>
                            @endforeach
                          </div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-info js-add-settings-item" data-target="#user-roles-list">
                              <i class="ti tabler-plus"></i> إضافة دور
                            </button>
                          </div>
                          <hr class="my-4">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-danger">
                              <i class="icon-base ti tabler-ban"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">محظورات المتدرب</h6>
                              <small class="text-muted">الأفعال غير المسموح بها أثناء التدريب</small>
                            </div>
                          </div>
                          <div id="user-restrictions-list" class="d-flex flex-column gap-2 settings-list" data-name="user_restrictions[]" data-placeholder="مثال: التأخر عن الموعد" data-icon="tabler-ban">
                            @foreach($userRestrictions as $item)
                              <div class="input-group settings-list-row">
                                <span class="input-group-text"><i class="ti tabler-ban"></i></span>
                                <input type="text" class="form-control" name="user_restrictions[]" value="{{ $item }}" placeholder="مثال: التأخر عن الموعد">
                                <button type="button" class="btn btn-outline-danger js-remove-settings-item">حذف</button>
                              </div>
                            @endforeach
                          </div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-danger js-add-settings-item" data-target="#user-restrictions-list">
                              <i class="ti tabler-plus"></i> إضافة محظور
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="mt-3 d-flex justify-content-end">
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="howitworks" role="tabpanel" aria-labelledby="howitworks-tab">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="icon-base ti tabler-help"></i>
                  </span>
                <div>
                  <h6 class="mb-0">كيف تعمل الخدمة</h6>
                  <small class="text-body-secondary">إدارة الأقسام والخطوات</small>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary js-hiw-add-section">
                  <i class="icon-base ti tabler-plus me-1"></i> إضافة قسم
                </button>
              </div>
              <div class="card-body">
                <form method="post" action="{{ route('admin.settings.howitworks.update') }}">@csrf
                  @php($hiw = \App\Models\HowItWorksSection::with('steps')->get())
                  <div id="hiw-list" class="d-flex flex-column gap-2">
                    @if ($hiw->isEmpty())
                      <div class="border rounded p-2 hiw-row">
                        <div class="mb-2">
                          <label class="form-label">العنوان</label>
                          <input type="text" class="form-control" name="sections[0][title]" placeholder="اكتب العنوان">
                        </div>
                        <div class="mb-2">
                          <label class="form-label d-block">الخطوات</label>
                          <div class="d-flex flex-column gap-2 steps">
                            <div class="input-group">
                              <span class="input-group-text"><i class="ti tabler-check"></i></span>
                              <input type="text" class="form-control" name="sections[0][steps][0]" placeholder="اكتب الخطوة">
                              <button type="button" class="btn btn-outline-danger js-hiw-remove-step">حذف</button>
                            </div>
                          </div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary js-hiw-add-step">إضافة خطوة</button>
                          </div>
                        </div>
                        <div class="d-flex justify-content-end mt-1">
                          <button type="button" class="btn btn-sm btn-outline-danger js-hiw-remove-section">حذف القسم</button>
                        </div>
                      </div>
                    @else
                      @foreach($hiw as $i => $section)
                        <div class="border rounded p-2 hiw-row">
                          <div class="mb-2">
                            <label class="form-label">العنوان</label>
                            <input type="text" class="form-control" name="sections[{{ $i }}][title]" value="{{ $section->title }}" placeholder="اكتب العنوان">
                          </div>
                          <div class="mb-2">
                            <label class="form-label d-block">الخطوات</label>
                            <div class="d-flex flex-column gap-2 steps">
                              @foreach($section->steps as $j => $step)
                                <div class="input-group">
                                  <span class="input-group-text"><i class="ti tabler-check"></i></span>
                                  <input type="text" class="form-control" name="sections[{{ $i }}][steps][{{ $j }}]" value="{{ $step->title }}" placeholder="اكتب الخطوة">
                                  <button type="button" class="btn btn-outline-danger js-hiw-remove-step">حذف</button>
                                </div>
                              @endforeach
                            </div>
                            <div class="mt-2">
                              <button type="button" class="btn btn-sm btn-outline-primary js-hiw-add-step">إضافة خطوة</button>
                            </div>
                          </div>
                          <div class="d-flex justify_content-end mt-1">
                            <button type="button" class="btn btn-sm btn-outline-danger js-hiw-remove-section">حذف القسم</button>
                          </div>
                        </div>
                      @endforeach
                    @endif
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .settings-tabs .nav-link {
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    margin: 0 0.25rem;
  }
  .settings-tabs .nav-link:hover {
    background: rgba(79, 70, 229, 0.1);
    transform: translateY(-2px);
  }
  .settings-tabs .nav-link.active {
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
  }
  .card {
    transition: all 0.3s ease;
  }
  .card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .form-control:focus, .form-select:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
  }
  .btn-primary {
    background: #4f46e5;
    border-color: #4f46e5;
  }
  .btn-primary:hover {
    background: #4338ca;
    border-color: #4338ca;
  }
  .table th {
    font-weight: 600;
    font-size: 0.875rem;
  }
  .settings-list-row .btn {
    min-width: 84px;
  }
  .is-invalid {
    border-color: #dc3545;
  }
  .is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
  }
</style>
@endpush

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
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    function addSettingsRow(list, value){
      if (!list) return;
      const icon = list.dataset.icon || 'tabler-list';
      const name = list.dataset.name || 'items[]';
      const placeholder = list.dataset.placeholder || '';
      const row = document.createElement('div');
      row.className = 'input-group settings-list-row';
      row.innerHTML = `
        <span class="input-group-text"><i class="ti ${icon}"></i></span>
        <input type="text" class="form-control" name="${name}" placeholder="${placeholder}">
        <button type="button" class="btn btn-outline-danger js-remove-settings-item">حذف</button>
      `;
      if (value) row.querySelector('input').value = value;
      list.appendChild(row);
    }

    document.addEventListener('click', function(e){
      const addBtn = e.target.closest('.js-add-settings-item');
      if (addBtn) {
        const list = document.querySelector(addBtn.dataset.target);
        addSettingsRow(list, '');
      }
      const removeBtn = e.target.closest('.js-remove-settings-item');
      if (removeBtn) {
        const row = removeBtn.closest('.settings-list-row');
        const list = removeBtn.closest('.settings-list');
        if (!row || !list) return;
        const rows = list.querySelectorAll('.settings-list-row');
        if (rows.length > 1) {
          row.remove();
        } else {
          row.querySelector('input').value = '';
        }
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

@push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    function addHiwSection(title = '', steps = ['']){
      const idx = document.querySelectorAll('#hiw-list .hiw-row').length;
      const el = document.createElement('div');
      el.className = 'border rounded p-2 hiw-row';
      el.innerHTML = `
        <div class="mb-2">
          <label class="form-label">العنوان</label>
          <input type="text" class="form-control" name="sections[${idx}][title]" placeholder="اكتب العنوان" />
        </div>
        <div class="mb-2">
          <label class="form-label d-block">الخطوات</label>
          <div class="d-flex flex-column gap-2 steps"></div>
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary js-hiw-add-step">إضافة خطوة</button>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-1">
          <button type="button" class="btn btn-sm btn-outline-danger js-hiw-remove-section">حذف القسم</button>
        </div>`;
      el.querySelector('input').value = title || '';
      const stepsEl = el.querySelector('.steps');
      (steps && steps.length ? steps : ['']).forEach((s, j) => {
        const row = document.createElement('div');
        row.className = 'input-group';
        row.innerHTML = `
          <span class="input-group-text"><i class="ti tabler-check"></i></span>
          <input type="text" class="form-control" name="sections[${idx}][steps][${j}]" placeholder="اكتب الخطوة" />
          <button type="button" class="btn btn-outline-danger js-hiw-remove-step">حذف</button>`;
        row.querySelector('input').value = s || '';
        stepsEl.appendChild(row);
      });
      document.getElementById('hiw-list').appendChild(el);
    }

    document.addEventListener('click', function(e){
      if (e.target.closest('.js-hiw-add-section')) addHiwSection();
      if (e.target.closest('.js-hiw-remove-section')) {
        const row = e.target.closest('.hiw-row');
        const all = document.querySelectorAll('#hiw-list .hiw-row');
        if (all.length > 1) row.remove();
      }
      if (e.target.closest('.js-hiw-add-step')) {
        const sec = e.target.closest('.hiw-row');
        const steps = sec.querySelector('.steps');
        const idx = Array.from(document.querySelectorAll('#hiw-list .hiw-row')).indexOf(sec);
        const j = steps.querySelectorAll('.input-group').length;
        const row = document.createElement('div');
        row.className = 'input-group';
        row.innerHTML = `
          <span class="input-group-text"><i class="ti tabler-check"></i></span>
          <input type="text" class="form-control" name="sections[${idx}][steps][${j}]" placeholder="اكتب الخطوة" />
          <button type="button" class="btn btn-outline-danger js-hiw-remove-step">حذف</button>`;
        steps.appendChild(row);
      }
      if (e.target.closest('.js-hiw-remove-step')) {
        const row = e.target.closest('.input-group');
        const steps = e.target.closest('.steps');
        if (steps.querySelectorAll('.input-group').length > 1) row.remove();
      }
    });
  });
  </script>
@endpush

@endsection
