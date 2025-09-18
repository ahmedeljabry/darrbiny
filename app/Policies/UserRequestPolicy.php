<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserRequest;

class UserRequestPolicy
{
    public function view(User $user, UserRequest $req): bool
    {
        return $req->user_id === $user->id || $user->hasRole('ADMIN');
    }

    public function update(User $user, UserRequest $req): bool
    {
        return $req->user_id === $user->id || $user->hasRole('ADMIN');
    }

    public function acceptOffer(User $user, UserRequest $req): bool
    {
        return $req->user_id === $user->id;
    }

    public function complete(User $user, UserRequest $req): bool
    {
        return $req->user_id === $user->id || $user->hasRole('ADMIN');
    }

    public function logDay(User $user, UserRequest $req): bool
    {
        return $user->hasRole('TRAINER');
    }

    public function approveDay(User $user, UserRequest $req): bool
    {
        return $req->user_id === $user->id;
    }
}

