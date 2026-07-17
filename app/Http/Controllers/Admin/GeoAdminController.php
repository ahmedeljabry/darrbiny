<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use App\Support\ReportCurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class GeoAdminController extends BaseController
{
    public function index()
    {
        $q = request('q');
        $exchangeRates = collect(app(ReportCurrencyConverter::class)->rates())
            ->except([ReportCurrencyConverter::REPORT_CURRENCY])
            ->all();
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
            'vat_percent' => ['nullable','numeric','min:0','max:100'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
            'vat_percent.numeric' => 'نسبة الضريبة يجب أن تكون رقماً',
            'vat_percent.min' => 'نسبة الضريبة لا يمكن أن تكون أقل من صفر',
            'vat_percent.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        $data['vat_percent'] = round((float) ($data['vat_percent'] ?? 0), 2);
        Country::create($data);

        return redirect()->route('admin.geo.index')->with(
            'status',
            'تم إضافة الدولة بنجاح. إذا كانت العملة جديدة، أضف سعر الصرف من الإعدادات > التحويل.'
        );
    }

    public function updateCountry(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
            'vat_percent' => ['nullable','numeric','min:0','max:100'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
            'vat_percent.numeric' => 'نسبة الضريبة يجب أن تكون رقماً',
            'vat_percent.min' => 'نسبة الضريبة لا يمكن أن تكون أقل من صفر',
            'vat_percent.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        $data['vat_percent'] = round((float) ($data['vat_percent'] ?? 0), 2);

        $country = Country::findOrFail($id);
        $country->update(collect($data)->only(['name','iso2','currency','vat_percent'])->toArray());

        return redirect()->route('admin.geo.countries.edit', $country->id)->with('status', 'تم حفظ الدولة بنجاح');
    }

    public function destroyCountry(string $id)
    {
        Country::findOrFail($id)->delete();
        return redirect()->route('admin.geo.index')->with('status', 'تم حذف الدولة');
    }

    public function createCountry()
    {
        return view('admin.geo.countries.create');
    }

    public function editCountry(string $id)
    {
        $country = Country::findOrFail($id);
        $exchangeRates = collect(app(ReportCurrencyConverter::class)->rates())
            ->except([ReportCurrencyConverter::REPORT_CURRENCY])
            ->all();
        $currentExchangeRate = $country->currency === ReportCurrencyConverter::REPORT_CURRENCY
            ? '1.000000'
            : (isset($exchangeRates[$country->currency]) ? number_format((float) $exchangeRates[$country->currency], 6, '.', '') : null);

        return view('admin.geo.countries.edit', compact('country', 'currentExchangeRate'));
    }
}
