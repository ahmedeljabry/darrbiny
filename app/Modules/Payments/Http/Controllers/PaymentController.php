<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Models\Payment;
use App\Models\UserRequest;
use App\Modules\Payments\Http\Resources\PaymentResource;
use App\Modules\Payments\Services\PaymentGatewayException;
use App\Modules\Payments\Services\PaymentGatewayFactory;
use App\Modules\Payments\Services\PaymentProvider;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Services\TamaraProvider;
use App\Support\PaymentMethodSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\Rule;

class PaymentController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentService $service,
        private readonly PaymentGatewayFactory $gateways,
    ) {}

    /**
     * Store plan payment transaction (full or partial)
     */
    public function plan(Request $request)
    {
        $validated = $request->validate([
            'user_request_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', Rule::in(Payment::paymentMethods())],
            'type' => ['sometimes', 'string', 'in:plan_full,plan_partial'],
            'status' => ['nullable', 'string', 'in:pending,succeeded,failed'],
            'price' => ['nullable', 'numeric', 'min:1'],
            'offer_id' => ['nullable', 'uuid'],
        ]);

        $paymentType = $validated['type'] ?? Payment::TYPE_PLAN_FULL;
        $normalizedStatus = $validated['payment_method'] === Payment::METHOD_WALLET
            ? Payment::STATUS_SUCCEEDED
            : Payment::STATUS_PENDING;

        $request->merge([
            'type' => $paymentType,
            'status' => $normalizedStatus,
        ]);

        $req = UserRequest::with('country')->findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);
        abort_unless(
            PaymentMethodSettings::isAvailableForRequest($validated['payment_method'], $req),
            422,
            'Payment method is not available for this country or currency'
        );

        try {
            $payment = $this->service->createPlanPayment(
                $req,
                $request->user(),
                $request
            );
        } catch (PaymentGatewayException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [[
                    'code' => 'payment_gateway_error',
                    'message' => $exception->getMessage(),
                ]],
            ], 422);
        }

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless((string) $payment->user_id === (string) $request->user()?->id, 403);

        return response()->json(['data' => new PaymentResource($payment)]);
    }

    public function webhook(Request $request, string $gateway): JsonResponse
    {
        abort_unless(in_array($gateway, [Payment::METHOD_TAP, Payment::METHOD_TABBY, Payment::METHOD_TAMARA], true), 404);

        $payload = $request->all();
        $headers = $request->headers->all();
        if ($request->query('tamaraToken')) {
            $headers['tamara-token'] = [(string) $request->query('tamaraToken')];
        }

        $provider = $this->gateways->forMethod($gateway);
        abort_unless($provider->validateWebhook($payload, $headers), 403, 'Invalid payment webhook signature');

        $payment = $this->resolveWebhookPayment($gateway, $payload);
        if (! $payment) {
            return response()->json(['data' => ['processed' => false, 'reason' => 'payment_not_found']]);
        }

        $statusPayload = $this->verifiedWebhookPayload($gateway, $payment, $payload, $provider);
        if (! $statusPayload) {
            return response()->json(['data' => ['processed' => false, 'reason' => 'gateway_status_unverified']], 503);
        }

        $gatewayStatus = $this->webhookGatewayStatus($statusPayload);

        if ($this->isSuccessfulWebhookStatus($gatewayStatus)) {
            if ($gateway === Payment::METHOD_TAMARA && $provider instanceof TamaraProvider && $gatewayStatus === 'order_approved') {
                $authorisePayload = $provider->authorise($payment);
                $statusPayload['authorise_response'] = $authorisePayload;
                $gatewayStatus = strtolower((string) (data_get($authorisePayload, 'status') ?: 'authorised'));
            }

            $payment = $this->service->markGatewayPaymentSucceeded($payment, $statusPayload, $gatewayStatus);
        } elseif ($this->isFailedWebhookStatus($gatewayStatus)) {
            $payment = $this->service->markGatewayPaymentFailed($payment, $statusPayload, $gatewayStatus);
        }

        return response()->json([
            'data' => [
                'processed' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'gateway_status' => $payment->gateway_status,
            ],
        ]);
    }

    public function paymentReturn(Request $request, string $gateway, string $result, ?string $paymentId = null): JsonResponse
    {
        abort_unless(in_array($gateway, [Payment::METHOD_TAP, Payment::METHOD_TABBY, Payment::METHOD_TAMARA], true), 404);

        $payment = $this->handleGatewayReturn($request, $gateway, $result, $paymentId);

        return response()->json([
            'data' => $this->paymentReturnPayload($gateway, $result, $payment),
        ]);
    }

    public function paymentReturnPage(Request $request, string $gateway, string $result, ?string $paymentId = null): Response
    {
        abort_unless(in_array($gateway, [Payment::METHOD_TAP, Payment::METHOD_TABBY, Payment::METHOD_TAMARA], true), 404);

        $payment = $this->handleGatewayReturn($request, $gateway, $result, $paymentId);
        $payload = $this->paymentReturnPayload($gateway, $result, $payment);

        return response()
            ->view('payments.return', [
                'payload' => $payload,
                'gateway' => $gateway,
                'result' => $result,
                'payment' => $payment,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function resolveWebhookPayment(string $gateway, array $payload): ?Payment
    {
        $paymentId = data_get($payload, 'payment_id')
            ?? data_get($payload, 'order_reference_id')
            ?? data_get($payload, 'metadata.payment_id')
            ?? data_get($payload, 'reference.transaction')
            ?? data_get($payload, 'payment.meta.payment_id')
            ?? data_get($payload, 'meta.payment_id');

        if (is_string($paymentId) && $paymentId !== '') {
            $payment = Payment::query()
                ->where('payment_method', $gateway)
                ->whereKey($paymentId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        $gatewayReference = data_get($payload, 'order_id')
            ?? data_get($payload, 'checkout_id')
            ?? data_get($payload, 'tap_id')
            ?? data_get($payload, 'charge_id')
            ?? data_get($payload, 'charge.id')
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'payment.id')
            ?? data_get($payload, 'id')
            ?? data_get($payload, 'payment_id');

        if (! is_string($gatewayReference) || $gatewayReference === '') {
            return null;
        }

        return Payment::query()
            ->where('payment_method', $gateway)
            ->where('gateway_reference', $gatewayReference)
            ->first();
    }

    private function webhookGatewayStatus(array $payload): string
    {
        $status = data_get($payload, 'event_type')
            ?? data_get($payload, 'status')
            ?? data_get($payload, 'payment.status')
            ?? data_get($payload, 'event')
            ?? 'unknown';

        return strtolower((string) $status);
    }

    private function isSuccessfulWebhookStatus(string $status): bool
    {
        return in_array($status, [
            'authorized',
            'authorised',
            'closed',
            'captured',
            'paid',
            'approved',
            'payment_approved',
            'payment_authorized',
            'payment_authorised',
            'payment_captured',
            'order_approved',
            'order_authorised',
            'order_authorized',
            'order_captured',
            'fully_captured',
        ], true);
    }

    private function isFailedWebhookStatus(string $status): bool
    {
        return in_array($status, [
            'rejected',
            'expired',
            'declined',
            'canceled',
            'cancelled',
            'failed',
            'abandoned',
            'timedout',
            'timeout',
            'void',
            'restricted',
            'payment_declined',
            'payment_expired',
            'payment_voided',
            'order_canceled',
            'order_cancelled',
            'order_declined',
            'order_expired',
        ], true);
    }

    private function handleGatewayReturn(Request $request, string $gateway, string $result, ?string $paymentId = null): ?Payment
    {
        $payment = $this->resolveReturnPayment($request, $gateway, $paymentId);

        if ($payment && $this->isFailedReturnResult($result)) {
            return $this->service->markGatewayPaymentFailed($payment, [
                'return_result' => $result,
            ], $result);
        }

        if ($payment && $this->isSuccessfulReturnResult($result)) {
            return $this->syncGatewayReturnStatus($gateway, $payment, $this->gatewayReferenceFromRequest($request));
        }

        return $payment;
    }

    private function resolveReturnPayment(Request $request, string $gateway, ?string $paymentId = null): ?Payment
    {
        $candidateId = trim((string) ($paymentId ?: $request->query('payment_id', '')));

        if ($candidateId !== '') {
            $payment = Payment::query()
                ->where('payment_method', $gateway)
                ->whereKey($candidateId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        $gatewayReference = $this->gatewayReferenceFromRequest($request);
        if ($gatewayReference === null) {
            return null;
        }

        return Payment::query()
            ->where('payment_method', $gateway)
            ->where('gateway_reference', $gatewayReference)
            ->first();
    }

    private function paymentReturnPayload(string $gateway, string $result, ?Payment $payment): array
    {
        return [
            'gateway' => $gateway,
            'result' => $result,
            'payment_id' => $payment?->id,
            'status' => $payment?->status,
            'gateway_status' => $payment?->gateway_status,
        ];
    }

    private function isFailedReturnResult(string $result): bool
    {
        return in_array(strtolower($result), ['cancel', 'cancelled', 'canceled', 'failure', 'failed'], true);
    }

    private function isSuccessfulReturnResult(string $result): bool
    {
        return in_array(strtolower($result), ['success', 'succeeded', 'paid', 'approved'], true);
    }

    private function syncGatewayReturnStatus(string $gateway, Payment $payment, ?string $gatewayReference = null): Payment
    {
        if ($payment->status === Payment::STATUS_SUCCEEDED) {
            return $payment;
        }

        $gatewayReference = $this->preferredGatewayReference($gateway, $payment, $gatewayReference);
        if (! $gatewayReference) {
            return $payment;
        }

        $provider = $this->gateways->forMethod($gateway);
        if (! method_exists($provider, 'paymentStatus')) {
            return $payment;
        }

        try {
            $gatewayPayload = $provider->paymentStatus($gatewayReference);
        } catch (\Throwable) {
            return $payment;
        }

        if (! is_array($gatewayPayload)) {
            return $payment;
        }

        if ($gateway === Payment::METHOD_TAP) {
            if (! $this->tapPayloadMatchesPayment($gatewayPayload, $payment)) {
                return $payment;
            }

            $payment = $this->bindTapGatewayReference($payment, $gatewayReference);
        }

        $gatewayStatus = $this->webhookGatewayStatus($gatewayPayload);

        if ($this->isSuccessfulWebhookStatus($gatewayStatus)) {
            if ($gateway === Payment::METHOD_TAMARA && $provider instanceof TamaraProvider && in_array($gatewayStatus, ['approved', 'order_approved'], true)) {
                $authorisePayload = $provider->authorise($payment);
                $gatewayPayload['authorise_response'] = $authorisePayload;
                $gatewayStatus = strtolower((string) (data_get($authorisePayload, 'status') ?: 'authorised'));
            }

            return $this->service->markGatewayPaymentSucceeded($payment, $gatewayPayload, $gatewayStatus);
        }

        if ($this->isFailedWebhookStatus($gatewayStatus)) {
            return $this->service->markGatewayPaymentFailed($payment, $gatewayPayload, $gatewayStatus);
        }

        $payment->forceFill([
            'gateway_status' => $gatewayStatus ?: $payment->gateway_status,
            'gateway_payload' => array_merge(is_array($payment->gateway_payload) ? $payment->gateway_payload : [], [
                'return_status_check' => $gatewayPayload,
            ]),
        ])->save();

        return $payment->refresh();
    }

    private function verifiedWebhookPayload(string $gateway, Payment $payment, array $payload, PaymentProvider $provider): ?array
    {
        if ($gateway !== Payment::METHOD_TAP) {
            return $payload;
        }

        if (! method_exists($provider, 'paymentStatus')) {
            return null;
        }

        $gatewayReference = $this->preferredGatewayReference(
            $gateway,
            $payment,
            $this->gatewayReferenceFromPayload($payload)
        );

        if (! $gatewayReference) {
            return null;
        }

        try {
            $gatewayPayload = $provider->paymentStatus($gatewayReference);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($gatewayPayload) || ! $this->tapPayloadMatchesPayment($gatewayPayload, $payment)) {
            return null;
        }

        $this->bindTapGatewayReference($payment, $gatewayReference);

        return array_merge($gatewayPayload, [
            'webhook_payload' => $payload,
        ]);
    }

    private function preferredGatewayReference(string $gateway, Payment $payment, ?string $candidate = null): ?string
    {
        $candidate = trim((string) $candidate);
        $stored = trim((string) $payment->gateway_reference);

        if ($gateway === Payment::METHOD_TAP && $candidate !== '' && $candidate !== (string) $payment->id) {
            return $candidate;
        }

        return $stored !== '' ? $stored : ($candidate !== '' ? $candidate : null);
    }

    private function gatewayReferenceFromRequest(Request $request): ?string
    {
        foreach (['tap_id', 'charge_id', 'order_id', 'checkout_id', 'id'] as $key) {
            $value = trim((string) $request->query($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function gatewayReferenceFromPayload(array $payload): ?string
    {
        foreach (['tap_id', 'charge_id', 'charge.id', 'data.id', 'order_id', 'checkout_id', 'payment.id', 'id'] as $key) {
            $value = data_get($payload, $key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function bindTapGatewayReference(Payment $payment, string $gatewayReference): Payment
    {
        $gatewayReference = trim($gatewayReference);
        $stored = trim((string) $payment->gateway_reference);

        if ($gatewayReference === '' || $stored === $gatewayReference) {
            return $payment;
        }

        if ($stored !== '' && $stored !== (string) $payment->id) {
            return $payment;
        }

        $payment->forceFill([
            'gateway_reference' => $gatewayReference,
        ])->save();

        return $payment->refresh();
    }

    private function tapPayloadMatchesPayment(array $payload, Payment $payment): bool
    {
        $paymentIdCandidates = [
            data_get($payload, 'metadata.payment_id'),
            data_get($payload, 'reference.transaction'),
            data_get($payload, 'payment_id'),
            data_get($payload, 'order_reference_id'),
        ];

        foreach ($paymentIdCandidates as $candidate) {
            if (is_string($candidate) && $candidate === (string) $payment->id) {
                return true;
            }
        }

        $payment->loadMissing('userRequest');
        $orderNumber = (string) ($payment->userRequest?->order_number ?? '');
        $orderCandidates = [
            data_get($payload, 'metadata.order_number'),
            data_get($payload, 'reference.order'),
            data_get($payload, 'order_number'),
        ];

        foreach ($orderCandidates as $candidate) {
            if ($orderNumber !== '' && is_string($candidate) && $candidate === $orderNumber) {
                return true;
            }
        }

        $currency = strtoupper((string) (data_get($payload, 'currency') ?? ''));
        $amount = data_get($payload, 'amount');

        return $currency === strtoupper((string) $payment->currency)
            && is_numeric($amount)
            && (int) round(((float) $amount) * 100) === $payment->grossAmountMinor();
    }
}
