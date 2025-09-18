<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ContentController extends BaseController
{
    public function index()
    {
        $settings = Setting::orderBy('key')->get();
        return view('admin.content.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'items' => ['required','array'],
            'items.*.key' => ['required','string','max:128'],
            'items.*.value' => ['nullable','string'],
        ]);
        foreach ($data['items'] as $item) {
            Setting::updateOrCreate(['key' => $item['key']], ['value' => $item['value']]);
        }
        return back()->with('status', 'Content updated');
    }
}

