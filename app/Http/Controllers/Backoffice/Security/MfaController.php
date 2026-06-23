<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmMfaRequest;
use App\Http\Requests\DisableMfaRequest;
use App\Http\Requests\EnableMfaRequest;
use App\Http\Requests\RegenerateRecoveryCodesRequest;
use App\Http\Requests\VerifyMfaChallengeRequest;
use App\Models\MfaDevice;
use App\Services\Security\MfaDeviceService;
use App\Services\Security\MfaEnforcementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MfaController extends Controller
{
    public function index(Request $request): View
    {
        return view('backoffice.security.mfa.index', [
            'devices' => $this->authenticatedUser($request)->mfaDevices()->latest()->get(),
            'requiresMfa' => app(MfaEnforcementService::class)->requiresMfa($this->authenticatedUser($request)),
            'sessionVerified' => app(MfaEnforcementService::class)->sessionVerified(),
        ]);
    }

    public function enable(EnableMfaRequest $request, MfaDeviceService $devices): View
    {
        $device = $devices->createTotpDevice($this->authenticatedUser($request), $request->validated('name') ?: 'Aplicação autenticadora');
        $recoveryCodes = $devices->regenerateRecoveryCodes($this->authenticatedUser($request));

        return view('backoffice.security.mfa.index', [
            'devices' => $this->authenticatedUser($request)->mfaDevices()->latest()->get(),
            'requiresMfa' => true,
            'sessionVerified' => false,
            'setupDevice' => $device,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function confirm(ConfirmMfaRequest $request, MfaDevice $mfaDevice, MfaDeviceService $devices, MfaEnforcementService $mfa): RedirectResponse
    {
        abort_unless($mfaDevice->user_id === $this->authenticatedUser($request)->id, 403);

        if (! $devices->confirm($mfaDevice, $request->validated('code'), $this->authenticatedUser($request))) {
            return back()->withErrors(['code' => 'Código MFA inválido.']);
        }

        $mfa->markVerified();

        return redirect()->route('backoffice.security.dashboard')->with('status', 'MFA confirmado.');
    }

    public function verify(VerifyMfaChallengeRequest $request, MfaDeviceService $devices, MfaEnforcementService $mfa): RedirectResponse
    {
        $code = $request->validated('code');
        $verified = $this->authenticatedUser($request)
            ->mfaDevices()
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->get()
            ->contains(fn (MfaDevice $device): bool => $devices->verifyTotp($device->secret_encrypted, $code));

        if (! $verified) {
            $verified = $devices->useRecoveryCode($this->authenticatedUser($request), $code);
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'Código MFA inválido.']);
        }

        $mfa->markVerified();

        return redirect()->intended(route('backoffice.security.dashboard'))->with('status', 'Sessão MFA validada.');
    }

    public function regenerate(RegenerateRecoveryCodesRequest $request, MfaDeviceService $devices): View
    {
        return view('backoffice.security.mfa.index', [
            'devices' => $this->authenticatedUser($request)->mfaDevices()->latest()->get(),
            'requiresMfa' => true,
            'sessionVerified' => app(MfaEnforcementService::class)->sessionVerified(),
            'recoveryCodes' => $devices->regenerateRecoveryCodes($this->authenticatedUser($request)),
        ]);
    }

    public function disable(DisableMfaRequest $request, MfaDevice $mfaDevice, MfaDeviceService $devices): RedirectResponse
    {
        abort_unless($mfaDevice->user_id === $this->authenticatedUser($request)->id, 403);
        $devices->disable($mfaDevice, $this->authenticatedUser($request));

        return back()->with('status', 'Dispositivo MFA desativado.');
    }
}
