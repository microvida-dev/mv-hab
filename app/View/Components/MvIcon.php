<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class MvIcon extends Component
{
    public function __construct(
        public readonly string $name,
        public readonly string $size = 'md',
    ) {}

    public function render(): View
    {
        return view('components.mv-icon');
    }

    public function classes(): string
    {
        return match ($this->size) {
            'sm' => 'h-4 w-4',
            'lg' => 'h-7 w-7',
            'xl' => 'h-10 w-10',
            default => 'h-6 w-6',
        };
    }

    public function svg(): string
    {
        $path = resource_path("icons/raw/{$this->name}.svg");

        if (! file_exists($path)) {
            return '';
        }

        return file_get_contents($path) ?: '';
    }
}
