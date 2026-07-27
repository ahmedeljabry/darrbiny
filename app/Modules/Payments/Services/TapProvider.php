<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class TapProvider implements PaymentProvider
{
    public function initiate(Payment $payment, array $metadata = []): array
    {
        $payment->loadMissing(['user', 'userRequest.plan.country', 'userRequest.country']);

        $secret = GatewayPayloadSupport::setting('payment.tap.secret_key');
        $public = GatewayPayloadSupport::setting('payment.tap.public_key');
        if (! $secret || ! $public) {
            throw new PaymentGatewayException('Tap keys are not configured.');
        }

        if (! $payment->userRequest) {
            throw new PaymentGatewayException('Payment is missing its user request.');
        }

        $baseUrl = $this->baseUrl();
        $payload = $this->payload($payment, $metadata);

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->post($baseUrl.'/v2/charges', $payload);

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new PaymentGatewayException('Tap charge could not be created.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        $checkoutUrl = $this->checkoutUrl($body);
        if (! $checkoutUrl) {
            throw new PaymentGatewayException('Tap charge response did not include a checkout URL.', [
                'response' => $body,
            ]);
        }

        return [
            'gateway_reference' => (string) (data_get($body, 'id') ?? $payment->id),
            'checkout_url' => $checkoutUrl,
            'gateway_status' => strtolower((string) (data_get($body, 'status') ?? 'initiated')),
            'payload' => $body,
            'public_key' => $public,
        ];
    }

    public function validateWebhook(array $payload, array $headers = []): bool
    {
        $secret = GatewayPayloadSupport::setting('payment.tap.webhook_secret');

        if (! $secret) {
            return $this->hasChargeReference($payload);
        }

        $provided = $this->header($headers, 'tap-signature')
            ?? $this->header($headers, 'x-tap-signature')
            ?? $this->header($headers, 'x-webhook-secret')
            ?? $this->bearerToken($headers)
            ?? (string) ($payload['webhook_secret'] ?? '');

        if ($provided !== '' && hash_equals($secret, $provided)) {
            return true;
        }

        $signature = $this->header($headers, 'tap-signature')
            ?? $this->header($headers, 'x-tap-signature');

        if (is_string($signature) && $signature !== '') {
            $computed = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
            if (hash_equals($computed, $signature)) {
                return true;
            }
        }

        return $this->hasChargeReference($payload);
    }

    public function paymentStatus(string $gatewayReference): ?array
    {
        $secret = GatewayPayloadSupport::setting('payment.tap.secret_key');
        if (! $secret) {
            throw new PaymentGatewayException('Tap keys are not configured.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->get($this->baseUrl().'/v2/charges/'.$gatewayReference);

        $body = $response->json();

        return $response->successful() && is_array($body) ? $body : null;
    }

    private function payload(Payment $payment, array $metadata): array
    {
        $request = $payment->userRequest;
        if (! $request) {
            throw new PaymentGatewayException('Payment is missing its user request.');
        }

        $user = $payment->user;
        $orderNumber = GatewayPayloadSupport::orderNumber($request);
        $amount = (float) GatewayPayloadSupport::totalAmount($payment);
        $currency = strtoupper((string) $payment->currency);

        return [
            'amount' => $amount,
            'currency' => $currency,
            'customer_initiated' => true,
            'threeDSecure' => true,
            'save_card' => false,
            'description' => GatewayPayloadSupport::description($payment, $request),
            'metadata' => array_filter([
                ...$metadata,
                'payment_id' => (string) $payment->id,
                'user_request_id' => (string) $request->id,
                'order_number' => $orderNumber,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'reference' => [
                'transaction' => (string) $payment->id,
                'order' => $orderNumber,
            ],
            'customer' => [
                'first_name' => GatewayPayloadSupport::firstName($user),
                'last_name' => GatewayPayloadSupport::lastName($user),
                'email' => GatewayPayloadSupport::email($user),
                'phone' => $this->phone($user, $request),
            ],
            'source' => [
                'id' => 'src_all',
            ],
            'post' => [
                'url' => GatewayPayloadSupport::webhookUrl('tap'),
            ],
            'redirect' => [
                'url' => GatewayPayloadSupport::returnUrl('tap', $payment, 'success'),
            ],
        ];
    }

    private function phone(?User $user, UserRequest $request): array
    {
        $digits = preg_replace('/\D+/', '', GatewayPayloadSupport::phone($user)) ?: '500000000';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $countryCode = $this->callingCode($request);
        $knownCodes = ['966', '971', '965', '973', '974', '968', '962', '20'];

        foreach ($knownCodes as $code) {
            if (str_starts_with($digits, $code) && strlen($digits) > strlen($code) + 4) {
                $countryCode = $code;
                $digits = substr($digits, strlen($code));
                break;
            }
        }

        return [
            'country_code' => $countryCode,
            'number' => $digits !== '' ? $digits : '500000000',
        ];
    }

    private function callingCode(UserRequest $request): string
    {
        return match (GatewayPayloadSupport::countryCode($request)) {
            'AE' => '971',
            'KW' => '965',
            'BH' => '973',
            'QA' => '974',
            'OM' => '968',
            'JO' => '962',
            'EG' => '20',
            default => '966',
        };
    }

    private function checkoutUrl(array $body): ?string
    {
        $candidates = [
            data_get($body, 'transaction.url'),
            data_get($body, 'redirect.url'),
            data_get($body, 'checkout_url'),
            data_get($body, 'url'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function baseUrl(): string
    {
        return rtrim(GatewayPayloadSupport::setting('payment.tap.base_url', 'https://api.tap.company'), '/');
    }

    private function hasChargeReference(array $payload): bool
    {
        foreach (['id', 'tap_id', 'charge_id', 'charge.id', 'data.id'] as $key) {
            $value = data_get($payload, $key);
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function header(array $headers, string $name): ?string
    {
        $headers = Arr::dot($headers);

        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === strtolower($name) || str_starts_with(strtolower((string) $key), strtolower($name).'.')) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }

    private function bearerToken(array $headers): ?string
    {
        $authorization = $this->header($headers, 'authorization');
        if (! is_string($authorization) || ! str_starts_with(strtolower($authorization), 'bearer ')) {
            return null;
        }

        return trim(substr($authorization, 7));
    }
}
