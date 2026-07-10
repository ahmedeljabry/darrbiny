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
      <div class="card-header border-0 pb-3 settings-toolbar">
        <div class="settings-banner d-flex flex-column gap-3">
          <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
            <div>
              <h5 class="mb-1 fw-bold">إدارة إعدادات المنصة</h5>
              <p class="mb-0 text-muted">واجهة إعدادات مبسطة بتبويبات واضحة وسريعة</p>
            </div>
          </div>
          <ul class="nav nav-pills settings-tabs" id="settings-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button" role="tab" aria-controls="site" aria-selected="true">
                <i class="icon-base ti tabler-world me-1"></i>عام
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="integration-keys-tab" data-bs-toggle="tab" data-bs-target="#integration-keys" type="button" role="tab" aria-controls="integration-keys" aria-selected="false">
                <i class="icon-base ti tabler-key me-1"></i>مفاتيح الربط
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="financial-rates-tab" data-bs-toggle="tab" data-bs-target="#financial-rates" type="button" role="tab" aria-controls="financial-rates" aria-selected="false">
                <i class="icon-base ti tabler-exchange me-1"></i>الرسوم والتحويل
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="gateway-fees-tab" data-bs-toggle="tab" data-bs-target="#gateway-fees" type="button" role="tab" aria-controls="gateway-fees" aria-selected="false">
                <i class="icon-base ti tabler-credit-card me-1"></i>رسوم وعمولات
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab" aria-controls="videos" aria-selected="false">
                <i class="icon-base ti tabler-video me-1"></i>الفيديو
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="pages-tab" data-bs-toggle="tab" data-bs-target="#pages" type="button" role="tab" aria-controls="pages" aria-selected="false">
                <i class="icon-base ti tabler-file-text me-1"></i>الصفحات
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab" aria-controls="roles" aria-selected="false">
                <i class="icon-base ti tabler-shield me-1"></i>الأدوار
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="howitworks-tab" data-bs-toggle="tab" data-bs-target="#howitworks" type="button" role="tab" aria-controls="howitworks" aria-selected="false">
                <i class="icon-base ti tabler-help me-1"></i>كيف تعمل الخدمة
              </button>
            </li>
          </ul>
        </div>
      </div>
      <div class="card-body">
        <div class="tab-content settings-sections">
          <div class="tab-pane fade show active settings-pane settings-block" id="site" data-section-label="الموقع" role="tabpanel" aria-labelledby="site-tab" tabindex="0">
            <div class="row g-4">
              <div class="col-xl-6 col-lg-6">
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
            </div>
          </div>

          <div class="tab-pane fade settings-pane settings-block" id="integration-keys" data-section-label="مفاتيح الربط" role="tabpanel" aria-labelledby="integration-keys-tab" tabindex="0">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-key"></i>
                </span>
                <div>
                  <h6 class="mb-0">مفاتيح الربط والتكاملات</h6>
                  <small class="text-body-secondary">واتساب، مزود SMS، وبوابات الدفع</small>
                </div>
              </div>
              <div class="card-body">
                <form method="post" action="{{ route('admin.settings.update') }}">@csrf
                  <div class="row g-4">
                    <div class="col-xl-6">
                      <div class="card h-100 border border-success">
                        <div class="card-body">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-success">
                              <i class="icon-base ti tabler-brand-whatsapp"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">HyperSend WhatsApp</h6>
                              <small class="text-muted">Token و Instance ID</small>
                            </div>
                          </div>
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Token</label>
                            <div class="input-group input-group-merge">
                              <span class="input-group-text"><i class="ti tabler-key"></i></span>
                              <input type="password" class="form-control" name="hypersend_whatsapp_token" value="{{ old('hypersend_whatsapp_token', $settings['integrations.hypersend.whatsapp.token'] ?? '') }}" autocomplete="off" placeholder="HyperSend token">
                            </div>
                            @error('hypersend_whatsapp_token')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                          </div>
                          <div class="mb-0">
                            <label class="form-label fw-semibold">Instance ID</label>
                            <div class="input-group input-group-merge">
                              <span class="input-group-text"><i class="ti tabler-device-mobile"></i></span>
                              <input type="text" class="form-control" name="hypersend_whatsapp_instance_id" value="{{ old('hypersend_whatsapp_instance_id', $settings['integrations.hypersend.whatsapp.instance_id'] ?? '') }}" placeholder="Instance ID">
                            </div>
                            @error('hypersend_whatsapp_instance_id')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-xl-6">
                      <div class="card h-100 border border-info">
                        <div class="card-body">
                          <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="avatar-initial rounded bg-label-info">
                              <i class="icon-base ti tabler-message-2"></i>
                            </span>
                            <div>
                              <h6 class="mb-0">مزود خدمة SMS</h6>
                              <small class="text-muted">بيانات المزود والمرسل</small>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <label class="form-label fw-semibold">اسم المزود</label>
                              <input type="text" class="form-control" name="sms_provider" value="{{ old('sms_provider', $settings['integrations.sms.provider'] ?? '') }}" placeholder="مثال: HyperSend">
                              @error('sms_provider')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                            <div class="col-md-6">
                              <label class="form-label fw-semibold">Sender ID</label>
                              <input type="text" class="form-control" name="sms_sender_id" value="{{ old('sms_sender_id', $settings['integrations.sms.sender_id'] ?? '') }}" placeholder="Darrbiny">
                              @error('sms_sender_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                            <div class="col-md-12">
                              <label class="form-label fw-semibold">API Key / Token</label>
                              <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti tabler-key"></i></span>
                                <input type="password" class="form-control" name="sms_api_key" value="{{ old('sms_api_key', $settings['integrations.sms.api_key'] ?? '') }}" autocomplete="off" placeholder="SMS API key">
                              </div>
                              @error('sms_api_key')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                            <div class="col-md-12">
                              <label class="form-label fw-semibold">Base URL</label>
                              <input type="url" class="form-control" name="sms_base_url" value="{{ old('sms_base_url', $settings['integrations.sms.base_url'] ?? '') }}" placeholder="https://api.example.com">
                              @error('sms_base_url')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    @php
                      $gatewayKeyFields = [
                        'tap' => [
                          'label' => 'تاب',
                          'tone' => 'primary',
                          'public' => 'tap_public_key',
                          'secret' => 'tap_secret_key',
                          'webhook' => 'tap_webhook_secret',
                          'setting_prefix' => 'payment.tap',
                        ],
                        'tabby' => [
                          'label' => 'تابي',
                          'tone' => 'warning',
                          'public' => 'tabby_public_key',
                          'secret' => 'tabby_secret_key',
                          'webhook' => 'tabby_webhook_secret',
                          'merchant_code' => 'tabby_merchant_code',
                          'base_url' => 'tabby_base_url',
                          'enabled' => 'tabby_enabled',
                          'setting_prefix' => 'payment.tabby',
                        ],
                        'tamara' => [
                          'label' => 'تمارا',
                          'tone' => 'danger',
                          'public' => 'tamara_public_key',
                          'secret' => 'tamara_secret_key',
                          'webhook' => 'tamara_webhook_secret',
                          'base_url' => 'tamara_base_url',
                          'enabled' => 'tamara_enabled',
                          'setting_prefix' => 'payment.tamara',
                        ],
                      ];
                    @endphp

                    @foreach($gatewayKeyFields as $gatewayCode => $gateway)
                      @php
                        $enabledField = $gateway['enabled'] ?? null;
                        $enabledValue = $enabledField
                          ? old($enabledField, $settings[$gateway['setting_prefix'] . '.enabled'] ?? '1')
                          : '1';
                        $isEnabled = filter_var($enabledValue, FILTER_VALIDATE_BOOL);
                      @endphp
                      <div class="col-xl-4 col-lg-6">
                        <div class="card h-100 border border-{{ $gateway['tone'] }}">
                          <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-initial rounded bg-label-{{ $gateway['tone'] }}">
                                  <i class="icon-base ti tabler-credit-card"></i>
                                </span>
                                <div>
                                  <h6 class="mb-0">بوابة {{ $gateway['label'] }}</h6>
                                  <small class="text-muted">{{ strtoupper($gatewayCode) }}</small>
                                </div>
                              </div>
                              @if($enabledField)
                                <div class="form-check form-switch m-0">
                                  <input type="hidden" name="{{ $enabledField }}" value="0">
                                  <input class="form-check-input" type="checkbox" role="switch" id="{{ $enabledField }}" name="{{ $enabledField }}" value="1" @checked($isEnabled)>
                                </div>
                              @else
                                <span class="badge bg-label-success">ظاهر</span>
                              @endif
                            </div>
                            @if($enabledField)
                              <label class="form-label d-block" for="{{ $enabledField }}">
                                {{ $isEnabled ? 'ظاهرة في التطبيق' : 'مخفية من التطبيق' }}
                              </label>
                            @endif
                            <div class="mb-3">
                              <label class="form-label fw-semibold">Public Key</label>
                              <input type="text" class="form-control" name="{{ $gateway['public'] }}" value="{{ old($gateway['public'], $settings[$gateway['setting_prefix'] . '.public_key'] ?? '') }}" placeholder="Public key">
                              @error($gateway['public'])
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                            @if(!empty($gateway['merchant_code']))
                              <div class="mb-3">
                                <label class="form-label fw-semibold">Merchant Code</label>
                                <input type="text" class="form-control" name="{{ $gateway['merchant_code'] }}" value="{{ old($gateway['merchant_code'], $settings[$gateway['setting_prefix'] . '.merchant_code'] ?? '') }}" placeholder="Merchant code">
                                @error($gateway['merchant_code'])
                                  <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                              </div>
                            @endif
                            @if(!empty($gateway['base_url']))
                              <div class="mb-3">
                                <label class="form-label fw-semibold">API Base URL</label>
                                <input type="url" class="form-control" name="{{ $gateway['base_url'] }}" value="{{ old($gateway['base_url'], $settings[$gateway['setting_prefix'] . '.base_url'] ?? '') }}" placeholder="{{ $gatewayCode === 'tabby' ? 'https://api.tabby.sa' : 'https://api.tamara.co' }}">
                                @error($gateway['base_url'])
                                  <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                              </div>
                            @endif
                            <div class="mb-3">
                              <label class="form-label fw-semibold">Secret Key</label>
                              <input type="password" class="form-control" name="{{ $gateway['secret'] }}" value="{{ old($gateway['secret'], $settings[$gateway['setting_prefix'] . '.secret_key'] ?? '') }}" autocomplete="off" placeholder="Secret key">
                              @error($gateway['secret'])
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                            <div class="mb-0">
                              <label class="form-label fw-semibold">Webhook Secret</label>
                              <input type="password" class="form-control" name="{{ $gateway['webhook'] }}" value="{{ old($gateway['webhook'], $settings[$gateway['setting_prefix'] . '.webhook_secret'] ?? '') }}" autocomplete="off" placeholder="Webhook secret">
                              @error($gateway['webhook'])
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>

                  <div class="alert alert-warning mt-4 mb-0">
                    <div class="d-flex align-items-start gap-2">
                      <i class="icon-base ti tabler-alert-triangle mt-1"></i>
                      <div>المفاتيح محفوظة للإدارة فقط. الـ API الخاص بالموبايل يرجع حالة ظهور طريقة الدفع فقط ولا يرجع أي مفاتيح سرية.</div>
                    </div>
                  </div>

                  <div class="mt-3">
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ مفاتيح الربط
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="tab-pane fade settings-pane settings-block" id="financial-rates" data-section-label="الرسوم والتحويل" role="tabpanel" aria-labelledby="financial-rates-tab" tabindex="0">
            <div class="row g-4">
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

                      <div id="exchange-rates" class="mt-4">
                        <div class="card border border-info h-100">
                          <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-3">
                              <span class="avatar-initial rounded bg-label-info">
                                <i class="icon-base ti tabler-exchange"></i>
                              </span>
                              <div>
                                <h6 class="mb-0">معدلات تحويل التقارير إلى {{ $reportCurrency }}</h6>
                                <small class="text-muted">تستخدم في الحجوزات والمالية والتقارير والداشبورد المالي ومحفظة التطبيق</small>
                              </div>
                            </div>

                            <p class="text-body-secondary small mb-3">
                              أدخل قيمة <strong>1</strong> من العملة الأصلية كم تساوي بالـ <strong>{{ $reportCurrency }}</strong>.
                              مثال: إذا كان <strong>1 EGP = 0.075 SAR</strong> فاكتب <strong>0.075</strong>.
                            </p>

                            <div class="alert alert-info">
                              <div class="d-flex align-items-start gap-2">
                                <i class="icon-base ti tabler-info-circle mt-1"></i>
                                <div>
                                  <strong>مهم:</strong> صفحات الحجوزات والمدفوعات وتقارير الإيرادات والمدفوعات والاشتراكات وضريبة القيمة المضافة تعرض المبالغ بالـ {{ $reportCurrency }} بعد التحويل حسب السعر المحفوظ لكل عملة.
                                  الريال السعودي ثابت بقيمة <strong>1.000000</strong> ولا يحتاج إلى إضافته هنا. هذه الصفحة هي المصدر المعتمد لأسعار الصرف في النظام كله.
                                </div>
                              </div>
                            </div>

                            @if(!empty($paymentCurrencies))
                              <div class="mb-3">
                                <div class="small text-muted mb-2">عملات مكتشفة حاليًا من الدول والمدفوعات</div>
                                <div class="d-flex flex-wrap gap-2">
                                  @foreach($paymentCurrencies as $currency)
                                    <span class="badge bg-label-secondary">{{ $currency }}</span>
                                  @endforeach
                                </div>
                              </div>
                            @endif

                            <div id="report-exchange-rates-list" class="d-flex flex-column gap-3 report-exchange-rates-list">
                              @foreach($reportExchangeRates as $index => $rateRow)
                                <div class="row g-2 align-items-end report-exchange-rate-row">
                                  <div class="col-lg-4">
                                    <label class="form-label fw-semibold">رمز العملة</label>
                                    <div class="input-group input-group-merge">
                                      <span class="input-group-text"><i class="ti tabler-currency"></i></span>
                                      <input
                                        type="text"
                                        class="form-control js-exchange-currency"
                                        name="report_exchange_rates[{{ $index }}][currency]"
                                        value="{{ strtoupper((string) ($rateRow['currency'] ?? '')) }}"
                                        placeholder="EGP"
                                        maxlength="3"
                                        style="text-transform: uppercase"
                                      >
                                    </div>
                                  </div>
                                  <div class="col-lg-5">
                                    <label class="form-label fw-semibold">قيمة 1 من العملة بالـ {{ $reportCurrency }}</label>
                                    <div class="input-group input-group-merge">
                                      <span class="input-group-text"><i class="ti tabler-calculator"></i></span>
                                      <input
                                        type="number"
                                        step="0.000001"
                                        min="0.000001"
                                        class="form-control js-exchange-rate"
                                        name="report_exchange_rates[{{ $index }}][rate]"
                                        value="{{ $rateRow['rate'] ?? '' }}"
                                        placeholder="0.075000"
                                      >
                                    </div>
                                  </div>
                                  <div class="col-lg-3 d-grid">
                                    <button type="button" class="btn btn-outline-danger js-remove-exchange-rate">
                                      <i class="icon-base ti tabler-trash me-1"></i> حذف
                                    </button>
                                  </div>
                                </div>
                              @endforeach
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                              <small class="text-muted">يمكنك تعديل المعدلات في أي وقت، وستنعكس مباشرة على المجاميع في الإدارة.</small>
                              <button type="button" class="btn btn-sm btn-outline-info js-add-exchange-rate">
                                <i class="icon-base ti tabler-plus me-1"></i> إضافة معدل تحويل
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>

                      <button class="btn btn-primary mt-3">
                        <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ الرسوم ومعدلات التحويل
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade settings-pane settings-block" id="gateway-fees" data-section-label="رسوم وعمولات" role="tabpanel" aria-labelledby="gateway-fees-tab" tabindex="0">
            <div class="card border-0 surface">
              <div class="card-header border-0 d-flex align-items-center gap-2">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="icon-base ti tabler-credit-card"></i>
                </span>
                <div>
                  <h6 class="mb-0">رسوم وعمولات بوابات الدفع</h6>
                  <small class="text-body-secondary">تاب، تابي، تمارا حسب الدولة</small>
                </div>
              </div>
              <div class="card-body">
                <form method="post" action="{{ route('admin.settings.update') }}">@csrf
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th><i class="icon-base ti tabler-credit-card me-1"></i> بوابة الدفع</th>
                          <th><i class="icon-base ti tabler-world me-1"></i> الدولة</th>
                          <th><i class="icon-base ti tabler-cash me-1"></i> الرسوم الثابتة</th>
                          <th><i class="icon-base ti tabler-percentage me-1"></i> العمولة</th>
                          <th><i class="icon-base ti tabler-info-circle me-1"></i> القيمة الحالية</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($paymentGatewayFees as $index => $gatewayFee)
                          @php
                            $gatewayKey = (string) ($gatewayFee['gateway'] ?? '');
                            $gatewayLabel = $gatewayFee['label'] ?? (\App\Support\PaymentGatewayFees::GATEWAYS[$gatewayKey] ?? $gatewayKey);
                            $gatewayCountryId = $gatewayFee['country_id'] ?? null;
                            $gatewayCountry = $gatewayCountryId ? $countries->firstWhere('id', (string) $gatewayCountryId) : null;
                            $gatewayCurrency = $gatewayCountry?->currency ?: 'SAR';
                            $fixedFeeMinor = (int) ($gatewayFee['fixed_fee_minor'] ?? \App\Support\PaymentGatewayFees::DEFAULT_FIXED_FEE_MINOR);
                            $commissionPercent = $gatewayFee['commission_percent'] ?? \App\Support\PaymentGatewayFees::DEFAULT_COMMISSION_PERCENT;
                          @endphp
                          <tr>
                            <td>
                              <input type="hidden" name="payment_gateway_fees[{{ $index }}][gateway]" value="{{ $gatewayKey }}">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-initial rounded bg-label-primary" style="width: 36px; height: 36px;">
                                  <i class="icon-base ti tabler-credit-card"></i>
                                </span>
                                <div>
                                  <div class="fw-semibold">{{ $gatewayLabel }}</div>
                                  <small class="text-muted">{{ strtoupper($gatewayKey) }}</small>
                                </div>
                              </div>
                              @error("payment_gateway_fees.$index.gateway")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </td>
                            <td>
                              <select class="form-select" name="payment_gateway_fees[{{ $index }}][country_id]" style="min-width: 220px;">
                                <option value="">اختر الدولة</option>
                                @foreach($countries as $country)
                                  <option value="{{ $country->id }}" @selected((string) $gatewayCountryId === (string) $country->id)>
                                    {{ $country->name }} - {{ $country->currency }}
                                  </option>
                                @endforeach
                              </select>
                              @error("payment_gateway_fees.$index.country_id")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </td>
                            <td>
                              <div class="input-group input-group-merge" style="min-width: 190px;">
                                <span class="input-group-text"><i class="ti tabler-cash"></i></span>
                                <input
                                  type="number"
                                  min="0"
                                  step="1"
                                  class="form-control"
                                  name="payment_gateway_fees[{{ $index }}][fixed_fee_minor]"
                                  value="{{ $fixedFeeMinor }}"
                                  placeholder="150"
                                >
                              </div>
                              <small class="text-muted">150 = 1.50 {{ $gatewayCurrency }}</small>
                              @error("payment_gateway_fees.$index.fixed_fee_minor")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </td>
                            <td>
                              <div class="input-group input-group-merge" style="min-width: 170px;">
                                <span class="input-group-text"><i class="ti tabler-percentage"></i></span>
                                <input
                                  type="number"
                                  min="0"
                                  max="100"
                                  step="0.01"
                                  class="form-control"
                                  name="payment_gateway_fees[{{ $index }}][commission_percent]"
                                  value="{{ $commissionPercent }}"
                                  placeholder="7"
                                >
                              </div>
                              @error("payment_gateway_fees.$index.commission_percent")
                                <div class="text-danger small mt-1">{{ $message }}</div>
                              @enderror
                            </td>
                            <td>
                              <div class="fw-semibold text-success">{{ number_format($fixedFeeMinor / 100, 2) }} {{ $gatewayCurrency }}</div>
                              <small class="text-muted">{{ rtrim(rtrim(number_format((float) $commissionPercent, 2, '.', ''), '0'), '.') }}%</small>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <div class="alert alert-info mt-3 mb-0">
                    <div class="d-flex align-items-start gap-2">
                      <i class="icon-base ti tabler-info-circle mt-1"></i>
                      <div>
                        يتم حفظ هذه القيم لاستخدام إدارة رسوم بوابات الدفع، ولا يتم إرسال جدول الرسوم والعمولات هذا داخل Endpoint إعدادات الرسوم الخاص بالموبايل.
                      </div>
                    </div>
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-primary">
                      <i class="icon-base ti tabler-device-floppy me-1"></i> حفظ رسوم وعمولات بوابات الدفع
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="tab-pane fade settings-pane settings-block" id="videos" data-section-label="الفيديو" role="tabpanel" aria-labelledby="videos-tab" tabindex="0">
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
                    <div class="col-md-6">
                      <label class="form-label">نص بانر المتدرب</label>
                      <textarea name="banner_text_student" class="form-control" rows="3" placeholder="اكتب النص الذي يظهر للمتدرب في البانر">{{ old('banner_text_student', $settings['home.banner.student_text'] ?? '') }}</textarea>
                      <small class="text-body-secondary d-block mt-1">يظهر في بانر الصفحة الرئيسية للمتدرب.</small>
                      @error('banner_text_student')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">نص بانر المدرب</label>
                      <textarea name="banner_text_trainer" class="form-control" rows="3" placeholder="اكتب النص الذي يظهر للمدرب في البانر">{{ old('banner_text_trainer', $settings['home.banner.trainer_text'] ?? '') }}</textarea>
                      <small class="text-body-secondary d-block mt-1">يظهر في بانر الصفحة الرئيسية للمدرب.</small>
                      @error('banner_text_trainer')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @enderror
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

          <div class="tab-pane fade settings-pane settings-block" id="pages" data-section-label="الصفحات" role="tabpanel" aria-labelledby="pages-tab" tabindex="0">
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
                  @php
                    $decodeFaq = static function ($value) {
                        if (!is_string($value) || trim($value) === '') {
                            return [];
                        }
                        $decoded = json_decode($value, true);
                        if (!is_array($decoded)) {
                            return [['question' => '', 'answer' => $value]];
                        }
                        return collect($decoded)
                            ->map(function ($row) {
                                if (!is_array($row)) {
                                    $answer = trim((string) $row);
                                    return $answer === '' ? null : ['question' => '', 'answer' => $answer];
                                }
                                $question = trim((string) ($row['question'] ?? ''));
                                $answer = trim((string) ($row['answer'] ?? ''));
                                return ($question === '' && $answer === '') ? null : [
                                    'question' => $question,
                                    'answer' => $answer,
                                ];
                            })
                            ->filter()
                            ->values()
                            ->all();
                    };
                    $faqPages = [
                        [
                            'key' => 'pages.usage',
                            'label' => 'سياسة الاستخدام',
                            'name' => 'page_usage_faqs',
                            'id' => 'usage-faq-list',
                        ],
                        [
                            'key' => 'pages.privacy',
                            'label' => 'سياسة الخصوصية',
                            'name' => 'page_privacy_faqs',
                            'id' => 'privacy-faq-list',
                        ],
                        [
                            'key' => 'pages.terms',
                            'label' => 'الشروط والأحكام (المستخدم)',
                            'name' => 'page_terms_faqs',
                            'id' => 'terms-faq-list',
                        ],
                        [
                            'key' => 'pages.terms_trainer',
                            'label' => 'الشروط والأحكام (المدربة)',
                            'name' => 'page_terms_trainer_faqs',
                            'id' => 'terms-trainer-faq-list',
                        ],
                        [
                            'key' => 'pages.about',
                            'label' => 'عن تطبيق دربيني',
                            'name' => 'page_about_faqs',
                            'id' => 'about-faq-list',
                        ],
                        [
                            'key' => 'pages.exchange',
                            'label' => 'سياسة الاستبدال',
                            'name' => 'page_exchange_faqs',
                            'id' => 'exchange-faq-list',
                        ],
                        [
                            'key' => 'pages.sales',
                            'label' => 'رسوم التطبيق (المستخدم)',
                            'name' => 'page_sales_faqs',
                            'id' => 'sales-faq-list',
                        ],
                        [
                            'key' => 'pages.sales_trainer',
                            'label' => 'رسوم التطبيق (المدربة)',
                            'name' => 'page_sales_trainer_faqs',
                            'id' => 'sales-trainer-faq-list',
                        ],
                        [
                            'key' => 'pages.app_usage_trainer',
                            'label' => 'شرح استخدام التطبيق للمدربة',
                            'name' => 'page_app_usage_trainer_faqs',
                            'id' => 'app-usage-trainer-faq-list',
                        ],
                        [
                            'key' => 'pages.app_usage_student',
                            'label' => 'شرح استخدام التطبيق للطالبة',
                            'name' => 'page_app_usage_student_faqs',
                            'id' => 'app-usage-student-faq-list',
                        ],
                        [
                            'key' => 'pages.faq',
                            'label' => 'الأسئلة الشائعة',
                            'name' => 'faqs',
                            'id' => 'faq-list',
                        ],
                    ];
                  @endphp
                  @foreach($faqPages as $page)
                    @php($items = $decodeFaq($settings[$page['key']] ?? ''))
                    @if (empty($items))
                      @php($items = [[ 'question' => '', 'answer' => '' ]])
                    @endif
                    <div class="mb-3">
                      <label class="form-label d-block">{{ $page['label'] }}</label>
                      <div id="{{ $page['id'] }}" class="d-flex flex-column gap-2 js-faq-list" data-name="{{ $page['name'] }}">
                        @foreach($items as $i => $faq)
                          <div class="border rounded p-2 faq-row">
                            <div class="row g-2 align-items-start">
                              <div class="col-md-5">
                                <label class="form-label">السؤال</label>
                                <div class="input-group input-group-merge">
                                  <span class="input-group-text"><i class="ti tabler-question-mark"></i></span>
                                  <input type="text" name="{{ $page['name'] }}[{{ $i }}][question]" class="form-control" value="{{ $faq['question'] ?? '' }}" placeholder="اكتب السؤال">
                                </div>
                              </div>
                              <div class="col-md-7">
                                <label class="form-label">الإجابة</label>
                                <div class="input-group input-group-merge">
                                  <span class="input-group-text"><i class="ti tabler-message"></i></span>
                                  <textarea name="{{ $page['name'] }}[{{ $i }}][answer]" class="form-control" rows="2" placeholder="اكتب الإجابة">{{ $faq['answer'] ?? '' }}</textarea>
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
                        <button type="button" class="btn btn-sm btn-outline-primary js-add-faq" data-target="#{{ $page['id'] }}"><i class="ti tabler-plus"></i> إضافة سؤال</button>
                      </div>
                    </div>
                  @endforeach
                  <div class="mb-3">
                    <label class="form-label">تواصل معنا</label>
                    <div class="input-group input-group-merge">
                      <span class="input-group-text"><i class="ti tabler-mail"></i></span>
                      <textarea
                        id="page_contact"
                        name="page_contact"
                        class="form-control"
                        rows="6"
                        placeholder="بيانات التواصل، البريد، الهاتف... ويمكن إدراج روابط HTML بسيطة"
                      >{{ $settings['pages.contact'] ?? '' }}</textarea>
                    </div>
                    <small class="text-muted d-block mt-2">يمكنك إدراج روابط قابلة للنقر مثل البريد الإلكتروني أو واتساب داخل النص نفسه.</small>
                  </div>
                  <div class="card border border-primary-subtle bg-label-primary mb-3">
                    <div class="card-body">
                      <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="avatar-initial rounded bg-label-primary">
                          <i class="icon-base ti tabler-link"></i>
                        </span>
                        <div>
                          <h6 class="mb-0">أداة إدراج رابط داخل النص</h6>
                          <small class="text-body-secondary">اختر نوع الرابط، اكتب النص الظاهر، ثم أدرجه داخل مربع "تواصل معنا".</small>
                        </div>
                      </div>
                      <div class="row g-3">
                        <div class="col-lg-3">
                          <label class="form-label">نوع الرابط</label>
                          <select id="contact-link-type" class="form-select">
                            <option value="email">إيميل</option>
                            <option value="whatsapp">واتساب</option>
                            <option value="custom">رابط مخصص</option>
                          </select>
                        </div>
                        <div class="col-lg-3">
                          <label class="form-label">النص الظاهر</label>
                          <input type="text" id="contact-link-text" class="form-control" placeholder="اكتب النص الذي سيضغط عليه المستخدم">
                        </div>
                        <div class="col-lg-4">
                          <label class="form-label">الرابط أو القيمة</label>
                          <input type="text" id="contact-link-value" class="form-control" placeholder="example@mail.com أو 9665XXXXXXXX أو https://example.com">
                        </div>
                        <div class="col-lg-2 d-grid">
                          <label class="form-label">&nbsp;</label>
                          <button type="button" class="btn btn-primary js-insert-contact-link">
                            <i class="icon-base ti tabler-plus me-1"></i> إدراج
                          </button>
                        </div>
                      </div>
                      <div class="mt-3">
                        <label class="form-label">معاينة</label>
                        <div id="contact-preview" class="border rounded p-3 bg-white text-break"></div>
                      </div>
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

          <div class="tab-pane fade settings-pane settings-block" id="roles" data-section-label="الأدوار والمحظورات" role="tabpanel" aria-labelledby="roles-tab" tabindex="0">
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

          <div class="tab-pane fade settings-pane settings-block" id="howitworks" data-section-label="كيف تعمل الخدمة" role="tabpanel" aria-labelledby="howitworks-tab" tabindex="0">
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
                  @php($hiw = \App\Models\HowItWorksSection::with('steps')->orderBy('position')->get())
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
                          <div class="d-flex justify-content-end mt-1">
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
  .settings-toolbar {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(25, 135, 84, 0.08));
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  }
  .settings-banner h5 {
    letter-spacing: 0.2px;
  }
  .settings-tabs {
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
  }
  .settings-tabs .nav-link {
    align-items: center;
    border: 1px solid rgba(79, 70, 229, 0.14);
    border-radius: 8px;
    color: #4b5563;
    display: inline-flex;
    font-weight: 600;
    min-height: 40px;
    white-space: nowrap;
  }
  .settings-tabs .nav-link.active {
    background: #4f46e5;
    border-color: #4f46e5;
    box-shadow: 0 6px 14px rgba(79, 70, 229, 0.2);
  }
  .settings-sections {
    scroll-behavior: smooth;
  }
  .settings-block {
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 12px;
    padding: 1rem;
    background: #fff;
    animation: paneFadeIn 0.2s ease;
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
  .report-exchange-rate-row {
    border: 1px dashed rgba(13, 110, 253, 0.18);
    border-radius: 14px;
    padding: 0.85rem;
    background: linear-gradient(180deg, rgba(13, 110, 253, 0.03), rgba(13, 110, 253, 0.01));
    margin: 0;
  }
  .is-invalid {
    border-color: #dc3545;
  }
  .is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
  }
  @keyframes paneFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @media (max-width: 1199.98px) {
    .settings-tabs .nav-item {
      flex: 1 1 calc(50% - 0.5rem);
    }
    .settings-tabs .nav-link {
      justify-content: center;
      width: 100%;
    }
    .settings-block {
      padding: 0.75rem;
    }
  }
</style>
@endpush

@push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = Array.from(document.querySelectorAll('#settings-tabs [data-bs-toggle="tab"]'));

    function showTab(tabButton) {
      if (!tabButton) return;

      if (window.bootstrap && window.bootstrap.Tab) {
        window.bootstrap.Tab.getOrCreateInstance(tabButton).show();
        return;
      }

      tabButtons.forEach(function (button) {
        const pane = document.querySelector(button.getAttribute('data-bs-target'));
        const isActive = button === tabButton;

        button.classList.toggle('active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');

        if (pane) {
          pane.classList.toggle('active', isActive);
          pane.classList.toggle('show', isActive);
        }
      });
    }

    function showTabForHash(hash, shouldScroll) {
      const target = hash ? document.getElementById(hash.replace('#', '')) : null;
      if (!target) return;

      const pane = target.classList.contains('tab-pane') ? target : target.closest('.tab-pane');
      if (!pane || !pane.id) return;

      const tabButton = document.querySelector(`#settings-tabs [data-bs-target="#${pane.id}"]`);
      showTab(tabButton);

      if (shouldScroll) {
        window.setTimeout(function () {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
      }
    }

    if (window.location.hash) {
      showTabForHash(window.location.hash, true);
    }

    tabButtons.forEach(function (button) {
      button.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');
        if (target && window.history.replaceState) {
          window.history.replaceState(null, '', target);
        }
      });
    });
  });
  </script>
@endpush

@push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('page_contact');
    const typeInput = document.getElementById('contact-link-type');
    const textInput = document.getElementById('contact-link-text');
    const valueInput = document.getElementById('contact-link-value');
    const preview = document.getElementById('contact-preview');
    const insertButton = document.querySelector('.js-insert-contact-link');

    if (!textarea || !typeInput || !textInput || !valueInput || !preview || !insertButton) {
      return;
    }

    function updateContactPreview() {
      preview.innerHTML = textarea.value || '<span class="text-muted">لا توجد معاينة بعد.</span>';
    }

    function buildContactLink(type, text, value) {
      const safeText = text.trim();
      const safeValue = value.trim();

      if (!safeText || !safeValue) {
        return '';
      }

      if (type === 'email') {
        return `<a href="mailto:${safeValue}">${safeText}</a>`;
      }

      if (type === 'whatsapp') {
        const normalizedNumber = safeValue.replace(/[^0-9]/g, '');
        return `<a href="https://wa.me/${normalizedNumber}">${safeText}</a>`;
      }

      return `<a href="${safeValue}" target="_blank" rel="noopener noreferrer">${safeText}</a>`;
    }

    insertButton.addEventListener('click', function () {
      const anchor = buildContactLink(typeInput.value, textInput.value, valueInput.value);
      if (!anchor) {
        return;
      }

      const start = textarea.selectionStart ?? textarea.value.length;
      const end = textarea.selectionEnd ?? textarea.value.length;
      const currentValue = textarea.value;
      textarea.value = currentValue.slice(0, start) + anchor + currentValue.slice(end);
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = start + anchor.length;
      updateContactPreview();
    });

    textarea.addEventListener('input', updateContactPreview);
    updateContactPreview();
  });
  </script>
@endpush

@push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    function reindexFaq(list){
      if (!list) return;
      const name = list.dataset.name || 'faqs';
      list.querySelectorAll('.faq-row').forEach((row, idx) => {
        const question = row.querySelector('input');
        const answer = row.querySelector('textarea');
        if (question) question.name = `${name}[${idx}][question]`;
        if (answer) answer.name = `${name}[${idx}][answer]`;
      });
    }

    function addFaqRow(list, q = '', a = ''){
      if (!list) return;
      const name = list.dataset.name || 'faqs';
      const idx = list.querySelectorAll('.faq-row').length;
      const html = `
        <div class="border rounded p-2 faq-row">
          <div class="row g-2 align-items-start">
            <div class="col-md-5">
              <label class="form-label">السؤال</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-question-mark"></i></span>
                <input type="text" name="${name}[${idx}][question]" class="form-control" placeholder="اكتب السؤال"/>
              </div>
            </div>
            <div class="col-md-7">
              <label class="form-label">الإجابة</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-message"></i></span>
                <textarea name="${name}[${idx}][answer]" class="form-control" rows="2" placeholder="اكتب الإجابة"></textarea>
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
      list.appendChild(row);
      reindexFaq(list);
    }

    document.addEventListener('click', function(e){
      const addBtn = e.target.closest('.js-add-faq');
      if (addBtn) {
        const list = document.querySelector(addBtn.dataset.target);
        addFaqRow(list);
      }
      const removeBtn = e.target.closest('.js-remove-faq');
      if (removeBtn) {
        const row = removeBtn.closest('.faq-row');
        const list = removeBtn.closest('.js-faq-list');
        if (!row || !list) return;
        const rows = list.querySelectorAll('.faq-row');
        if (rows.length > 1) {
          row.remove();
        } else {
          row.querySelectorAll('input,textarea').forEach((f) => { f.value = ''; });
        }
        reindexFaq(list);
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
    const list = document.getElementById('report-exchange-rates-list');
    if (!list) return;

    function reindexExchangeRates() {
      list.querySelectorAll('.report-exchange-rate-row').forEach((row, index) => {
        const currencyInput = row.querySelector('.js-exchange-currency');
        const rateInput = row.querySelector('.js-exchange-rate');

        if (currencyInput) {
          currencyInput.name = `report_exchange_rates[${index}][currency]`;
        }

        if (rateInput) {
          rateInput.name = `report_exchange_rates[${index}][rate]`;
        }
      });
    }

    function addExchangeRateRow(currency = '', rate = '') {
      const row = document.createElement('div');
      row.className = 'row g-2 align-items-end report-exchange-rate-row';
      row.innerHTML = `
        <div class="col-lg-4">
          <label class="form-label fw-semibold">رمز العملة</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-currency"></i></span>
            <input type="text" class="form-control js-exchange-currency" maxlength="3" placeholder="EGP" style="text-transform: uppercase">
          </div>
        </div>
        <div class="col-lg-5">
          <label class="form-label fw-semibold">قيمة 1 من العملة بالـ {{ $reportCurrency }}</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="ti tabler-calculator"></i></span>
            <input type="number" step="0.000001" min="0.000001" class="form-control js-exchange-rate" placeholder="0.075000">
          </div>
        </div>
        <div class="col-lg-3 d-grid">
          <button type="button" class="btn btn-outline-danger js-remove-exchange-rate">
            <i class="icon-base ti tabler-trash me-1"></i> حذف
          </button>
        </div>
      `;

      row.querySelector('.js-exchange-currency').value = currency;
      row.querySelector('.js-exchange-rate').value = rate;
      list.appendChild(row);
      reindexExchangeRates();
    }

    document.addEventListener('click', function(e){
      const addButton = e.target.closest('.js-add-exchange-rate');
      if (addButton) {
        addExchangeRateRow();
      }

      const removeButton = e.target.closest('.js-remove-exchange-rate');
      if (removeButton) {
        const row = removeButton.closest('.report-exchange-rate-row');
        const rows = list.querySelectorAll('.report-exchange-rate-row');

        if (!row) return;

        if (rows.length > 1) {
          row.remove();
        } else {
          row.querySelectorAll('input').forEach((input) => { input.value = ''; });
        }

        reindexExchangeRates();
      }
    });

    document.addEventListener('input', function(e){
      const currencyInput = e.target.closest('.js-exchange-currency');
      if (currencyInput) {
        currencyInput.value = currencyInput.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 3);
      }
    });

    reindexExchangeRates();
  });
  </script>
@endpush

@push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    function reindexHiw() {
      const sections = document.querySelectorAll('#hiw-list .hiw-row');
      sections.forEach((sec, i) => {
        const titleInput = sec.querySelector('input[name*="[title]"]');
        if (titleInput) titleInput.name = `sections[${i}][title]`;

        const steps = sec.querySelectorAll('.steps .input-group');
        steps.forEach((row, j) => {
          const input = row.querySelector('input');
          if (input) input.name = `sections[${i}][steps][${j}]`;
        });
      });
    }

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
      reindexHiw();
    }

    document.addEventListener('click', function(e){
      if (e.target.closest('.js-hiw-add-section')) addHiwSection();
      if (e.target.closest('.js-hiw-remove-section')) {
        const row = e.target.closest('.hiw-row');
        const all = document.querySelectorAll('#hiw-list .hiw-row');
        if (all.length > 1) row.remove();
        reindexHiw();
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
        reindexHiw();
      }
      if (e.target.closest('.js-hiw-remove-step')) {
        const row = e.target.closest('.input-group');
        const steps = e.target.closest('.steps');
        if (steps.querySelectorAll('.input-group').length > 1) row.remove();
        reindexHiw();
      }
    });

    reindexHiw();

    const hiwList = document.getElementById('hiw-list');
    const hiwForm = hiwList ? hiwList.closest('form') : null;
    if (hiwForm) {
      hiwForm.addEventListener('submit', function () {
        reindexHiw();
      });
    }
  });
  </script>
@endpush

@endsection
