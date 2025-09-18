<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Egypt', 'iso2' => 'EG', 'currency' => 'EGP', 'cities' => ['Cairo','Alexandria']],
            ['name' => 'Saudi Arabia', 'iso2' => 'SA', 'currency' => 'SAR', 'cities' => ['Riyadh','Jeddah']],
            ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'currency' => 'AED', 'cities' => ['Dubai','Abu Dhabi']],
        ];
        foreach ($data as $row) {
            $country = Country::firstOrCreate(
                ['iso2' => $row['iso2']],
                ['name' => $row['name'], 'currency' => $row['currency']]
            );
            foreach ($row['cities'] as $city) {
                City::firstOrCreate(['name' => $city, 'country_id' => $country->id]);
            }
        }
    }
}

