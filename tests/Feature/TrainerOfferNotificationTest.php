<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\TrainerOfferAcceptedNotification;
use App\Notifications\TrainerOfferSentNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainerOfferNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_receives_database_and_fcm_notification_when_trainer_sends_offer(): void
    {
        [$student, $trainer, $userRequest] = $this->createOfferFixture();

        $this->withToken($trainer->createToken('trainer')->plainTextToken)
            ->postJson('/api/v1/trainer/offers', [
                'user_request_id' => $userRequest->id,
                'price_minor' => 25000,
                'message' => 'عرض مناسب',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_request_id', $userRequest->id)
            ->assertJsonPath('data.trainer_id', $trainer->id);

        $offer = TrainerOffer::query()->where('user_request_id', $userRequest->id)->firstOrFail();
        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $student->id)
            ->where('type', TrainerOfferSentNotification::class)
            ->first();

        $this->assertNotNull($storedNotification);

        $payload = json_decode((string) $storedNotification->data, true);
        $this->assertSame('trainer_offer_sent', $payload['type']);
        $this->assertSame('قام احد المدربات بإرسال عرض سعر لكي', $payload['message']);
        $this->assertSame($offer->id, $payload['offer_id']);
        $this->assertSame($userRequest->id, $payload['user_request_id']);
        $this->assertSame($trainer->id, $payload['trainer_id']);
        $this->assertSame(25000, $payload['price_minor']);

        $notification = new TrainerOfferSentNotification($offer);
        $channels = $notification->via($student);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);
        $this->assertSame('sync', $notification->viaConnections()['database']);
        $this->assertSame(config('queue.default'), $notification->viaConnections()[FcmChannel::class]);
    }

    public function test_trainer_receives_database_and_fcm_notification_when_student_accepts_offer(): void
    {
        [$student, $trainer, $userRequest] = $this->createOfferFixture();

        $offer = TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 25000,
            'message' => 'عرض مناسب',
            'status' => TrainerOffer::STATUS_SENT,
        ]);

        $this->withToken($student->createToken('student')->plainTextToken)
            ->postJson('/api/v1/offers/'.$offer->id.'/accept')
            ->assertOk()
            ->assertJsonPath('data.trainer_id', $trainer->id)
            ->assertJsonPath('data.status', UserRequest::STATUS_OFFER_SELECTED);

        $storedNotification = DB::table('notifications')
            ->where('notifiable_id', $trainer->id)
            ->where('type', TrainerOfferAcceptedNotification::class)
            ->first();

        $this->assertNotNull($storedNotification);

        $payload = json_decode((string) $storedNotification->data, true);
        $this->assertSame('trainer_offer_accepted', $payload['type']);
        $this->assertSame('قامت المتدربه بقبول عرض سعرك', $payload['message']);
        $this->assertSame($offer->id, $payload['offer_id']);
        $this->assertSame($userRequest->id, $payload['user_request_id']);
        $this->assertSame($student->id, $payload['trainee_id']);
        $this->assertSame(25000, $payload['price_minor']);

        $notification = new TrainerOfferAcceptedNotification($offer);
        $channels = $notification->via($trainer);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);
        $this->assertSame('sync', $notification->viaConnections()['database']);
        $this->assertSame(config('queue.default'), $notification->viaConnections()[FcmChannel::class]);
    }

    public function test_only_request_owner_can_accept_offer(): void
    {
        [, $trainer, $userRequest] = $this->createOfferFixture();

        $otherStudent = User::factory()->create([
            'phone_with_cc' => '+966500001003',
        ]);
        $otherStudent->assignRole('USER');

        $offer = TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 25000,
            'message' => 'عرض مناسب',
            'status' => TrainerOffer::STATUS_SENT,
        ]);

        $this->withToken($otherStudent->createToken('other-student')->plainTextToken)
            ->postJson('/api/v1/offers/'.$offer->id.'/accept')
            ->assertForbidden();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $trainer->id,
            'type' => TrainerOfferAcceptedNotification::class,
        ]);

        $this->assertDatabaseHas('trainer_offers', [
            'id' => $offer->id,
            'status' => TrainerOffer::STATUS_SENT,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'trainer_id' => null,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
        ]);
    }

    private function createOfferFixture(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Plan for offer notification tests',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+966500001001',
        ]);
        $student->assignRole('USER');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+966500001002',
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
        ]);

        $userRequest = UserRequest::create([
            'user_id' => $student->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Al Olaya',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ]);

        return [$student, $trainer, $userRequest];
    }
}
