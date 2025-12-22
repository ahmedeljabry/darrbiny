<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\SubscriptionsReportExport;
use App\Models\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class SubscriptionsController extends BaseController
{
    public function index(Request $request)
    {
        $q = UserRequest::with('plan', 'user')->latest();

        $scope = $request->query('scope');
        $status = $request->query('status');

        if ($scope === 'active') {
            $q->whereIn('status', [
                UserRequest::STATUS_IN_TRAINING,
                UserRequest::STATUS_PAID,
                UserRequest::STATUS_OFFER_SELECTED,
            ]);
        } elseif ($scope === 'completed') {
            $q->where('status', UserRequest::STATUS_COMPLETED);
        } elseif ($scope === 'awaiting_offers') {
            $q->where('status', UserRequest::STATUS_AWAITING_OFFERS);
        } elseif ($status) {
            $q->where('status', $status);
        }

        $subs = $q->paginate(20)->withQueryString();

        if ($request->query('export') === 'excel') {
            $allSubs = UserRequest::with('plan', 'user')
                ->when($scope === 'active', function ($query) {
                    $query->whereIn('status', [
                        UserRequest::STATUS_IN_TRAINING,
                        UserRequest::STATUS_PAID,
                        UserRequest::STATUS_OFFER_SELECTED,
                    ]);
                })
                ->when($scope === 'completed', fn($query) => $query->where('status', UserRequest::STATUS_COMPLETED))
                ->when($scope === 'awaiting_offers', fn($query) => $query->where('status', UserRequest::STATUS_AWAITING_OFFERS))
                ->when($status, fn($query) => $query->where('status', $status))
                ->latest()
                ->get();

            return Excel::download(
                new SubscriptionsReportExport($allSubs),
                'subscriptions-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        return view('admin.subscriptions.index', compact('subs', 'scope', 'status'));
    }
}
