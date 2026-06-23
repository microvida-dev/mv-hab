<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Backoffice</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar FAQ contextual</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">@include('backoffice.contextual-faqs.partials.form', ['action' => route('backoffice.contextual-faqs.update', $faq), 'method' => 'PATCH', 'faq' => $faq])</div></div>
</x-app-layout>
