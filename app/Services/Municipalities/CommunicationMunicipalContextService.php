<?php

namespace App\Services\Municipalities;

use App\Models\Allocation;
use App\Models\Application;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\DefinitiveList;
use App\Models\DocumentSubmission;
use App\Models\Hearing;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\OfficialNotification;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\ProvisionalList;
use App\Models\SupportTicket;
use App\Models\TenantCommunication;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class CommunicationMunicipalContextService
{
    public function forDelivery(
        CommunicationDelivery $delivery,
    ): ?int {
        $delivery->loadMissing('communication');
        $communication = $delivery->communication;

        return $communication instanceof CommunicationLog
            ? $this->forCommunication($communication)
            : null;
    }

    public function forCommunication(
        CommunicationLog $communication,
    ): ?int {
        $communication->loadMissing([
            'creator',
            'recipient',
            'related',
            'template',
            'templateVersion.template',
        ]);

        $storedMunicipalityId = $this->positiveId(
            $communication->municipality_id,
        );

        if ($storedMunicipalityId === null) {
            return null;
        }

        $resolvedMunicipalityId = $this->forSources(
            creator: $communication->creator,
            related: $communication->related,
            recipient: $communication->recipient,
            template: $communication->template,
            version: $communication->templateVersion,
        );

        return $resolvedMunicipalityId === $storedMunicipalityId
            ? $storedMunicipalityId
            : null;
    }

    public function forSources(
        ?User $creator,
        ?Model $related,
        ?User $recipient,
        ?NotificationTemplate $template = null,
        ?NotificationTemplateVersion $version = null,
    ): ?int {
        $sourceIds = [];
        $creatorMunicipalityId = $creator instanceof User
            ? $this->positiveId($creator->municipality_id)
            : null;

        if ($creatorMunicipalityId !== null) {
            $sourceIds[] = $creatorMunicipalityId;
        }

        if ($related instanceof Model) {
            $relatedMunicipalityId = $this->forModel($related);

            if ($relatedMunicipalityId === null) {
                return null;
            }

            $sourceIds[] = $relatedMunicipalityId;
        }

        $municipalityId = $this->single($sourceIds);

        if ($municipalityId === null) {
            return null;
        }

        if (
            ! $recipient instanceof User
            || $this->positiveId($recipient->municipality_id)
                !== $municipalityId
        ) {
            return null;
        }

        $templateMunicipalityIds = [];

        if ($template instanceof NotificationTemplate) {
            $templateMunicipalityId = $this->templateMunicipalityId(
                $template,
            );

            if ($templateMunicipalityId === false) {
                return null;
            }

            if (is_int($templateMunicipalityId)) {
                $templateMunicipalityIds[] = $templateMunicipalityId;
            }
        }

        if ($version instanceof NotificationTemplateVersion) {
            $version->loadMissing('template');
            $versionTemplate = $version->template;

            if (! $versionTemplate instanceof NotificationTemplate) {
                return null;
            }

            if (
                $template instanceof NotificationTemplate
                && (int) $template->id !== (int) $versionTemplate->id
            ) {
                return null;
            }

            $versionMunicipalityId = $this->templateMunicipalityId(
                $versionTemplate,
            );

            if ($versionMunicipalityId === false) {
                return null;
            }

            if (is_int($versionMunicipalityId)) {
                $templateMunicipalityIds[] = $versionMunicipalityId;
            }
        }

        return $templateMunicipalityIds === []
            || $this->single([
                $municipalityId,
                ...$templateMunicipalityIds,
            ]) === $municipalityId
            ? $municipalityId
            : null;
    }

    public function forModel(Model $model): ?int
    {
        if ($model->exists && $model->getKey() !== null) {
            $fresh = $model->newQuery()->find($model->getKey());

            if (! $fresh instanceof Model) {
                return null;
            }

            $model = $fresh;
        }

        $direct = $this->positiveId(
            $model->getAttribute('municipality_id'),
        );

        if ($direct !== null) {
            return $direct;
        }

        $domainIds = $this->domainMunicipalityIds($model);

        if ($domainIds === false) {
            return null;
        }

        if ($domainIds !== []) {
            return $this->single($domainIds);
        }

        $ids = match (true) {
            $model instanceof Application => [
                $this->fromProgram($model->getAttribute('program_id')),
                $this->fromContest($model->getAttribute('contest_id')),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof Contest => [
                $this->fromProgram($model->getAttribute('program_id')),
            ],
            $model instanceof Contract => [
                $this->fromProgram($model->getAttribute('program_id')),
                $this->fromContest($model->getAttribute('contest_id')),
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
                $this->fromHousingUnit(
                    $model->getAttribute('housing_unit_id'),
                ),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof DocumentSubmission => [
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof SupportTicket => [
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
                $this->fromContest($model->getAttribute('contest_id')),
                $this->fromHousingUnit(
                    $model->getAttribute('housing_unit_id'),
                ),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof TenantCommunication => [
                $this->fromContract(
                    $model->getAttribute('lease_contract_id'),
                ),
                $this->fromHousingUnit(
                    $model->getAttribute('housing_unit_id'),
                ),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof MaintenanceRequest,
            $model instanceof PropertyInspection => [
                $this->fromHousingUnit(
                    $model->getAttribute('housing_unit_id'),
                ),
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
            ],
            $model instanceof Complaint,
            $model instanceof Hearing => [
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
                $this->fromUser(
                    $model->getAttribute('candidate_user_id')
                        ?? $model->getAttribute('user_id'),
                ),
            ],
            $model instanceof OfficialNotification => [
                $this->fromApplication(
                    $model->getAttribute('application_id'),
                ),
                $this->fromUser($model->getAttribute('user_id')),
            ],
            $model instanceof NotificationTemplate => [
                $this->forNotificationTemplate($model),
            ],
            $model instanceof User => [
                $this->positiveId($model->municipality_id),
            ],
            default => [],
        };

        return $this->single($ids);
    }

    /**
     * @return list<int>|false
     */
    private function domainMunicipalityIds(Model $model): array|false
    {
        $resolvers = [
            'program_id' => fn (mixed $id): ?int => $this->fromProgram($id),
            'contest_id' => fn (mixed $id): ?int => $this->fromContest($id),
            'application_id' => fn (mixed $id): ?int => $this->fromApplication($id),
            'housing_unit_id' => fn (mixed $id): ?int => $this->fromHousingUnit($id),
            'lease_contract_id' => fn (mixed $id): ?int => $this->fromContract($id),
            'contract_id' => fn (mixed $id): ?int => $this->fromContract($id),
            'complaint_id' => fn (mixed $id): ?int => $this->fromRelatedModel(Complaint::class, $id),
            'hearing_id' => fn (mixed $id): ?int => $this->fromRelatedModel(Hearing::class, $id),
            'support_ticket_id' => fn (mixed $id): ?int => $this->fromRelatedModel(SupportTicket::class, $id),
            'allocation_id' => fn (mixed $id): ?int => $this->fromRelatedModel(Allocation::class, $id),
            'provisional_list_id' => fn (mixed $id): ?int => $this->fromRelatedModel(ProvisionalList::class, $id),
            'definitive_list_id' => fn (mixed $id): ?int => $this->fromRelatedModel(DefinitiveList::class, $id),
        ];
        $ids = [];

        foreach ($resolvers as $attribute => $resolver) {
            $value = $model->getAttribute($attribute);

            if ($value === null) {
                continue;
            }

            $id = $this->positiveId($value);
            $municipalityId = $id !== null ? $resolver($id) : null;

            if ($municipalityId === null) {
                return false;
            }

            $ids[] = $municipalityId;
        }

        return array_values(array_unique($ids));
    }

    private function forNotificationTemplate(
        NotificationTemplate $template,
    ): ?int {
        return $this->single([
            $this->positiveId($template->municipality_id),
            $this->fromProgram($template->program_id),
            $this->fromContest($template->contest_id),
        ]);
    }

    private function templateHasMunicipalContext(
        NotificationTemplate $template,
    ): bool {
        return $template->municipality_id !== null
            || $template->program_id !== null
            || $template->contest_id !== null;
    }

    private function templateMunicipalityId(
        NotificationTemplate $template,
    ): int|false|null {
        $municipalityId = $this->forNotificationTemplate($template);

        if (
            $this->templateHasMunicipalContext($template)
            && $municipalityId === null
        ) {
            return false;
        }

        return $municipalityId;
    }

    private function fromProgram(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        return $id === null
            ? null
            : $this->positiveId(
                Program::query()
                    ->whereKey($id)
                    ->value('municipality_id'),
            );
    }

    private function fromContest(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        if ($id === null) {
            return null;
        }

        return $this->fromProgram(
            Contest::query()->whereKey($id)->value('program_id'),
        );
    }

    private function fromApplication(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        if ($id === null) {
            return null;
        }

        $application = Application::query()
            ->whereKey($id)
            ->first(['id', 'program_id', 'contest_id', 'user_id']);

        return $application instanceof Application
            ? $this->forModel($application)
            : null;
    }

    private function fromContract(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        if ($id === null) {
            return null;
        }

        $contract = Contract::query()
            ->whereKey($id)
            ->first([
                'id',
                'program_id',
                'contest_id',
                'application_id',
                'housing_unit_id',
                'user_id',
            ]);

        return $contract instanceof Contract
            ? $this->forModel($contract)
            : null;
    }

    private function fromHousingUnit(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        return $id === null
            ? null
            : $this->positiveId(
                HousingUnit::query()
                    ->whereKey($id)
                    ->value('municipality_id'),
            );
    }

    private function fromUser(mixed $id): ?int
    {
        $id = $this->positiveId($id);

        return $id === null
            ? null
            : $this->positiveId(
                User::query()
                    ->whereKey($id)
                    ->value('municipality_id'),
            );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function fromRelatedModel(
        string $modelClass,
        mixed $id,
    ): ?int {
        $id = $this->positiveId($id);

        if ($id === null) {
            return null;
        }

        $model = $modelClass::query()->find($id);

        return $model instanceof Model
            ? $this->forModel($model)
            : null;
    }

    /**
     * @param  list<int|null>  $ids
     */
    private function single(array $ids): ?int
    {
        $ids = array_values(array_unique(array_filter(
            $ids,
            static fn (?int $id): bool => $id !== null,
        )));

        return count($ids) === 1 ? $ids[0] : null;
    }

    private function positiveId(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }
}
