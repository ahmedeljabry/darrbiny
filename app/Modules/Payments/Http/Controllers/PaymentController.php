<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Models\Payment;
use App\Models\UserRequest;
use App\Modules\Payments\Http\Resources\PaymentDetailsResource;
use App\Modules\Payments\Http\Resources\PaymentResource;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PaymentController extends BaseController
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * Get payment details for a booking (service fee, VAT, total)
     * Mobile app calls this after creating a booking to show payment screen
     */
    public function paymentDetails(Request $request, string $id)
    {
        $req = UserRequest::with(['offers', 'user'])->findOrFail($id);
        $this->authorize('view', $req);
        return response()->json(['data' => new PaymentDetailsResource($req)]);
    }

    /**
     * Store reservation fee payment transaction
     * Mobile app handles payment gateway OR wallet payment
     */
    public function reservation(Request $request)
    {
        $validated = $request->validate([
            'user_request_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', 'in:wallet,apple_pay,stripe,paypal,moyasar,tap'],
            'provider' => ['required_if:payment_method,apple_pay,stripe,paypal,moyasar,tap', 'string', 'in:apple_pay,stripe,paypal,moyasar,tap'],
            'provider_ref' => ['required_if:payment_method,apple_pay,stripe,paypal,moyasar,tap', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,succeeded,failed'],
            'transaction_data' => ['nullable', 'array'], // Additional transaction metadata from mobile
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);
        
        // If wallet payment, check balance and deduct
        if ($validated['payment_method'] === 'wallet') {
            $payment = $this->service->payWithWallet(
                $req,
                $request->user(),
                Payment::TYPE_RESERVATION_FEE
            );
        } else {
            $payment = $this->service->storeReservationPayment(
                $req, 
                $request->user()->id,
                $validated['provider'],
                $validated['provider_ref'],
                $validated['status'],
                $validated['transaction_data'] ?? []
            );
        }

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }

    /**
     * Store plan payment transaction (full payment after offer selection)
     * Mobile app handles payment gateway OR wallet payment
     */
    public function plan(Request $request)
    {
        $validated = $request->validate([
            'user_request_id' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', 'in:wallet,apple_pay,stripe,paypal,moyasar,tap'],
            'provider' => ['required_if:payment_method,apple_pay,stripe,paypal,moyasar,tap', 'string', 'in:apple_pay,stripe,paypal,moyasar,tap'],
            'provider_ref' => ['required_if:payment_method,apple_pay,stripe,paypal,moyasar,tap', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,succeeded,failed'],
            'transaction_data' => ['nullable', 'array'], // Additional transaction metadata from mobile
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);
        
        // If wallet payment, check balance and deduct
        if ($validated['payment_method'] === 'wallet') {
            $payment = $this->service->payWithWallet(
                $req,
                $request->user(),
                Payment::TYPE_PLAN_FULL
            );
        } else {
            $payment = $this->service->storePlanPayment(
                $req,
                $request->user()->id,
                $validated['provider'],
                $validated['provider_ref'],
                $validated['status'],
                $validated['transaction_data'] ?? []
            );
        }

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }

    /**
     * Webhook endpoint for payment providers to notify about payment status changes
     */
    public function webhook(Request $request, string $provider)
    {
        // Verify webhook signatures via provider
        $valid = app(\App\Modules\Payments\Services\PaymentProvider::class)
            ->validateWebhook($request->all(), $request->headers->all());
        abort_unless($valid, 400, 'Invalid signature');
        
        // Update payment status based on webhook data
        $this->service->handleWebhook($provider, $request->all());
        
        return response()->json(['data' => ['ok' => true]]);
    }
}
