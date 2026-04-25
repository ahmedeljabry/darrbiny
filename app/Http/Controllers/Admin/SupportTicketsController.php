<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\SupportTicketsExport;
use App\Models\SupportTicket;
use App\Modules\Support\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class SupportTicketsController extends BaseController
{
    public function __construct(private readonly SupportTicketService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $q = SupportTicket::with('user')->withCount('messages')->latest();
        if ($status) $q->where('status', $status);
        if ($search) {
            $q->where(function($w) use ($search){
                $w->where('subject','like',"%$search%")
                  ->orWhere('name','like',"%$search%")
                  ->orWhere('email','like',"%$search%")
                  ->orWhere('phone_with_cc','like',"%$search%");
            });
        }
        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }
        
        if ($request->query('export') === 'excel') {
            $allTickets = $q->get();
            return Excel::download(
                new SupportTicketsExport($allTickets),
                'support-tickets-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        $tickets = $q->paginate(20)->withQueryString();
        return view('admin.support.index', [
            'tickets' => $tickets,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function show(string $id)
    {
        $ticket = SupportTicket::with(['user','messages.user'])->findOrFail($id);
        return view('admin.support.show', compact('ticket'));
    }

    public function reply(Request $request, string $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $data = $request->validate([
            'message' => ['required','string','max:2000'],
            'status' => ['nullable','in:open,pending,closed'],
        ]);

        $this->service->addMessage(
            $ticket,
            $request->user(),
            (string) $data['message'],
            $data['status'] ?? null,
            true
        );

        return back()->with('status', 'تم إضافة الرد وتحديث الحالة');
    }
}
