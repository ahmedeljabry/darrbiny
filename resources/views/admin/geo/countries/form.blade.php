@php($isEdit = isset($country) && $country)
@php($action = $isEdit ? route('admin.geo.countries.update', $country->id) : route('admin.geo.countries.store'))

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
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header border-0 d-flex align-items-center justify-content-between pb-3">
            <div class="d-flex align-items-center gap-3">
              <span class="avatar-initial rounded bg-label-info" style="width: 48px; height: 48px;">
                <i class="icon-base ti tabler-map-pin" style="font-size: 24px;"></i>
              </span>
              <div>
                <h5 class="mb-0 fw-bold">مدن الدولة</h5>
                <small class="text-muted">إدارة مدن الدولة</small>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-primary js-gpt-generate-cities">
                <i class="ti tabler-sparkles me-1"></i> توليد المدن بـ GPT
              </button>
              <button type="button" class="btn btn-sm btn-primary js-add-city">
                <i class="ti tabler-plus me-1"></i> إضافة مدينة
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="alert alert-primary d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
              <div>
                <strong class="d-block">توليد مدن تلقائي</strong>
                <small class="text-muted">اضغط زر GPT لتوليد قائمة مدن الدولة تلقائياً ثم راجعها قبل الحفظ.</small>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="replaceGeneratedCities">
                <label class="form-check-label" for="replaceGeneratedCities">استبدال المدن الجديدة الحالية</label>
              </div>
            </div>

            <div id="gpt-cities-status" class="small text-muted mb-3"></div>

            <div id="cities-list" class="d-flex flex-column gap-2 mb-4">
              @php($oldNew = old('new_cities', []))
              @if(is_array($oldNew) && count($oldNew))
                @foreach($oldNew as $val)
                  <div class="row g-2 align-items-center city-row">
                    <div class="col">
                      <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>
                        <input type="text" name="new_cities[]" class="form-control @error('new_cities.*') is-invalid @enderror" placeholder="اسم مدينة جديدة" value="{{ $val }}">
                      </div>
                      @error('new_cities.*')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @enderror
                    </div>
                    <div class="col-auto">
                      <button type="button" class="btn btn-outline-danger btn-sm js-remove-city" title="حذف">
                        <i class="ti tabler-x"></i>
                      </button>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="row g-2 align-items-center city-row">
                  <div class="col">
                    <div class="input-group input-group-merge">
                      <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>
                      <input type="text" name="new_cities[]" class="form-control" placeholder="اسم مدينة جديدة">
                    </div>
                  </div>
                  <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm js-remove-city" title="حذف">
                      <i class="ti tabler-x"></i>
                    </button>
                  </div>
                </div>
              @endif
            </div>

            @if($isEdit && isset($cities) && $cities->count())
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 80%;"><i class="icon-base ti tabler-map-pin me-1"></i> المدينة</th>
                      <th class="text-center" style="width: 20%;"><i class="icon-base ti tabler-trash me-1"></i> حذف</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($cities as $city)
                      <tr>
                        <td>
                          <input type="text" name="cities[{{ $city->id }}]" value="{{ old('cities.'.$city->id, $city->name) }}" class="form-control @error('cities.'.$city->id) is-invalid @enderror">
                          @error('cities.'.$city->id)
                            <div class="text-danger small mt-1">{{ $message }}</div>
                          @enderror
                        </td>
                        <td class="text-center">
                          <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="delete_cities[]" value="{{ $city->id }}" id="delete_city_{{ $city->id }}">
                            <label class="form-check-label" for="delete_city_{{ $city->id }}"></label>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @if(method_exists($cities, 'links') && $cities->hasPages())
                <div class="mt-3">{{ $cities->links() }}</div>
              @endif
            @endif
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('admin.geo.index') }}" class="btn btn-outline-secondary">
            <i class="icon-base ti tabler-arrow-right me-1"></i> عودة
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ti tabler-device-floppy me-1"></i> {{ $isEdit ? 'حفظ الدولة والمدن' : 'حفظ' }}
          </button>
        </div>
      </div>
  </div>
</form>

@push('styles')
<style>
  .geo-country-form .card {
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 12px;
  }
  .geo-country-form .card-header {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.04), rgba(13, 202, 240, 0.04));
  }
  .geo-country-form .city-row .form-control {
    min-height: 42px;
  }
  .geo-country-form .js-gpt-generate-cities {
    border-style: dashed;
  }
  .geo-country-form #gpt-cities-status {
    min-height: 20px;
  }
  @media (max-width: 768px) {
    .geo-country-form .card-header {
      gap: 0.75rem;
    }
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const generateButton = document.querySelector('.js-gpt-generate-cities');
  const statusBox = document.getElementById('gpt-cities-status');
  const replaceCheckbox = document.getElementById('replaceGeneratedCities');
  const generateEndpoint = @json(route('admin.geo.cities.generate'));
  const csrfToken = @json(csrf_token());

  function normalizeCityName(value) {
    return (value || '')
      .toString()
      .trim()
      .replace(/\s+/g, ' ')
      .toLowerCase();
  }

  function setStatus(message, tone = 'muted') {
    if (!statusBox) return;
    const tones = {
      muted: 'text-muted',
      success: 'text-success',
      danger: 'text-danger',
      warning: 'text-warning',
      info: 'text-info',
    };
    statusBox.className = `small mb-3 ${tones[tone] ?? tones.muted}`;
    statusBox.textContent = message;
  }

  function addCityRow(val = ''){
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center city-row';
    row.innerHTML = `
      <div class="col">
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="ti tabler-map-pin"></i></span>
          <input type="text" name="new_cities[]" class="form-control" placeholder="اسم مدينة جديدة"/>
        </div>
      </div>
      <div class="col-auto">
        <button type="button" class="btn btn-outline-danger btn-sm js-remove-city" title="حذف">
          <i class="ti tabler-x"></i>
        </button>
      </div>
    `;
    row.querySelector('input').value = val;
    document.getElementById('cities-list').appendChild(row);
    return row;
  }

  function clearNewCityRows() {
    document.querySelectorAll('#cities-list .city-row').forEach((row) => row.remove());
  }

  function collectExistingCityNames(includeNewDraft = true) {
    const names = new Set();
    document.querySelectorAll('input[name^="cities["]').forEach((input) => {
      const normalized = normalizeCityName(input.value);
      if (normalized !== '') names.add(normalized);
    });
    if (includeNewDraft) {
      document.querySelectorAll('input[name="new_cities[]"]').forEach((input) => {
        const normalized = normalizeCityName(input.value);
        if (normalized !== '') names.add(normalized);
      });
    }
    return names;
  }

  async function generateCitiesWithGpt() {
    if (!generateButton) return;

    const nameInput = document.querySelector('input[name="name"]');
    const iso2Input = document.querySelector('input[name="iso2"]');
    const currencyInput = document.querySelector('input[name="currency"]');

    const countryName = (nameInput?.value || '').trim();
    const iso2 = (iso2Input?.value || '').trim().toUpperCase();
    const currency = (currencyInput?.value || '').trim().toUpperCase();

    if (countryName === '') {
      setStatus('يرجى إدخال اسم الدولة أولاً قبل التوليد.', 'warning');
      nameInput?.focus();
      return;
    }

    const shouldReplace = !!replaceCheckbox?.checked;
    const existingNames = collectExistingCityNames(!shouldReplace);

    setStatus('جاري توليد المدن من GPT...', 'info');
    generateButton.disabled = true;
    generateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جاري التوليد...';

    try {
      const response = await fetch(generateEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          name: countryName,
          iso2: iso2,
          currency: currency,
        }),
      });

      const payload = await response.json();
      if (!response.ok) {
        const message = payload?.message || payload?.errors?.name?.[0] || 'فشل توليد المدن.';
        throw new Error(message);
      }

      const rawCities = Array.isArray(payload?.data?.cities) ? payload.data.cities : [];
      const normalizedUnique = [];
      const seen = new Set();
      for (const city of rawCities) {
        const value = (city || '').toString().trim().replace(/\s+/g, ' ');
        const key = normalizeCityName(value);
        if (value !== '' && !seen.has(key)) {
          seen.add(key);
          normalizedUnique.push(value);
        }
      }

      const filtered = normalizedUnique.filter((city) => !existingNames.has(normalizeCityName(city)));

      if (shouldReplace) {
        clearNewCityRows();
      }

      if (filtered.length === 0) {
        if (document.querySelectorAll('#cities-list .city-row').length === 0) {
          addCityRow('');
        }
        setStatus('تم التوليد لكن كل المدن موجودة مسبقاً في القائمة.', 'warning');
        return;
      }

      filtered.forEach((city) => addCityRow(city));
      setStatus(`تمت إضافة ${filtered.length} مدينة بنجاح من GPT.`, 'success');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'حدث خطأ أثناء توليد المدن.', 'danger');
    } finally {
      generateButton.disabled = false;
      generateButton.innerHTML = '<i class="ti tabler-sparkles me-1"></i> توليد المدن بـ GPT';
    }
  }
  
  document.addEventListener('click', function(e){
    if (e.target.closest('.js-add-city')) {
      addCityRow('');
    }
    if (e.target.closest('.js-remove-city')) {
      const rows = document.querySelectorAll('#cities-list .city-row');
      const row = e.target.closest('.city-row');
      if (rows.length > 1) {
        row.remove();
      } else {
        row.querySelector('input').value = '';
      }
    }
    if (e.target.closest('.js-gpt-generate-cities')) {
      generateCitiesWithGpt();
    }
  });
});
</script>
@endpush
