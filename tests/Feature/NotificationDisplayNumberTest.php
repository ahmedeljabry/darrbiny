<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use App\Notifications\CourseCancelledNotification;
use App\Notifications\CourseCompletedNotification;
use App\Notifications\NewRequestAvailable;
use App\Notifications\WalletBalanceAddedNotification;
use App\Support\NotificationPayloadSanitizer;
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

    public function test_course_notifications_allocate_missing_booking_order_number_before_payload(): void
    {
        $notificationFactories = [
            'course cancelled' => fn (UserRequest $request) => new CourseCancelledNotification($request->load('plan'), 'اختبار سبب الإلغاء', 10),
            'course completed' => fn (UserRequest $request) => new CourseCompletedNotification($request->load('plan')),
            'new request' => fn (UserRequest $request) => new NewRequestAvailable($request->load(['plan', 'user'])),
            'course payout' => fn (UserRequest $request) => new WalletBalanceAddedNotification(1000, 'course_payout', null, $request),
        ];

        foreach ($notificationFactories as $notificationFactory) {
            [$user, $userRequest] = $this->createUserAndRequestWithMissingOrderNumber();

            $data = $notificationFactory($userRequest)->toDatabase($user);

            $this->assertNotificationPayloadUsesBookingOrderNumber($data, $userRequest);
        }
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
                'request_id' => $userRequest->id,
                'user_request_id' => $userRequest->id,
                'country_id' => $userRequest->country_id,
                'meta' => [
                    'payment_ref' => "Payment: {$userRequest->id}",
                ],
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
        $this->assertArrayHasKey('display_order_number', $response->json('data.0.data'));
        $this->assertArrayNotHasKey('user_request_id', $response->json('data.0.data'));
        $this->assertArrayNotHasKey('request_id', $response->json('data.0.data'));
        $this->assertArrayNotHasKey('country_id', $response->json('data.0.data'));
        $this->assertPayloadHasNoUuid($response->json('data.0.data'));
    }

    public function test_notification_payload_sanitizer_removes_uuid_identifiers_for_firebase_payloads(): void
    {
        $uuid = (string) Str::uuid();

        $payload = NotificationPayloadSanitizer::withoutUuids([
            'type' => 'test',
            'notification_id' => $uuid,
            'user_request_id' => $uuid,
            'display_order_number' => '5078',
            'message' => "Payment: {$uuid}",
        ]);

        $this->assertArrayNotHasKey('notification_id', $payload);
        $this->assertArrayNotHasKey('user_request_id', $payload);
        $this->assertSame('Payment: 5078', $payload['message']);
        $this->assertPayloadHasNoUuid($payload);
    }

    private function assertNotificationPayloadUsesBookingOrderNumber(array $data, UserRequest $userRequest): void
    {
        $userRequest->refresh();

        $this->assertNotNull($userRequest->order_number);
        $this->assertSame($userRequest->order_number, $data['order_number']);
        $this->assertSame($userRequest->formatted_order_number, $data['formatted_order_number']);
        $this->assertSame((string) $userRequest->order_number, $data['display_order_number']);
        $this->assertStringStartsWith('5', $data['display_order_number']);

        if (isset($data['message'])) {
            $this->assertStringNotContainsString($userRequest->id, (string) $data['message']);
        }
    }

    private function assertPayloadHasNoUuid(mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->assertPayloadHasNoUuid($item);
            }

            return;
        }

        if (is_scalar($value)) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i',
                (string) $value
            );
        }
    }

    private function createUserAndRequestWithMissingOrderNumber(): array
    {
        [$user, $userRequest] = $this->createUserAndRequest(null);

        $userRequest->forceFill(['order_number' => null])->saveQuietly();

        return [$user, $userRequest->refresh()];
    }

    private function createUserAndRequest(?int $orderNumber): array
    {
        $country = Country::firstOrCreate(
            ['iso2' => 'SA'],
            [
                'name' => 'Saudi Arabia',
                'currency' => 'SAR',
            ]
        );

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
            'phone_with_cc' => fake()->unique()->numerify('+966500009###'),
        ]);

        $attributes = [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->addDay()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ];

        if ($orderNumber !== null) {
            $attributes['order_number'] = $orderNumber;
        }

        $userRequest = UserRequest::create($attributes);

        return [$user, $userRequest];
    }
}
