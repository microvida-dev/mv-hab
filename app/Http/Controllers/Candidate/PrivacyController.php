<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataSubjectRequestRequest;
use App\Http\Requests\StoreUserConsentRequest;
use App\Http\Requests\WithdrawUserConsentRequest;
use App\Models\ConsentPurpose;
use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\UserConsent;
use App\Services\Rgpd\DataExportService;
use App\Services\Rgpd\DataSubjectRequestService;
use App\Services\Rgpd\UserConsentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.privacy.index', [
            'purposes' => ConsentPurpose::query()->where('is_active', true)->latest()->get(),
            'consents' => $this->authenticatedUser($request)->consents()->with('purpose')->latest()->get(),
            'requests' => $this->authenticatedUser($request)->dataSubjectRequests()->with('exports')->latest('received_at')->get(),
        ]);
    }

    public function storeRequest(StoreDataSubjectRequestRequest $request, DataSubjectRequestService $requests): RedirectResponse
    {
        $rgpdRequest = $requests->create($request->validated(), $this->authenticatedUser($request), $this->authenticatedUser($request));

        return redirect()->route('candidate.privacy.requests.show', $rgpdRequest)->with('status', 'Pedido RGPD registado.');
    }

    public function showRequest(Request $request, DataSubjectRequest $dataSubjectRequest): View
    {
        abort_unless($dataSubjectRequest->user_id === $this->authenticatedUser($request)->id, 403);

        return view('candidate.privacy.request', [
            'requestRecord' => $dataSubjectRequest->load('actions', 'exports'),
        ]);
    }

    public function grantConsent(StoreUserConsentRequest $request, UserConsentService $consents): RedirectResponse
    {
        $validated = $request->validated();

        $purpose = ConsentPurpose::query()
            ->where('is_active', true)
            ->findOrFail((int) $validated['consent_purpose_id']);

        $consents->grant(
            $this->authenticatedUser($request),
            $purpose,
            $validated['text_snapshot'],
        );

        return back()->with('status', 'Consentimento registado.');
    }

    public function withdrawConsent(WithdrawUserConsentRequest $request, UserConsent $userConsent, UserConsentService $consents): RedirectResponse
    {
        abort_unless($userConsent->user_id === $this->authenticatedUser($request)->id, 403);
        $consents->withdraw($userConsent, $this->authenticatedUser($request));

        return back()->with('status', 'Consentimento retirado.');
    }

    public function generateExport(Request $request, DataSubjectRequest $dataSubjectRequest, DataExportService $exports): RedirectResponse
    {
        abort_unless($dataSubjectRequest->user_id === $this->authenticatedUser($request)->id, 403);
        $package = $exports->generate($dataSubjectRequest, $this->authenticatedUser($request));

        return redirect()->route('candidate.privacy.exports.show', $package)->with('status', 'Exportação de dados gerada.');
    }

    public function showExport(Request $request, DataExportPackage $dataExportPackage): View
    {
        abort_unless($dataExportPackage->user_id === $this->authenticatedUser($request)->id, 403);

        return view('candidate.privacy.export', ['package' => $dataExportPackage->load('request')]);
    }

    public function downloadExport(Request $request, DataExportPackage $dataExportPackage, DataExportService $exports): StreamedResponse
    {
        abort_unless($dataExportPackage->user_id === $this->authenticatedUser($request)->id, 403);

        return $exports->download($dataExportPackage, $this->authenticatedUser($request));
    }
}
