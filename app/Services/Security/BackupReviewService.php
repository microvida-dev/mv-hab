<?php

namespace App\Services\Security;

use App\Models\BackupReview;
use App\Models\User;
use Illuminate\Support\Str;

class BackupReviewService
{
    /**
     * @param array{
     *     status?: string,
     *     environment?: string,
     *     backup_scope?: string|null,
     *     frequency?: string|null,
     *     retention_period?: string|null,
     *     last_backup_at?: \DateTimeInterface|string|null,
     *     last_restore_test_at?: \DateTimeInterface|string|null,
     *     findings?: string|null,
     *     recommendations?: string|null
     * } $data
     */
    public function create(User $actor, array $data): BackupReview
    {
        return BackupReview::query()->create([
            'review_number' => 'BKP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => $data['status'] ?? 'reviewed',
            'environment' => $data['environment'] ?? app()->environment(),
            'backup_scope' => $data['backup_scope'] ?? 'Base de dados e storage privado. DEMO — SUJEITO A VALIDAÇÃO DO MUNICÍPIO/DPO.',
            'frequency' => $data['frequency'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'last_backup_at' => $data['last_backup_at'] ?? null,
            'last_restore_test_at' => $data['last_restore_test_at'] ?? null,
            'findings' => $data['findings'] ?? null,
            'recommendations' => $data['recommendations'] ?? 'Formalizar periodicidade, retenção, segregação de acessos e teste de restore.',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
    }
}
