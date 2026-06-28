<?php

namespace App\View\Components\UX;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LegacyPageShell extends Component
{
    public function __construct(public readonly string $maxWidth = 'mv-page-shell') {}

    public function render(): View|Closure|string
    {
        return view('components.ux.page-shell');
    }
}
