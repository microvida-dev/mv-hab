<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdditionalDocumentRequestRequest;
use App\Models\AdditionalDocumentRequest;
use App\Models\Application;
use App\Services\ApplicationActions\AdditionalDocumentRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AdditionalDocumentRequestController extends Controller
{
    public function __construct(private readonly AdditionalDocumentRequestService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', AdditionalDocumentRequest::class);

        return view('backoffice.additional-document-requests.index', [
            'requests' => AdditionalDocumentRequest::query()->with(['application', 'user'])->latest()->paginate(20),
        ]);
    }

    public function store(StoreAdditionalDocumentRequestRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('createBackoffice', AdditionalDocumentRequest::class);
        $this->service->create($application, $this->authenticatedUser($request), $request->validated());

        return back()->with('success', 'Pedido de documento adicional criado.');
    }
}
