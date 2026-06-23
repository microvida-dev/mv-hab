<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $email_for_notifications
 * @property string|null $phone_for_notifications
 * @property string|null $postal_address
 */
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'allow_in_app',
        'allow_email',
        'allow_sms',
        'allow_postal',
        'preferred_channel',
        'email_for_notifications',
        'phone_for_notifications',
        'postal_address',
        'consented_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_in_app' => 'boolean',
            'allow_email' => 'boolean',
            'allow_sms' => 'boolean',
            'allow_postal' => 'boolean',
            'preferred_channel' => CommunicationChannel::class,
            'consented_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
