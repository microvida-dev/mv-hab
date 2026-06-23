<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Models\TemplateVariable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TemplateRenderingService
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function render(array $content, array $variables, ?CommunicationChannel $channel = null): array
    {
        $placeholders = collect($content)
            ->filter(fn ($value) => is_string($value))
            ->flatMap(fn (string $value) => $this->extract($value))
            ->unique()
            ->values();

        $known = TemplateVariable::query()
            ->whereIn('code', $placeholders)
            ->where('is_active', true)
            ->pluck('code');
        $unknown = $placeholders->diff($known);

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variables' => 'Variáveis de template desconhecidas ou inativas: '.$unknown->implode(', ').'.',
            ]);
        }

        $missing = $placeholders->filter(fn (string $code) => Arr::get($variables, $code) === null);

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variables' => 'Faltam variáveis obrigatórias: '.$missing->implode(', ').'.',
            ]);
        }

        if ($channel === CommunicationChannel::Sms) {
            $sensitive = TemplateVariable::query()
                ->whereIn('code', $placeholders)
                ->where('is_sensitive', true)
                ->pluck('code');

            if ($sensitive->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'variables' => 'Variáveis sensíveis não podem ser usadas por SMS: '.$sensitive->implode(', ').'.',
                ]);
            }
        }

        return collect($content)->map(function ($value) use ($variables) {
            if (! is_string($value)) {
                return $value;
            }

            return preg_replace_callback(
                '/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',
                fn (array $match) => $this->format(Arr::get($variables, $match[1])),
                $value,
            );
        })->all();
    }

    /**
     * @return list<string>
     */
    public function extract(string $content): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $content, $matches);

        return $matches[1];
    }

    private function format(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        return (string) $value;
    }
}
