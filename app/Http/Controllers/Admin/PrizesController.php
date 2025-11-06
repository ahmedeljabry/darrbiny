<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Reward;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class PrizesController extends BaseController
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $status = $request->query('status');
        
        $query = Reward::query();
        
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }
        
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }
        
        $prizes = $query->orderBy('order')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        
        return view('admin.prizes.index', compact('prizes', 'search', 'status'));
    }

    public function create()
    {
        return view('admin.prizes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'required_points' => ['required', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['active'] = $request->has('active');
        $data['order'] = $data['order'] ?? 0;

        if ($request->hasFile('image')) {
            $disk = config('filesystems.default', 'public');
            $path = $request->file('image')->store('prizes', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $request->file('image')->getMimeType(),
                'size' => $request->file('image')->getSize(),
            ]);
            $data['image'] = $path;
        }

        Reward::create($data);

        return redirect()->route('admin.prizes.index')
            ->with('status', 'تم إنشاء الجائزة بنجاح');
    }

    public function show(string $id)
    {
        $prize = Reward::with('redemptions.user')->findOrFail($id);
        return view('admin.prizes.show', compact('prize'));
    }

    public function edit(string $id)
    {
        $prize = Reward::findOrFail($id);
        return view('admin.prizes.edit', compact('prize'));
    }

    public function update(Request $request, string $id)
    {
        $prize = Reward::findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'required_points' => ['required', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $data['active'] = $request->has('active');
        $data['order'] = $data['order'] ?? 0;

        if ($request->hasFile('image')) {
            if ($prize->image) {
                Storage::disk(config('filesystems.default', 'public'))->delete($prize->image);
            }
            
            $disk = config('filesystems.default', 'public');
            $path = $request->file('image')->store('prizes', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $request->file('image')->getMimeType(),
                'size' => $request->file('image')->getSize(),
            ]);
            $data['image'] = $path;
        }

        $prize->update($data);

        return redirect()->route('admin.prizes.index')
            ->with('status', 'تم تحديث الجائزة بنجاح');
    }

    public function destroy(string $id)
    {
        $prize = Reward::findOrFail($id);
        
        if ($prize->image) {
            Storage::disk(config('filesystems.default', 'public'))->delete($prize->image);
        }
        
        $prize->delete();

        return redirect()->route('admin.prizes.index')
            ->with('status', 'تم حذف الجائزة بنجاح');
    }
}

