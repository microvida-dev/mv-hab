<?php

namespace App\Models;

use App\Enums\AdministrativeNoteVisibility;
use Database\Factories\AdministrativeProcessNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeProcessNote extends Model
{
    /** @use HasFactory<AdministrativeProcessNoteFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'visibility',
        'note_type',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => AdministrativeNoteVisibility::class,
        ];
    }

    /**
     * @return BelongsTo<AdministrativeProcess, $this>
     */
    public function administrativeProcess(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcess::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
