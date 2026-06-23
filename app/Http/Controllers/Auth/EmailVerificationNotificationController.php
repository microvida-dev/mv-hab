<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($this->authenticatedUser($request)->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $this->authenticatedUser($request)->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
