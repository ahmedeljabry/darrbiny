<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Models\Payment;
use App\Models\UserRequest;
use App\Modules\Payments\Http\Resources\PaymentResource;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\Rule;

class PaymentController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(private readonly PaymentService $service) {}

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
            : ($validated['status'] ?? Payment::STATUS_PENDING);

        $request->merge([
            'type' => $paymentType,
            'status' => $normalizedStatus,
        ]);

        $req = UserRequest::findOrFail($validated['user_request_id']);
        $this->authorize('update', $req);
        $payment = $this->service->payWithWallet(
            $req,
            $request->user(),
            $request
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }
}
