<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class TabbyProvider implements PaymentProvider
{
    public function initiate(Payment $payment, array $metadata = []): array
    {
        $payment->loadMissing(['user', 'userRequest.plan.country', 'userRequest.country']);

        $secret = GatewayPayloadSupport::setting('payment.tabby.secret_key');
        $merchantCode = GatewayPayloadSupport::setting('payment.tabby.merchant_code');

        if (! $secret || ! $merchantCode) {
            throw new PaymentGatewayException('Tabby keys are not configured.');
        }

        $request = $payment->userRequest;
        if (! $request) {
            throw new PaymentGatewayException('Payment is missing its user request.');
        }

        $baseUrl = rtrim(
            GatewayPayloadSupport::setting('payment.tabby.base_url', $this->defaultBaseUrl((string) $payment->currency)),
            '/'
        );

        $payload = $this->payload($payment, $merchantCode);

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->post($baseUrl.'/api/v2/checkout', $payload);

        $body = $response->json();

        if (! $response->successful() || ! is_array($body)) {
            throw new PaymentGatewayException('Tabby checkout session could not be created.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        $checkoutUrl = $this->checkoutUrl($body);
        if (! $checkoutUrl) {
            throw new PaymentGatewayException('Tabby checkout response did not include a checkout URL.', [
                'response' => $body,
            ]);
        }

        return [
            'gateway_reference' => (string) (data_get($body, 'payment.id') ?? data_get($body, 'id') ?? $payment->id),
            'checkout_url' => $checkoutUrl,
            'gateway_status' => strtolower((string) (data_get($body, 'status') ?? data_get($body, 'payment.status') ?? 'created')),
            'payload' => $body,
        ];
    }

    public function validateWebhook(array $payload, array $headers = []): bool
    {
        $secret = GatewayPayloadSupport::setting('payment.tabby.webhook_secret');
        if (! $secret) {
            return false;
        }

        $provided = $this->header($headers, 'x-tabby-signature')
            ?? $this->header($headers, 'tabby-signature')
            ?? $this->header($headers, 'x-webhook-secret')
            ?? $this->bearerToken($headers)
            ?? (string) ($payload['webhook_secret'] ?? '');

        return $provided !== '' && hash_equals($secret, $provided);
    }

    public function paymentStatus(string $gatewayReference): ?array
    {
        $secret = GatewayPayloadSupport::setting('payment.tabby.secret_key');
        if (! $secret) {
            throw new PaymentGatewayException('Tabby keys are not configured.');
        }

        $baseUrl = rtrim(GatewayPayloadSupport::setting('payment.tabby.base_url', 'https://api.tabby.ai'), '/');
        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($secret)
            ->get($baseUrl.'/api/v2/payments/'.$gatewayReference);

        return $response->successful() ? $response->json() : null;
    }

    private function payload(Payment $payment, string $merchantCode): array
    {
        $request = $payment->userRequest;
        $user = $payment->user;
        $amount = GatewayPayloadSupport::amount($payment);
        $taxAmount = GatewayPayloadSupport::taxAmount($payment);
        $orderNumber = GatewayPayloadSupport::orderNumber($request);
        $planTitle = trim((string) ($request->plan?->title ?? 'Training course')) ?: 'Training course';

        return [
            'payment' => [
                'amount' => $amount,
                'currency' => strtoupper((string) $payment->currency),
                'description' => GatewayPayloadSupport::description($payment, $request),
                'buyer' => [
                    'name' => trim((string) ($user?->name ?? 'Darrbiny Customer')) ?: 'Darrbiny Customer',
                    'email' => GatewayPayloadSupport::email($user),
                    'phone' => GatewayPayloadSupport::phone($user),
                ],
                'shipping_address' => [
                    'city' => (string) ($request->area_level_2 ?: $request->locality ?: $request->country?->name ?: 'Riyadh'),
                    'address' => (string) ($request->locality ?: $request->area_level_1 ?: $request->country?->name ?: 'Darrbiny'),
                    'zip' => '00000',
                ],
                'order' => [
                    'reference_id' => $orderNumber,
                    'items' => [[
                        'title' => $planTitle,
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'category' => 'Training',
                        'reference_id' => (string) ($request->plan_id ?? $payment->id),
                        'description' => $planTitle,
                        'discount_amount' => '0.00',
                        'tax_amount' => $taxAmount,
                        'is_refundable' => true,
                    ]],
                    'updated_at' => now()->toIso8601String(),
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => '0.00',
                    'discount_amount' => '0.00',
                ],
                'meta' => [
                    'payment_id' => (string) $payment->id,
                    'user_request_id' => (string) $request->id,
                    'order_number' => $orderNumber,
                ],
            ],
            'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            'merchant_code' => $merchantCode,
            'merchant_urls' => [
                'success' => GatewayPayloadSupport::returnUrl('tabby', $payment, 'success'),
                'cancel' => GatewayPayloadSupport::returnUrl('tabby', $payment, 'cancel'),
                'failure' => GatewayPayloadSupport::returnUrl('tabby', $payment, 'failure'),
            ],
        ];
    }

    private function checkoutUrl(array $body): ?string
    {
        $candidates = [
            data_get($body, 'configuration.available_products.installments.0.web_url'),
            data_get($body, 'configuration.available_products.pay_in_4.0.web_url'),
            data_get($body, 'configuration.products.installments.web_url'),
            data_get($body, 'checkout_url'),
            data_get($body, 'web_url'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return $this->firstUrlByKey($body, ['web_url', 'checkout_url']);
    }

    private function firstUrlByKey(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $keys, true) && is_string($value) && $value !== '') {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->firstUrlByKey($value, $keys);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function defaultBaseUrl(string $currency): string
    {
        return strtoupper($currency) === 'SAR' ? 'https://api.tabby.sa' : 'https://api.tabby.ai';
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
