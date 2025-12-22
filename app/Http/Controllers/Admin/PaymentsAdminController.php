<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PaymentsReportExport;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class PaymentsAdminController extends BaseController
{
    public function index(Request $request)
    {
        $q = Payment::with(['user', 'userRequest'])->latest();
        if ($type = $request->query('type')) $q->where('type', $type);
        if ($status = $request->query('status')) $q->where('status', $status);
        
        if ($request->query('export') === 'excel') {
            $allPayments = Payment::with(['user', 'userRequest'])
                ->when($type, fn($query) => $query->where('type', $type))
                ->when($status, fn($query) => $query->where('status', $status))
                ->latest()
                ->get();

            return Excel::download(
                new PaymentsReportExport($allPayments),
                'payments-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        $payments = $q->paginate(20);
        return view('admin.payments.index', compact('payments'));
    }
}

