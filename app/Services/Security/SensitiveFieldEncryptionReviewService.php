<?php

namespace App\Services\Security;

use App\Enums\EncryptedFieldStatus;
use App\Models\EncryptedFieldRegistry;
use App\Models\User;

class SensitiveFieldEncryptionReviewService
{
    private const BLOCKED_FIELDS = ['email', 'name'];

    public function register(string $modelClass, string $table, string $field, ?User $actor = null, ?string $notes = null): EncryptedFieldRegistry
    {
        $blocked = in_array($field, self::BLOCKED_FIELDS, true);

        return EncryptedFieldRegistry::query()->updateOrCreate(
            ['table_name' => $table, 'field_name' => $field],
            [
                'model_class' => $modelClass,
                'encryption_status' => $blocked ? EncryptedFieldStatus::BlockedBySearchRequirement : EncryptedFieldStatus::Planned,
                'search_strategy' => $blocked ? 'Campo usado em login/pesquisa; exige estratégia dedicada.' : 'Avaliar índice hash auxiliar quando necessário.',
                'notes' => $notes,
                'migration_required' => ! $blocked,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ],
        );
    }

    public function seedDefaultRegistry(?User $actor = null): void
    {
        /** @var list<array{0: string, 1: string, 2: string}> $fields */
        $fields = [
            [User::class, 'users', 'email'],
            [User::class, 'users', 'name'],
            ['App\\Models\\AdhesionRegistration', 'adhesion_registrations', 'tax_number'],
            ['App\\Models\\HouseholdMember', 'household_members', 'birth_date'],
            ['App\\Models\\IncomeRecord', 'income_records', 'notes'],
        ];

        foreach ($fields as [$model, $table, $field]) {
            $this->register($model, $table, $field, $actor, 'Registo inicial Sprint 18 — sujeito a validação técnica.');
        }
    }

    /**
     * @return array{
     *     registered_fields: int,
     *     planned_fields: int,
     *     encrypted_fields: int,
     *     blocked_by_search: int,
     *     status: string,
     *     recommendations: list<string>
     * }
     */
    public function review(): array
    {
        return [
            'registered_fields' => EncryptedFieldRegistry::query()->count(),
            'planned_fields' => EncryptedFieldRegistry::query()->where('encryption_status', EncryptedFieldStatus::Planned->value)->count(),
            'encrypted_fields' => EncryptedFieldRegistry::query()->where('encryption_status', EncryptedFieldStatus::Encrypted->value)->count(),
            'blocked_by_search' => EncryptedFieldRegistry::query()->where('encryption_status', EncryptedFieldStatus::BlockedBySearchRequirement->value)->count(),
            'status' => 'review_required',
            'recommendations' => [
                'Não encriptar diretamente campos usados em login sem estratégia de índice hash.',
                'Priorizar dados fiscais, documentos e notas clínicas/deficiência para revisão Sprint 18/produção.',
            ],
        ];
    }
}
