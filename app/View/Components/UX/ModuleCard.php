<?php

namespace App\View\Components\UX;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ModuleCard extends Component
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $href = null,
        public readonly ?string $icon = null,
        public readonly ?string $status = null,
        public readonly ?string $metric = null,
        public readonly string $actionLabel = 'Abrir',
        public readonly bool $authorized = true,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ux.module-card');
    }
}
