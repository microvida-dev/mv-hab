@props([
    'title',
    'description' => null,
    'logoWidth' => 'w-56',
])

<div class="text-center">
    <x-application-logo class="mx-auto {{ $logoWidth }}" />

    <h2 class="mt-8 text-2xl font-bold tracking-tight text-slate-950">
        {{ $title }}
    </h2>

    @if($description)
        <p class="mt-3 text-sm leading-6 text-slate-500">
            {{ $description }}
        </p>
    @endif
</div>
