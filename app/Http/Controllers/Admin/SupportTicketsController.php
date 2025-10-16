<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class SupportTicketsController extends BaseController
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');
        $q = SupportTicket::with('user')->latest();
        if ($status) $q->where('status', $status);
        if ($search) {
            $q->where(function($w) use ($search){
                $w->where('subject','like',"%$search%")
                  ->orWhere('name','like',"%$search%")
                  ->orWhere('email','like',"%$search%")
                  ->orWhere('phone_with_cc','like',"%$search%");
            });
        }
        $tickets = $q->paginate(20)->withQueryString();
        return view('admin.support.index', [
            'tickets' => $tickets,
            'status' => $status,
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

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'author_type' => 'admin',
            'message' => $data['message'],
        ]);

        if (!empty($data['status'])) {
            $ticket->update(['status' => $data['status']]);
        }

        return back()->with('status', 'تم إضافة الرد وتحديث الحالة');
    }
}
