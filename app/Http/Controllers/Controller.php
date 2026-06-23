<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function currentUser(): User
    {
        $request = request();

        return $this->authenticatedUser($request);
    }
}
