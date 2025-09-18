<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Controllers;

use App\Models\TrainerOffer;
use App\Models\UserRequest;
use App\Modules\Offers\Http\Requests\StoreOfferRequest;
use App\Modules\Offers\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class OfferController extends BaseController
{
    public function __construct(private readonly OfferService $service) {}

    public function listForRequest(string $id)
    {
        $req = UserRequest::findOrFail($id);
        $this->authorize('view', $req);
        $offers = TrainerOffer::where('user_request_id', $id)->latest()->get();
        return response()->json(['data' => $offers]);
    }

    public function store(StoreOfferRequest $request)
    {
        $offer = $this->service->create($request->user()->id, $request->validated());
        return response()->json(['data' => $offer], 201);
    }

    public function accept(Request $request, string $id)
    {
        $offer = TrainerOffer::findOrFail($id);
        $req = UserRequest::findOrFail($offer->user_request_id);
        $this->authorize('acceptOffer', $req);
        $this->service->accept($req, $offer);
        return response()->json(['data' => $req->fresh()]);
    }
}

