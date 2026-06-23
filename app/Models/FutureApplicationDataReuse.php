<?php

namespace App\Models;

use App\Enums\DataReuseStatus;
use Database\Factories\FutureApplicationDataReuseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FutureApplicationDataReuse extends Model
{
    /** @use HasFactory<FutureApplicationDataReuseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['sections'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DataReuseStatus::class,
            'sections' => 'array',
            'source_snapshot' => 'array',
            'warnings' => 'array',
            'confirmed_at' => 'datetime',
            'applied_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function sourceApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'source_application_id');
    }

    /** @return BelongsTo<CandidateDataReuseProfile, $this> */
    public function sourceReuseProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateDataReuseProfile::class, 'source_reuse_profile_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function targetApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'target_application_id');
    }
}
