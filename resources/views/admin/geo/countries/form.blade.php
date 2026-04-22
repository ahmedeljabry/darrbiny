@php($isEdit = isset($country) && $country)
@php($action = $isEdit ? route('admin.geo.countries.update', $country->id) : route('admin.geo.countries.store'))
@php($currentExchangeRate = $currentExchangeRate ?? null)

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

<form method="post" action="{{ $action }}" class="geo-country-form">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row g-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header border-0 d-flex align-items-center gap-3 pb-3">
          <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
            <i class="icon-base ti tabler-world" style="font-size: 24px;"></i>
          </span>
          <div>
            <h5 class="mb-0 fw-bold">تفاصيل الدولة</h5>
            <small class="text-muted">معلومات الدولة الأساسية</small>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">رمز ISO2 <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-flag"></i></span>
                <input type="text" name="iso2" class="form-control @error('iso2') is-invalid @enderror" maxlength="2" placeholder="SA" value="{{ old('iso2', $isEdit ? $country->iso2 : '') }}" required>
              </div>
              @error('iso2')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">رمز الدولة المكون من حرفين (مثل: SA, US)</small>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">العملة <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-currency-dollar"></i></span>
                <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror" maxlength="3" placeholder="SAR" value="{{ old('currency', $isEdit ? $country->currency : '') }}" required>
              </div>
              @error('currency')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">رمز العملة المكون من 3 أحرف (مثل: SAR, USD)</small>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">الاسم <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti tabler-edit"></i></span>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="السعودية" value="{{ old('name', $isEdit ? $country->name : '') }}" required>
              </div>
              @error('name')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">اسم الدولة بالكامل</small>
            </div>
            <div class="col-12">
              <div class="alert alert-info mb-0">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold mb-1">إدارة سعر الصرف من الإعدادات</div>
                    <div class="small">
                      يتم أخذ كل معدلات التحويل المستخدمة في النظام من صفحة
                      <a href="{{ route('admin.settings.index') }}#financial-rates" class="fw-semibold">الإعدادات &gt; التحويل</a>.
                      @if($isEdit)
                        @if($currentExchangeRate)
                          السعر الحالي لهذه العملة: <strong>{{ $currentExchangeRate }} {{ \App\Support\ReportCurrencyConverter::REPORT_CURRENCY }}</strong>.
                        @else
                          لا يوجد سعر صرف محفوظ لهذه العملة حتى الآن.
                        @endif
                      @endif
                    </div>
                  </div>
                  <a href="{{ route('admin.settings.index') }}#financial-rates" class="btn btn-outline-info">
                    <i class="icon-base ti tabler-exchange me-1"></i> فتح إعدادات التحويل
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.geo.index') }}" class="btn btn-outline-secondary">
          <i class="icon-base ti tabler-arrow-right me-1"></i> عودة
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="icon-base ti tabler-device-floppy me-1"></i> {{ $isEdit ? 'حفظ الدولة' : 'حفظ' }}
        </button>
      </div>
    </div>
  </div>
</form>
