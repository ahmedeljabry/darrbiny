<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use App\Notifications\CourseCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDisplayNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_cancelled_notification_uses_booking_order_number(): void
    {
        [$user, $userRequest] = $this->createUserAndRequest(5076);

        $data = (new CourseCancelledNotification(
            $userRequest->load('plan'),
            'اختبار سبب الإلغاء',
            10
        ))->toDatabase($user);

        $this->assertStringContainsString('#' . $userRequest->order_number, $data['message']);
        $this->assertStringNotContainsString($userRequest->id, $data['message']);
        $this->assertSame($userRequest->order_number, $data['order_number']);
        $this->assertSame($userRequest->formatted_order_number, $data['formatted_order_number']);
        $this->assertSame((string) $userRequest->order_number, $data['display_order_number']);
        $this->assertStringStartsWith('5', $data['display_order_number']);
    }

    public function test_notifications_api_normalizes_legacy_uuid_course_references(): void
    {
        [$user, $userRequest] = $this->createUserAndRequest(5077);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => CourseCancelledNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'تم إلغاء الدورة',
                'message' => "تم إلغاء دورة كورس تدريب رقم #{$userRequest->id}",
                'type' => 'course_cancelled',
                'user_request_id' => $userRequest->id,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($user->createToken('notifications')->plainTextToken)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.data.display_order_number', (string) $userRequest->order_number);

        $message = (string) $response->json('data.0.data.message');

        $this->assertStringContainsString('#' . $userRequest->order_number, $message);
        $this->assertStringNotContainsString($userRequest->id, $message);
    }

    private function createUserAndRequest(int $orderNumber): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'كورس تدريب',
            'description' => 'Course used for notification tests',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+966500009001',
        ]);

        $userRequest = UserRequest::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->addDay()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        return [$user, $userRequest];
    }
}
