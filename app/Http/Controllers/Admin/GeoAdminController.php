<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function cities(string $countryId): JsonResponse
    {
        $cities = City::where('country_id', $countryId)
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json(['data' => $cities]);
    }

    public function storeCountry(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
            'cities' => ['nullable','array'],
            'cities.*' => ['nullable','string','max:120'],
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);
        $cities = $data['cities'] ?? null;
        unset($data['cities']);
        $country = Country::create($data);
        if (is_array($cities)) {
            $toCreate = collect($cities)
                ->map(fn($n) => trim((string)$n))
                ->filter()
                ->unique()
                ->map(fn($name) => [
                    'id' => (string) Str::uuid(),
                    'country_id' => $country->id,
                    'name' => $name,
                ])
                ->values()
                ->all();
            if (!empty($toCreate)) City::insert($toCreate);
        }
        return redirect()->route('admin.geo.index')->with('status', 'تم إضافة الدولة');
    }

    public function updateCountry(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'iso2' => ['required','string','size:2'],
            'currency' => ['required','string','size:3'],
            'cities' => ['nullable','array'],
            'cities.*' => ['nullable','string','max:120'],
            'new_cities' => ['nullable','array'],
            'new_cities.*' => ['nullable','string','max:120'],
            'delete_cities' => ['nullable','array'],
            'delete_cities.*' => ['nullable','uuid'],
        ]);
        $data['iso2'] = strtoupper($data['iso2']);
        $data['currency'] = strtoupper($data['currency']);

        $country = Country::findOrFail($id);
        $country->update(collect($data)->only(['name','iso2','currency'])->toArray());

        // Update existing cities
        if (!empty($data['cities']) && is_array($data['cities'])) {
            foreach ($data['cities'] as $cityId => $name) {
                $name = trim((string) $name);
                if ($name === '') { continue; }
                City::where('id', $cityId)->where('country_id', $country->id)->update(['name' => $name]);
            }
        }

        if (!empty($data['delete_cities']) && is_array($data['delete_cities'])) {
            City::whereIn('id', $data['delete_cities'])->where('country_id', $country->id)->delete();
        }

        if (!empty($data['new_cities']) && is_array($data['new_cities'])) {
            $toCreate = collect($data['new_cities'])
                ->map(fn($n) => trim((string)$n))
                ->filter()
                ->unique()
                ->map(fn($name) => [
                    'id' => (string) Str::uuid(),
                    'country_id' => $country->id,
                    'name' => $name,
                ])
                ->values()
                ->all();
            if (!empty($toCreate)) City::insert($toCreate);
        }

        return redirect()->route('admin.geo.countries.edit', $country->id)->with('status', 'تم حفظ الدولة والمدن');
    }

    public function destroyCountry(string $id)
    {
        Country::findOrFail($id)->delete();
        return redirect()->route('admin.geo.index')->with('status', 'تم حذف الدولة');
    }

    public function storeCities(Request $request, string $countryId)
    {
        $country = Country::findOrFail($countryId);
        $data = $request->validate([
            'cities' => ['required','array','min:1'],
            'cities.*' => ['nullable','string','max:120'],
        ]);
        $toCreate = collect($data['cities'])
            ->map(fn($n) => trim((string)$n))
            ->filter()
            ->unique()
            ->map(fn($name) => [
                'id' => (string) Str::uuid(),
                'country_id' => $country->id,
                'name' => $name,
            ])
            ->values()
            ->all();
        if (!empty($toCreate)) City::insert($toCreate);
        return back()->with('status', 'تم إضافة المدن');
    }

    public function updateCity(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
        ]);
        $city = City::findOrFail($id);
        $city->update($data);
        return back()->with('status', 'تم تحديث المدينة');
    }

    public function destroyCity(string $id)
    {
        City::findOrFail($id)->delete();
        return back()->with('status', 'تم حذف المدينة');
    }

    public function createCountry()
    {
        return view('admin.geo.countries.create');
    }

    public function editCountry(string $id)
    {
        $country = Country::findOrFail($id);
        $cities = City::where('country_id', $id)->orderBy('name')->paginate(30);
        return view('admin.geo.countries.edit', compact('country','cities'));
    }
}
