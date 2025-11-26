<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletsController extends BaseController
{
    public function __construct(private readonly WalletService $wallets) {}

    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('admin.wallets.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $this->wallets->addAdjustment($user, (int) $data['amount'], $request->user(), $data['notes'] ?? null);

        return back()->with('status', 'تم إضافة الرصيد إلى المحفظة');
    }
}
