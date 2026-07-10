<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class TamaraProvider implements PaymentProvider
{
    public function initiate(Payment $payment, array $metadata = []): array
    {
        $payment->loadMissing(['user', 'userRequest.plan', 'userRequest.country']);

        $secret = GatewayPayloadSupport::setting('payment.tamara.secret_key');
        if (! $secret) {
            throw new PaymentGatewayException('Tamara keys are not configured.');
        }

        $baseUrl = rtrim(GatewayPayloadSupport::setting('payment.tamara.base_url', 'https://api.tamara.co'), '/');
        $payload = $this->payload($payment);

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->post($baseUrl.'/checkout', $payload);

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new PaymentGatewayException('Tamara checkout session could not be created.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        $checkoutUrl = (string) (data_get($body, 'checkout_url') ?? '');
        if ($checkoutUrl === '') {
            throw new PaymentGatewayException('Tamara checkout response did not include a checkout URL.', [
                'response' => $body,
            ]);
        }

        return [
            'gateway_reference' => (string) (data_get($body, 'order_id') ?? data_get($body, 'checkout_id') ?? $payment->id),
            'checkout_url' => $checkoutUrl,
            'gateway_status' => strtolower((string) (data_get($body, 'status') ?? 'new')),
            'payload' => $body,
        ];
    }

    public function validateWebhook(array $payload, array $headers = []): bool
    {
        $notificationToken = GatewayPayloadSupport::setting('payment.tamara.webhook_secret');
        if (! $notificationToken) {
            return false;
        }

        $token = $this->tokenFromHeaders($headers) ?: (string) ($headers['tamara-token'][0] ?? $headers['tamara-token'] ?? '');

        return $token !== '' && $this->validJwt($token, $notificationToken);
    }

    public function authorise(Payment $payment): array
    {
        $secret = GatewayPayloadSupport::setting('payment.tamara.secret_key');
        if (! $secret || ! $payment->gateway_reference) {
            throw new PaymentGatewayException('Tamara order cannot be authorised.');
        }

        $baseUrl = rtrim(GatewayPayloadSupport::setting('payment.tamara.base_url', 'https://api.tamara.co'), '/');

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->post($baseUrl.'/orders/'.$payment->gateway_reference.'/authorise');

        $body = $response->json();
        if (! $response->successful()) {
            throw new PaymentGatewayException('Tamara order authorisation failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        return is_array($body) ? $body : ['status' => 'authorised'];
    }

    private function payload(Payment $payment): array
    {
        $request = $payment->userRequest;
        if (! $request) {
            throw new PaymentGatewayException('Payment is missing its user request.');
        }

        $user = $payment->user;
        $amount = GatewayPayloadSupport::amount($payment);
        $currency = strtoupper((string) $payment->currency);
        $orderNumber = GatewayPayloadSupport::orderNumber($request);
        $planTitle = trim((string) ($request->plan?->title ?? 'Training course')) ?: 'Training course';

        $money = ['amount' => $amount, 'currency' => $currency];
        $zero = ['amount' => '0.00', 'currency' => $currency];

        return [
            'total_amount' => $money,
            'shipping_amount' => $zero,
            'tax_amount' => $zero,
            'order_reference_id' => (string) $payment->id,
            'order_number' => $orderNumber,
            'description' => GatewayPayloadSupport::description($payment, $request),
            'country_code' => GatewayPayloadSupport::countryCode($request),
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'items' => [[
                'name' => $planTitle,
                'quantity' => 1,
                'reference_id' => (string) ($request->plan_id ?? $payment->id),
                'type' => 'Digital',
                'sku' => substr((string) ($request->plan_id ?? $payment->id), 0, 128),
                'unit_price' => $money,
                'total_amount' => $money,
                'tax_amount' => $zero,
                'discount_amount' => $zero,
            ]],
            'consumer' => [
                'first_name' => GatewayPayloadSupport::firstName($user),
                'last_name' => GatewayPayloadSupport::lastName($user),
                'phone_number' => GatewayPayloadSupport::phone($user),
                'email' => GatewayPayloadSupport::email($user),
            ],
            'merchant_url' => [
                'success' => GatewayPayloadSupport::returnUrl('tamara', $payment, 'success'),
                'failure' => GatewayPayloadSupport::returnUrl('tamara', $payment, 'failure'),
                'cancel' => GatewayPayloadSupport::returnUrl('tamara', $payment, 'cancel'),
                'notification' => GatewayPayloadSupport::webhookUrl('tamara'),
            ],
        ];
    }

    private function tokenFromHeaders(array $headers): ?string
    {
        $authorization = $this->header($headers, 'authorization');
        if (is_string($authorization) && str_starts_with(strtolower($authorization), 'bearer ')) {
            return trim(substr($authorization, 7));
        }

        return $this->header($headers, 'tamara-token');
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

    private function validJwt(string $token, string $secret): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return hash_equals($secret, $token);
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $secret, true));

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        $claims = json_decode((string) $this->base64UrlDecode($payload), true);
        if (is_array($claims) && isset($claims['exp']) && (int) $claims['exp'] < time()) {
            return false;
        }

        return true;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
