<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Pré-visualização</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $notificationTemplate->name }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Pré-visualização com dados fictícios. Modelos oficiais exigem validação municipal e jurídica.</div>
        <article class="rounded-md border border-ink-100 bg-white p-8">
            @if ($rendered['subject'])<p class="text-sm text-ink-500">{{ $rendered['subject'] }}</p>@endif
            <h2 class="mt-2 text-xl font-semibold text-ink-900">{{ $rendered['title'] }}</h2>
            <div class="mt-6 whitespace-pre-line text-sm leading-7 text-ink-700">{{ $rendered['body'] }}</div>
        </article>
    </div></div>
</x-app-layout>
