<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Models\Payment;
use App\Models\UserRequest;
use App\Modules\Payments\Http\Resources\PaymentResource;
use App\Modules\Payments\Services\PaymentGatewayException;
use App\Modules\Payments\Services\PaymentGatewayFactory;
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
            'price' => ['nullable', 'integer', 'min:1'],
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

        $gatewayStatus = $this->webhookGatewayStatus($payload);

        if ($this->isSuccessfulWebhookStatus($gatewayStatus)) {
            if ($gateway === Payment::METHOD_TAMARA && $provider instanceof TamaraProvider && $gatewayStatus === 'order_approved') {
                $authorisePayload = $provider->authorise($payment);
                $payload['authorise_response'] = $authorisePayload;
                $gatewayStatus = strtolower((string) (data_get($authorisePayload, 'status') ?: 'authorised'));
            }

            $payment = $this->service->markGatewayPaymentSucceeded($payment, $payload, $gatewayStatus);
        } elseif ($this->isFailedWebhookStatus($gatewayStatus)) {
            $payment = $this->service->markGatewayPaymentFailed($payment, $payload, $gatewayStatus);
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

        return $payment;
    }

    private function resolveReturnPayment(Request $request, string $gateway, ?string $paymentId = null): ?Payment
    {
        $candidateId = trim((string) ($paymentId ?: $request->query('payment_id', '')));

        if ($candidateId !== '') {
            return Payment::query()
                ->where('payment_method', $gateway)
                ->whereKey($candidateId)
                ->first();
        }

        return null;
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
}
