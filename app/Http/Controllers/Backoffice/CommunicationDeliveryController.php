<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPostalDeliveryRequest;
use App\Models\CommunicationDelivery;
use App\Services\Notifications\CommunicationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommunicationDeliveryController extends Controller
{
    public function __construct(private readonly CommunicationDeliveryService $service) {}

    public function resend(CommunicationDelivery $communicationDelivery): RedirectResponse
    {
        Gate::authorize('update', $communicationDelivery);
        $this->service->resend($communicationDelivery, $this->currentUser());

        return back()->with('success', 'Nova tentativa registada.');
    }

    public function registerPostal(RegisterPostalDeliveryRequest $request, CommunicationDelivery $communicationDelivery): RedirectResponse
    {
        Gate::authorize('update', $communicationDelivery);
        $this->service->registerPostal($communicationDelivery, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Envio postal registado.');
    }
}
