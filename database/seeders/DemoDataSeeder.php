<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users
        $user = User::firstOrCreate(['phone_with_cc' => '+201111111111'], [
            'name' => 'Demo User',
            'currency' => 'EGP',
        ]);
        $user->assignRole('USER');

        $trainer = User::firstOrCreate(['phone_with_cc' => '+201222222222'], [
            'name' => 'Demo Trainer',
            'currency' => 'EGP',
        ]);
        $trainer->assignRole('TRAINER');
        TrainerProfile::firstOrCreate(['user_id' => $trainer->id]);

        // Admin user (for panel login when using session-based auth)
        $admin = User::firstOrCreate(['phone_with_cc' => '+201333333333'], [
            'name' => 'Admin',
            'currency' => 'EGP',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('ADMIN');

        // Plans (assumes GeoSeeder ran and countries/cities exist)
        $city = \App\Models\City::first();
        $country = $city?->country ?? \App\Models\Country::first();
        Plan::firstOrCreate([
            'title' => 'Starter 5 Hours',
            'city_id' => $city?->id,
        ], [
            'description' => 'Basics of driving',
            'hours_count' => 5,
            'training_type' => 'manual',
            'country_id' => $country?->id,
            'base_price_minor' => 50000,
            'is_active' => true,
        ]);
    }
}
