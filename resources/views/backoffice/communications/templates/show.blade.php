<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="font-mono text-xs text-mvhab-primary">{{ $notificationTemplate->code }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $notificationTemplate->name }}</h1></div>
            <div class="flex flex-wrap gap-2"><a href="{{ route('backoffice.communications.templates.preview', $notificationTemplate) }}" class="mv-button-secondary">Pré-visualizar</a>@can('update', $notificationTemplate)<a href="{{ route('backoffice.communications.templates.edit', $notificationTemplate) }}" class="mv-button-primary">Editar</a><form method="POST" action="{{ route('backoffice.communications.templates.archive', $notificationTemplate) }}">@csrf<x-danger-button>Arquivar</x-danger-button></form>@endcan</div>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
        <section class="grid gap-6 border-b border-ink-100 pb-8 md:grid-cols-3">
            <div><p class="text-xs font-semibold uppercase text-ink-500">Canal</p><p class="mt-2">{{ $notificationTemplate->channel->label() }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-ink-500">Estado</p><p class="mt-2">{{ $notificationTemplate->status->label() }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-ink-500">Versão ativa</p><p class="mt-2">{{ $notificationTemplate->activeVersion?->version_number ? 'v'.$notificationTemplate->activeVersion->version_number : 'Não definida' }}</p></div>
        </section>
        <section><h2 class="text-lg font-semibold text-ink-900">Conteúdo atual</h2><p class="mt-4 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $notificationTemplate->activeVersion?->body ?? $notificationTemplate->body }}</p></section>
        <section>
            <h2 class="text-lg font-semibold text-ink-900">Versões</h2>
            <div class="mt-4 overflow-hidden rounded-2xl mv-surface">
                @forelse ($notificationTemplate->versions as $version)
                    <a href="{{ route('backoffice.communications.template-versions.show', $version) }}" class="flex items-center justify-between border-b border-ink-100 px-4 py-3 text-sm"><span class="font-semibold">Versão {{ $version->version_number }}</span><span>{{ $version->status->label() }}</span></a>
                @empty
                    <p class="p-5 text-sm text-ink-500">Sem versões.</p>
                @endforelse
            </div>
        </section>
        @can('update', $notificationTemplate)
            <section class="rounded-2xl mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Criar nova versão</h2>
                <form method="POST" action="{{ route('backoffice.communications.template-versions.store', $notificationTemplate) }}" class="mt-5 space-y-4">@csrf
                    <x-text-input name="subject" class="w-full" :value="$notificationTemplate->subject" placeholder="Assunto" />
                    <x-text-input name="title" class="w-full" :value="$notificationTemplate->title" placeholder="Título" />
                    <textarea name="body" rows="8" class="w-full mv-input" required>{{ $notificationTemplate->body }}</textarea>
                    <textarea name="change_summary" rows="2" class="w-full mv-input" placeholder="Resumo da alteração"></textarea>
                    <x-primary-button>Criar versão</x-primary-button>
                </form>
            </section>
        @endcan
    </div></div>
</x-app-layout>
