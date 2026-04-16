<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RatingsAdminController extends BaseController
{
    public function index(Request $request)
    {
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        $ratings = Rating::with(['user', 'trainer', 'userRequest'])
            ->when($name !== '', function ($query) use ($name) {
                $query->where(function ($nestedQuery) use ($name) {
                    $nestedQuery->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $name . '%'))
                        ->orWhereHas('trainer', fn ($trainerQuery) => $trainerQuery->where('name', 'like', '%' . $name . '%'));
                });
            })
            ->when($phone !== '', function ($query) use ($phone) {
                $query->where(function ($nestedQuery) use ($phone) {
                    $nestedQuery->whereHas('user', fn ($userQuery) => $userQuery->where('phone_with_cc', 'like', '%' . $phone . '%'))
                        ->orWhereHas('trainer', fn ($trainerQuery) => $trainerQuery->where('phone_with_cc', 'like', '%' . $phone . '%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ratings.index', compact('ratings', 'name', 'phone'));
    }

    public function create()
    {
        $users = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'user');
        })->orderBy('name')->get();
        
        $trainers = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'trainer');
        })->orderBy('name')->get();
        
        $userRequests = \App\Models\UserRequest::with(['user', 'trainer'])
            ->latest()
            ->limit(100)
            ->get();
        
        return view('admin.ratings.create', compact('users', 'trainers', 'userRequests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'trainer_id' => ['required', 'uuid', 'exists:users,id'],
            'user_request_id' => ['required', 'uuid', 'exists:user_requests,id'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Rating::where('user_id', $data['user_id'])
            ->where('trainer_id', $data['trainer_id'])
            ->where('user_request_id', $data['user_request_id'])
            ->first();

        if ($existing) {
            return back()->withErrors(['user_request_id' => 'يوجد تقييم موجود بالفعل لهذا الطلب.'])->withInput();
        }

        Rating::create($data);

        return redirect()->route('admin.ratings.index')->with('status', 'تم إنشاء التقييم بنجاح.');
    }

    public function edit(Rating $rating)
    {
        $users = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'user');
        })->orderBy('name')->get();
        
        $trainers = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'trainer');
        })->orderBy('name')->get();
        
        $userRequests = \App\Models\UserRequest::with(['user', 'trainer'])
            ->latest()
            ->limit(100)
            ->get();
        
        return view('admin.ratings.edit', compact('rating', 'users', 'trainers', 'userRequests'));
    }

    public function update(Rating $rating, Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'trainer_id' => ['required', 'uuid', 'exists:users,id'],
            'user_request_id' => ['required', 'uuid', 'exists:user_requests,id'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Rating::where('user_id', $data['user_id'])
            ->where('trainer_id', $data['trainer_id'])
            ->where('user_request_id', $data['user_request_id'])
            ->where('id', '!=', $rating->id)
            ->first();

        if ($existing) {
            return back()->withErrors(['user_request_id' => 'يوجد تقييم موجود بالفعل لهذا الطلب.'])->withInput();
        }

        $rating->update($data);

        return redirect()->route('admin.ratings.index')->with('status', 'تم تحديث التقييم بنجاح.');
    }

    public function destroy(Rating $rating)
    {
        $rating->delete();
        return back()->with('status', 'تم حذف التقييم بنجاح.');
    }
}
