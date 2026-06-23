<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministrativeProcessNoteRequest;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use App\Services\Administrative\AdministrativeProcessNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdministrativeProcessNoteController extends Controller
{
    public function __construct(private readonly AdministrativeProcessNoteService $noteService) {}

    public function store(StoreAdministrativeProcessNoteRequest $request, AdministrativeProcess $administrativeProcess): RedirectResponse
    {
        Gate::authorize('create', AdministrativeProcessNote::class);
        $this->noteService->create($administrativeProcess, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Nota criada.');
    }

    public function update(StoreAdministrativeProcessNoteRequest $request, AdministrativeProcessNote $administrativeProcessNote): RedirectResponse
    {
        Gate::authorize('update', $administrativeProcessNote);
        $this->noteService->update($administrativeProcessNote, $request->validated(), $this->authenticatedUser($request));

        return back()->with('success', 'Nota atualizada.');
    }

    public function destroy(Request $request, AdministrativeProcessNote $administrativeProcessNote): RedirectResponse
    {
        Gate::authorize('delete', $administrativeProcessNote);
        $this->noteService->delete($administrativeProcessNote, $this->authenticatedUser($request));

        return back()->with('success', 'Nota removida.');
    }
}
