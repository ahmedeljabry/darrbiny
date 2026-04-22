<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use App\Models\Setting;
use App\Support\ReportCurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;

class GeoAdminController extends BaseController
{
    public function index()
    {
        $q = request('q');
        $exchangeRates = $this->reportExchangeRates();
        $countries = Country::query()
            ->when($q, fn($qq)=>$qq->where(function($w) use ($q){
                $w->where('name','like',"%$q%")
                  ->orWhere('iso2','like',"%$q%")
                  ->orWhere('currency','like',"%$q%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        return view('admin.geo.index', compact('countries', 'exchangeRates'));
    }

    public function storeCountry(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
            'exchange_rate_to_sar' => ['nullable', 'numeric', 'gt:0'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
            'exchange_rate_to_sar.numeric' => 'سعر الصرف يجب أن يكون رقمًا صحيحًا أو عشريًا',
            'exchange_rate_to_sar.gt' => 'سعر الصرف يجب أن يكون أكبر من صفر',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        $this->ensureExchangeRateIsConfigured($data['currency'], $data['exchange_rate_to_sar'] ?? null);

        Country::create(collect($data)->only(['name', 'iso2', 'currency'])->toArray());
        $this->persistExchangeRate($data['currency'], $data['exchange_rate_to_sar'] ?? null);

        return redirect()->route('admin.geo.index')->with('status', 'تم إضافة الدولة بنجاح');
    }

    public function updateCountry(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
            'exchange_rate_to_sar' => ['nullable', 'numeric', 'gt:0'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
            'exchange_rate_to_sar.numeric' => 'سعر الصرف يجب أن يكون رقمًا صحيحًا أو عشريًا',
            'exchange_rate_to_sar.gt' => 'سعر الصرف يجب أن يكون أكبر من صفر',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        $this->ensureExchangeRateIsConfigured($data['currency'], $data['exchange_rate_to_sar'] ?? null);

        $country = Country::findOrFail($id);
        $country->update(collect($data)->only(['name','iso2','currency'])->toArray());
        $this->persistExchangeRate($data['currency'], $data['exchange_rate_to_sar'] ?? null);

        return redirect()->route('admin.geo.countries.edit', $country->id)->with('status', 'تم حفظ الدولة بنجاح');
    }

    public function destroyCountry(string $id)
    {
        Country::findOrFail($id)->delete();
        return redirect()->route('admin.geo.index')->with('status', 'تم حذف الدولة');
    }

    public function createCountry()
    {
        return view('admin.geo.countries.create', [
            'currentExchangeRate' => old('exchange_rate_to_sar'),
        ]);
    }

    public function editCountry(string $id)
    {
        $country = Country::findOrFail($id);
        $currentExchangeRate = old('exchange_rate_to_sar', $this->exchangeRateForCurrency($country->currency));

        return view('admin.geo.countries.edit', compact('country', 'currentExchangeRate'));
    }

    private function ensureExchangeRateIsConfigured(string $currency, mixed $exchangeRate): void
    {
        if ($currency === ReportCurrencyConverter::REPORT_CURRENCY) {
            return;
        }

        if ($this->normalizeExchangeRate($exchangeRate) !== null) {
            return;
        }

        if ($this->exchangeRateForCurrency($currency) !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'exchange_rate_to_sar' => 'أدخل سعر الصرف لهذه العملة مقابل الريال السعودي حتى تعمل التقارير والحجوزات بشكل صحيح.',
        ]);
    }

    private function reportExchangeRates(): array
    {
        $stored = Setting::query()->where('key', ReportCurrencyConverter::SETTINGS_KEY)->value('value');
        $decoded = json_decode(is_string($stored) ? $stored : '[]', true);
        $decoded = is_array($decoded) ? $decoded : [];

        return collect($decoded)
            ->mapWithKeys(function ($value, $currency): array {
                $currency = strtoupper(trim((string) $currency));
                $rate = is_numeric($value) ? round((float) $value, 6) : null;

                if ($currency === '' || $rate === null || $rate <= 0) {
                    return [];
                }

                return [$currency => $rate];
            })
            ->all();
    }

    private function exchangeRateForCurrency(?string $currency): ?string
    {
        $currency = strtoupper(trim((string) $currency));
        $rate = $this->reportExchangeRates()[$currency] ?? null;

        if ($rate === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $rate, 6, '.', ''), '0'), '.');
    }

    private function persistExchangeRate(string $currency, mixed $exchangeRate): void
    {
        $currency = strtoupper(trim((string) $currency));
        $rate = $this->normalizeExchangeRate($exchangeRate);

        if ($currency === '' || $currency === ReportCurrencyConverter::REPORT_CURRENCY || $rate === null) {
            return;
        }

        $rates = $this->reportExchangeRates();
        $rates[$currency] = $rate;
        ksort($rates);

        Setting::query()->updateOrCreate(
            ['key' => ReportCurrencyConverter::SETTINGS_KEY],
            ['value' => json_encode($rates, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function normalizeExchangeRate(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $rate = round((float) $value, 6);

        return $rate > 0 ? $rate : null;
    }
}
