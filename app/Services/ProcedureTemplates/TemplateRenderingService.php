<?php

namespace App\Services\ProcedureTemplates;

use App\Models\ProcedureTemplate;
use Illuminate\Validation\ValidationException;

class TemplateRenderingService
{
    /**
     * @param  array<string, string>  $variables
     */
    public function render(ProcedureTemplate $template, array $variables): string
    {
        $unknown = $this->unknownPlaceholders($template->content, $variables);

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'content' => 'Variáveis desconhecidas: '.implode(', ', $unknown),
            ]);
        }

        return preg_replace_callback(
            '/{{\s*([A-Za-z0-9_\.]+)\s*}}/',
            static fn (array $matches): string => e($variables[$matches[1]] ?? ''),
            $template->content,
        ) ?? $template->content;
    }

    /**
     * @return list<string>
     */
    public function placeholders(string $content): array
    {
        preg_match_all('/{{\s*([A-Za-z0-9_\.]+)\s*}}/', $content, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  array<string, string>  $variables
     * @return list<string>
     */
    private function unknownPlaceholders(string $content, array $variables): array
    {
        return array_values(array_diff($this->placeholders($content), array_keys($variables)));
    }
}
