<?php

namespace App\Services\PublicPortal;

use App\Models\PublicPortalSetting;
use Illuminate\Support\Collection;

class PublicPortalSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'show_map' => true,
            'map_center_lat' => 39.4595,
            'map_center_lng' => -8.6674,
            'map_zoom' => 12,
            'portal_title' => 'Oferta Habitacional',
            'portal_description' => 'Consulte concursos, habitações municipais e informação pública de apoio à candidatura.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = PublicPortalSetting::query()
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(fn (PublicPortalSetting $setting) => [$setting->key => $this->valueFromSetting($setting)]);

        return array_merge($this->defaults(), $settings->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = PublicPortalSetting::query()->where('key', $key)->first();

        return $setting instanceof PublicPortalSetting ? $this->valueFromSetting($setting) : ($default ?? $this->defaults()[$key] ?? null);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            PublicPortalSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
                    'value' => ['value' => $value],
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                    'is_public' => true,
                ],
            );
        }
    }

    /**
     * @return Collection<int, PublicPortalSetting>
     */
    public function editableSettings(): Collection
    {
        $this->ensureDefaultsExist();

        return PublicPortalSetting::query()->orderBy('key')->get();
    }

    private function ensureDefaultsExist(): void
    {
        foreach ($this->defaults() as $key => $value) {
            PublicPortalSetting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
                    'value' => ['value' => $value],
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                    'is_public' => true,
                ],
            );
        }
    }

    private function valueFromSetting(PublicPortalSetting $setting): mixed
    {
        $value = $setting->getAttribute('value');

        return data_get($value, 'value', $value);
    }
}
