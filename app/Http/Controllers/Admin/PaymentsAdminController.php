<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class PaymentsAdminController extends BaseController
{
    public function index(Request $request)
    {
        $q = Payment::query()->latest();
        if ($type = $request->query('type')) $q->where('type', $type);
        if ($status = $request->query('status')) $q->where('status', $status);
        $payments = $q->paginate(20);
        return view('admin.payments.index', compact('payments'));
    }
}

