<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Documento gerado</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $generatedProcedureDocument->title }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $generatedProcedureDocument->document_number }} · {{ $generatedProcedureDocument->status->label() }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($generatedProcedureDocument->file_path)
                    <a class="mv-button-secondary" href="{{ route('backoffice.generated-documents.download', $generatedProcedureDocument) }}">Download</a>
                @endif
                <form method="POST" action="{{ route('backoffice.generated-documents.issue', $generatedProcedureDocument) }}">@csrf <button class="mv-button-primary">Aprovar</button></form>
            </div>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">@if (session('success'))<div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>@endif<section class="mv-surface p-6"><div class="prose max-w-none">{!! $generatedProcedureDocument->content_snapshot !!}</div></section><section class="mv-surface p-6"><h2 class="text-lg font-semibold text-ink-900">Contexto preservado</h2><pre class="mt-4 overflow-auto rounded-md bg-ink-50 p-4 text-xs">{{ json_encode($generatedProcedureDocument->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></section></div></div>
</x-app-layout>
