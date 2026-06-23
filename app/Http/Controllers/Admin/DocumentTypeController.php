<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentTypeController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', DocumentType::class);

        $documentTypes = DocumentType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.document-types.index', compact('documentTypes'));
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentType::class);

        return view('admin.document-types.create', $this->formData());
    }

    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        $documentType = DocumentType::query()->create($this->normalizedData($request->validated()));
        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $documentType,
            module: 'documents',
            action: 'document_type_create',
            description: 'Tipo documental criado.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.document-types.index')
            ->with('success', 'Tipo documental criado.');
    }

    public function edit(DocumentType $documentType): View
    {
        Gate::authorize('update', $documentType);

        return view('admin.document-types.edit', [
            'documentType' => $documentType,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->update($this->normalizedData($request->validated()));
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $documentType,
            module: 'documents',
            action: 'document_type_update',
            description: 'Tipo documental atualizado.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.document-types.index')
            ->with('success', 'Tipo documental atualizado.');
    }

    public function destroy(Request $request, DocumentType $documentType): RedirectResponse
    {
        Gate::authorize('delete', $documentType);
        $documentType->delete();
        $this->auditLogger->record(
            event: AuditEvents::DELETE,
            auditable: $documentType,
            module: 'documents',
            action: 'document_type_delete',
            description: 'Tipo documental removido por soft delete.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return to_route('admin.document-types.index')
            ->with('success', 'Tipo documental removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => DocumentCategory::options(),
            'appliesTo' => DocumentAppliesTo::options(),
            'mimeTypes' => [
                'application/pdf' => 'PDF',
                'image/jpeg' => 'JPG/JPEG',
                'image/png' => 'PNG',
                'image/webp' => 'WEBP',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedData(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['is_required_by_default'] = (bool) ($data['is_required_by_default'] ?? false);
        $data['requires_expiry_date'] = (bool) ($data['requires_expiry_date'] ?? false);
        $data['requires_issue_date'] = (bool) ($data['requires_issue_date'] ?? false);
        $data['allowed_mime_types'] = $data['allowed_mime_types'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
