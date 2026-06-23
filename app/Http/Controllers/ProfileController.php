<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $this->authenticatedUser($request),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->authenticatedUser($request)->fill($request->validated());

        if ($this->authenticatedUser($request)->isDirty('email')) {
            $this->authenticatedUser($request)->email_verified_at = null;
        }

        $this->authenticatedUser($request)->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($this->authenticatedUser($request)->adhesionRegistration()->exists()) {
            return back()->withErrors([
                'registration' => 'A eliminação da conta requer tratamento administrativo enquanto existir um Registo de Adesão, incluindo registos removidos.',
            ], 'userDeletion');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $this->authenticatedUser($request);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
