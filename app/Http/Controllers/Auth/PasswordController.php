<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->authenticatedUser($request)->update([
            'password' => Hash::make($validated['password']),
        ]);

        app(AuditTrailService::class)->record(
            'auth.password.changed',
            $this->authenticatedUser($request),
            AuditEventCategory::Security,
            AuditEventSeverity::Security,
            'Password alterada pelo utilizador autenticado.',
            subject: $this->authenticatedUser($request),
        );

        return back()->with('status', 'password-updated');
    }
}
