<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">العنوان</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-edit"></i></span>
            <input type="text" name="title" class="form-control" value="{{ old('title', $plan->title ?? '') }}"
                required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">المستوى</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-layers"></i></span>
            <input type="text" name="level" class="form-control" value="{{ old('level', $plan->level ?? '') }}">
        </div>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label">الوصف</label>
    <div class="input-group input-group-merge">
        <span class="input-group-text"><i class="icon-base ti tabler-message-dots"></i></span>
        <textarea name="description" class="form-control"
            rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">السعر الأدنى</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-currency-dollar"></i></span>
            <input type="number" step="0.01" min="0" name="price_min" class="form-control"
                value="{{ old('price_min', $plan->price_min ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">الدفعة الابتدائية</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-wallet"></i></span>
            <input type="number" step="0.01" min="0" name="deposit_amount" class="form-control"
                value="{{ old('deposit_amount', $plan->deposit_amount ?? '') }}">
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">شارة الخصم</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-discount-2"></i></span>
            <input type="text" name="badge_discount" class="form-control"
                value="{{ old('badge_discount', $plan->badge_discount ?? '') }}">
        </div>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-4">
        <label class="form-label">الأيام</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-calendar"></i></span>
            <input type="text" name="duration_days" class="form-control"
                value="{{ old('duration_days', $plan->duration_days ?? '') }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">عدد الساعات</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-clock-hour-3"></i></span>
            <input type="number" min="0" name="hours_count" class="form-control"
                value="{{ old('hours_count', $plan->hours_count ?? '') }}">
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">عدد الجلسات</label>
        <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-list-numbers"></i></span>
            <input type="number" min="0" name="session_count" class="form-control"
                value="{{ old('session_count', $plan->session_count ?? '') }}">
        </div>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-6">
        <label class="form-label">الدولة</label>
        <div class="input-group input-group-merge">
            <select name="country_id" class="form-select select2 js-country" required style="width: 100%"
                data-cities-url="{{ route('admin.countries.cities', 'COUNTRY_ID') }}">
                <option value="">— اختر الدولة —</option>
                @foreach(($countries ?? []) as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id', $plan->country_id ?? '') === $country->id)>
                        {{ $country->name }} ({{ $country->iso2 }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">المدينة</label>
        <div class="input-group input-group-merge">
            <select name="city_id" class="form-select select2 js-city" required style="width: 100%">
                <option value="">— اختر المدينة —</option>
                @foreach(($cities ?? []) as $city)
                    <option value="{{ $city->id }}" @selected(old('city_id', $plan->city_id ?? '') === $city->id)>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
        </div>
  </div>
</div>

<div class="mt-3">
  <div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))>
    <label class="form-check-label">نشطة</label>
  </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && $.fn.select2) {
                const dir = @json(app()->getLocale() === 'en' ? 'ltr' : 'rtl');
                $('.select2').select2({ dir: dir, width: '100%' });

                function loadCities(countryId, selectedCityId = null) {
                    const $city = $('.js-city');
                    const $country = $('.js-country');
                    const urlTpl = $country.data('cities-url');
                    if (!countryId || !urlTpl) {
                        $city.empty().append(new Option('— اختر المدينة —', ''));
                        $city.val(null).trigger('change');
                        return;
                    }
                    const url = String(urlTpl).replace('COUNTRY_ID', countryId);
                    $.getJSON(url).done(function (resp) {
                        const items = (resp && resp.data) ? resp.data : [];
                        $city.empty().append(new Option('— اختر المدينة —', ''));
                        items.forEach(function (c) {
                            const opt = new Option(c.name, c.id, false, selectedCityId === c.id);
                            $city.append(opt);
                        });
                        $city.trigger('change');
                    }).fail(function () {
                        console.error('Failed to load cities for country', countryId);
                    });
                }

                $('.js-country').on('change', function () {
                    const countryId = $(this).val();
                    loadCities(countryId);
                });

                const initialCountry = $('.js-country').val();
                const existingCity = @json(old('city_id', $plan->city_id ?? null));
                if (initialCountry && $('.js-city option').length <= 1) {
                    loadCities(initialCountry, existingCity);
                }
            }

            // features JS moved to features_card partial
        });
    </script>
@endpush
