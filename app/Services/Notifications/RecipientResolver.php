<?php

namespace App\Services\Notifications;

use App\Models\NotificationEventRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RecipientResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, User>
     */
    public function resolve(NotificationEventRule $rule, Model $related, array $context = []): Collection
    {
        if ($rule->recipient_type === 'custom_user' && isset($context['recipient_user']) && $context['recipient_user'] instanceof User) {
            return collect([$context['recipient_user']]);
        }

        if (in_array($rule->recipient_type, ['candidate', 'tenant'], true)) {
            foreach (['candidate', 'tenant', 'user', 'requester'] as $relation) {
                $value = $context[$relation] ?? ($related->{$relation} ?? null);
                if ($value instanceof User) {
                    return collect([$value]);
                }
            }
        }

        $role = match ($rule->recipient_type) {
            'municipal_technician' => 'municipal_technician',
            'jury_member' => 'jury',
            'finance_manager' => 'financial_manager',
            'maintenance_manager' => 'maintenance_manager',
            'admin' => 'administrator',
            default => null,
        };

        return $role
            ? User::query()->whereHas('roles', fn ($query) => $query->where('name', $role))->get()
            : collect();
    }
}
