<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Revisão de storage documental</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6"><dl class="grid gap-3">@foreach ($review as $key => $value)<div class="border-b border-ink-100 pb-3"><dt class="font-semibold text-ink-700">{{ str($key)->replace('_', ' ')->title() }}</dt><dd class="mt-1 text-sm text-ink-600">{{ is_array($value) ? implode(' | ', $value) : $value }}</dd></div>@endforeach</dl></section>
    </div></div>
</x-app-layout>
