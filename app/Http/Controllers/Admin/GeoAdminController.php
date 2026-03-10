<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class GeoAdminController extends BaseController
{
    public function index()
    {
        $q = request('q');
        $countries = Country::query()
            ->when($q, fn($qq)=>$qq->where(function($w) use ($q){
                $w->where('name','like',"%$q%")
                  ->orWhere('iso2','like',"%$q%")
                  ->orWhere('currency','like',"%$q%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        return view('admin.geo.index', compact('countries'));
    }

    public function storeCountry(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        Country::create($data);
        return redirect()->route('admin.geo.index')->with('status', 'تم إضافة الدولة بنجاح');
    }

    public function updateCountry(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
        ], [
            'name.required' => 'اسم الدولة مطلوب',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 120 حرفاً',
            'iso2.required' => 'رمز ISO2 مطلوب',
            'iso2.size' => 'رمز ISO2 يجب أن يكون حرفين بالضبط',
            'currency.required' => 'العملة مطلوبة',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف بالضبط',
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);

        $country = Country::findOrFail($id);
        $country->update(collect($data)->only(['name','iso2','currency'])->toArray());

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
        return view('admin.geo.countries.edit', compact('country'));
    }
}
