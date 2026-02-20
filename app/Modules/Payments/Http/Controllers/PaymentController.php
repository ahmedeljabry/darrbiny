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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaymentController extends BaseController
{
    use AuthorizesRequests;

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
            'payment_method' => ['required', 'string', 'in:wallet,tap'],
            'status' => ['required', 'string', 'in:pending,succeeded,failed'],
            'transaction_data' => ['nullable', 'array'],
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);

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
                $validated['payment_method'],
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
            'payment_method' => ['required', 'string', 'in:wallet,tap'],
            'status' => ['required', 'string', 'in:pending,succeeded,failed'],
            'transaction_data' => ['nullable', 'array'],
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);

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
                $validated['payment_method'],
                $validated['status'],
                $validated['transaction_data'] ?? []
            );
        }

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
