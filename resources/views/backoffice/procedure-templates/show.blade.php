<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Minuta de procedimento</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $procedureTemplate->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $procedureTemplate->template_number }} · {{ $procedureTemplate->type->label() }} · {{ $procedureTemplate->status->label() }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('backoffice.procedure-templates.edit', $procedureTemplate) }}" class="mv-button-secondary">Editar</a>
                <form method="POST" action="{{ route('backoffice.procedure-templates.publish', $procedureTemplate) }}">@csrf <button class="mv-button-primary">Publicar</button></form>
            </div>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))<div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>@endif
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Conteúdo base</h2>
                <pre class="mt-4 max-h-96 overflow-auto whitespace-pre-wrap rounded-md bg-ink-50 p-4 text-sm text-ink-700">{{ $procedureTemplate->content }}</pre>
            </section>
            <section class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('backoffice.procedure-templates.preview', $procedureTemplate) }}" class="mv-surface space-y-4 p-6">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Pré-visualizar</h2>
                    <input name="application_id" placeholder="ID da candidatura" class="w-full rounded-md border-ink-200">
                    <input name="contest_id" placeholder="ID do concurso" class="w-full rounded-md border-ink-200">
                    <button class="mv-button-secondary">Pré-visualizar</button>
                </form>
                <form method="POST" action="{{ route('backoffice.procedure-templates.documents.generate', $procedureTemplate) }}" class="mv-surface space-y-4 p-6">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Gerar documento</h2>
                    <input name="application_id" placeholder="ID da candidatura" class="w-full rounded-md border-ink-200">
                    <input name="contest_id" placeholder="ID do concurso" class="w-full rounded-md border-ink-200">
                    <button class="mv-button-primary">Gerar documento</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
