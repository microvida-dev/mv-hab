<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseDocumentData;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class CaseDocumentSummaryService
{
    public function __construct(private readonly CaseAuthorizationService $authorization) {}

    /**
     * @return list<CaseDocumentData>
     */
    public function forCase(User $user, string $caseType, Model $case): array
    {
        if (! $this->authorization->hasPermission($user, 'documents.view') && ! in_array($caseType, ['maintenance_request', 'inspection', 'support_ticket'], true)) {
            return [];
        }

        if ($case instanceof DocumentSubmission) {
            return [$this->documentSubmission($case)];
        }

        $items = [];

        if ($case instanceof Contract) {
            $items[] = $this->countItem('Documentos contratuais', $case->documents()->count() + $case->generatedDocuments()->count(), 'Documentos associados por rotas protegidas.');
        }

        if ($case instanceof MaintenanceRequest) {
            $items[] = $this->countItem('Anexos de manutenção', $case->attachments()->count(), 'Anexos técnicos protegidos.');
        }

        if ($case instanceof PropertyInspection) {
            $items[] = $this->countItem('Evidências de vistoria', $case->attachments()->count(), 'Fotografias/anexos autorizados.');
            $items[] = $this->countItem('Relatório de vistoria', $case->report()->exists() ? 1 : 0, 'Relatório técnico protegido.');
        }

        if ($case instanceof Complaint) {
            $items[] = $this->countItem('Anexos de reclamação', $case->attachments()->count(), 'Anexos privados da reclamação.');
        }

        if ($case instanceof SupportTicket) {
            $items[] = $this->countItem('Anexos de apoio', $case->attachments()->count(), 'Anexos privados do pedido de apoio.');
        }

        if ($case instanceof HousingUnit) {
            $items[] = $this->countItem('Documentos públicos', $case->publicDocuments()->count(), 'Documentos explicitamente publicados.');
        }

        if ($case instanceof DataSubjectRequest) {
            $items[] = $this->countItem('Exportações RGPD', $case->exports()->count(), 'Exportações privadas e auditadas.');
        }

        return array_values(array_filter($items));
    }

    private function documentSubmission(DocumentSubmission $document): CaseDocumentData
    {
        $route = Route::has('admin.document-reviews.show') ? 'admin.document-reviews.show' : null;

        return new CaseDocumentData(
            label: $document->title ?: 'Documento submetido',
            status: $this->enumLabel($document->status),
            description: 'Documento privado acessível apenas por rota protegida.',
            route: $route,
            routeParameter: $route !== null ? $document : null,
        );
    }

    private function countItem(string $label, int $count, string $description): ?CaseDocumentData
    {
        if ($count <= 0) {
            return null;
        }

        return new CaseDocumentData($label.' ('.$count.')', 'completed', $description);
    }

    private function enumLabel(mixed $value): string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return (string) $value->label();
        }

        if ($value instanceof \BackedEnum) {
            return str((string) $value->value)->replace('_', ' ')->title()->toString();
        }

        return str((string) $value)->replace('_', ' ')->title()->toString();
    }
}
