<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\NewRequestAvailable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NewRequestAvailableNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_matching_area_trainers_receive_database_and_fcm_notification_when_student_pays_request_fee(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $student = $this->createStudent();
        $student->update(['points_balance' => 500]);
        $matchingTrainer = $this->createTrainer($country->id, [
            'area_level_1' => 'Cairo Governorate',
            'area_level_2' => 'Cairo',
            'area_level_3' => 'Nasr City',
            'locality' => 'Different Locality',
        ]);
        $broadAreaTrainer = $this->createTrainer($country->id, [
            'area_level_1' => ' cairo governorate ',
            'area_level_2' => ' cairo ',
            'area_level_3' => null,
            'locality' => 'Nasr City',
        ]);
        $otherAreaTrainer = $this->createTrainer($country->id, [
            'area_level_1' => 'Cairo Governorate',
            'area_level_2' => 'Cairo',
            'area_level_3' => 'Heliopolis',
            'locality' => 'Nasr City',
        ]);

        $token = $student->createToken('student')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/user-requests', [
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'area_level_1' => 'Cairo Governorate',
                'area_level_2' => 'Cairo',
                'area_level_3' => 'Nasr City',
                'locality' => 'Nasr City',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $response->json('data.id'),
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 1000,
            ])
            ->assertCreated();

        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $matchingTrainer->id)
            ->where('type', NewRequestAvailable::class)
            ->first();

        $this->assertNotNull($storedNotification);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $broadAreaTrainer->id,
            'type' => NewRequestAvailable::class,
        ]);

        $payload = json_decode((string) $storedNotification->data, true);
        $this->assertSame('new_request_available', $payload['type']);
        $this->assertSame('يوجد طلب تدريب جديد في منطقتك', $payload['message']);
        $this->assertSame('Cairo Governorate', $payload['area_level_1']);
        $this->assertSame('Cairo', $payload['area_level_2']);
        $this->assertSame('Nasr City', $payload['area_level_3']);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherAreaTrainer->id,
            'type' => NewRequestAvailable::class,
        ]);

        $notification = new NewRequestAvailable(UserRequest::query()->firstOrFail());
        $channels = $notification->via($matchingTrainer);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);
        $this->assertSame('sync', $notification->viaConnections()['database']);
        $this->assertSame('sync', $notification->viaConnections()[FcmChannel::class]);
    }

    private function createCountryAndPlan(): array
    {
        $country = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);

        $plan = Plan::create([
            'title' => 'Training Plan',
            'description' => 'Plan for trainer notification tests',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        return [$country, $plan];
    }

    private function createStudent(): User
    {
        $student = User::factory()->create([
            'phone_with_cc' => '+201000000001',
        ]);
        $student->assignRole('USER');

        return $student;
    }

    private function createTrainer(string $countryId, array $location): User
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => fake()->unique()->numerify('+201000001###'),
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'country_id' => $countryId,
            'verified_at' => now(),
            ...$location,
        ]);

        return $trainer;
    }
}
