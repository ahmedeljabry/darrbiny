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

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <span class="avatar-initial rounded bg-label-primary" style="width: 56px; height: 56px;">
        <i class="icon-base ti tabler-settings" style="font-size: 28px;"></i>
      </span>
    <div>
      <h4 class="mb-1 text-dark">لوحة إعدادات المنصة</h4>
      <p class="mb-0 text-muted">تحكم بالعلامة التجارية، الدفع، الرسوم، المحتوى، والفيديوهات.</p>
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
      <div class="card-header border-0">
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
                <div class="card h-100 border-0 surface">
                  <div class="card-header border-0 d-flex align-items-center gap-2">
                    <span class="avatar-initial rounded bg-label-primary">
                      <i class="icon-base ti tabler-brand-appgallery"></i>
                    </span>
                    <div>
                      <h6 class="mb-0">العلامة التجارية</h6>
                      <small class="text-muted">الاسم والشعار والأيقونة</small>
                    </div>
                  </div>
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
                          <div class="mt-2"><img src="{{ \App\Support\StorageUrl::make($settings['brand.logo_path']) }}" alt="logo" height="48"></div>
                        @endif
                      </div>
                      <div class="mb-3">
                        <label class="form-label">الأيقونة (Favicon)</label>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png">
                        @if(!empty($settings['brand.favicon_path']))
                          <div class="mt-2"><img src="{{ \App\Support\StorageUrl::make($settings['brand.favicon_path']) }}" alt="favicon" height="32"></div>
                        @endif
                      </div>
                      <button class="btn btn-primary w-100">حفظ</button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="card h-100 border-0 surface">
                  <div class="card-header border-0 d-flex align-items-center gap-2">
                    <span class="avatar-initial rounded bg-label-success">
                      <i class="icon-base ti tabler-credit-card"></i>
                    </span>
                    <div>
                      <h6 class="mb-0">بوابة الدفع: TAP</h6>
                      <small class="text-muted">المفاتيح والويب هوك</small>
                    </div>
                  </div>
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
                      <button class="btn btn-primary w-100">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-lg-12">
                <div class="card h-100 border-0 surface">
                  <div class="card-header border-0 d-flex align-items-center gap-2">
                    <span class="avatar-initial rounded bg-label-warning">
                      <i class="icon-base ti tabler-currency-dollar"></i>
                    </span>
                    <div>
                      <h6 class="mb-0">الرسوم والعمولات</h6>
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
                                  <h6 class="mb-0">رسوم الحجز (الجاهزة) لكل دولة</h6>
                                  <small class="text-muted">Reservation Fee Per Country</small>
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
                                  <small class="text-muted">Default Fee</small>
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
  .surface { 
    background: #f9fafb; 
    border: 1px solid #eef2f6; 
    transition: all 0.3s ease;
  }
  .surface:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  .settings-tabs .nav-link { 
    border-radius: 10px; 
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
  }
  .settings-tabs .nav-link:hover {
    background: rgba(79, 70, 229, 0.1);
  }
  .settings-tabs .nav-link.active { 
    background: #4f46e5; 
    color: #fff; 
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
  }
  .card-header .avatar-initial {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
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
