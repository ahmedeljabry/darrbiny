<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Egypt', 'iso2' => 'EG', 'currency' => 'EGP'],
            ['name' => 'Jordan', 'iso2' => 'JO', 'currency' => 'JOD'],
            ['name' => 'Saudi Arabia', 'iso2' => 'SA', 'currency' => 'SAR'],
            ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'currency' => 'AED'],
        ];
        foreach ($data as $row) {
            Country::firstOrCreate(
                ['iso2' => $row['iso2']],
                ['name' => $row['name'], 'currency' => $row['currency']]
            );
        }
    }
}
