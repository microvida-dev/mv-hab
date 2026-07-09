@php
    use App\Enums\ProcedureMinuteStatus;

    $isApproved = $procedureMinute->status === ProcedureMinuteStatus::Approved;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Ata"
            :title="$procedureMinute->title"
            :description="$procedureMinute->minute_number.' · '.$procedureMinute->status->label()"
        >
            <x-slot name="actions">
                @if ($procedureMinute->file_path)
                    <a href="{{ route('backoffice.procedure-minutes.download', $procedureMinute) }}" class="mv-button-secondary">
                        Download
                    </a>
                @endif

                @if (! $isApproved)
                    @can('approve', $procedureMinute)
                        <form method="POST" action="{{ route('backoffice.procedure-minutes.approve', $procedureMinute) }}">
                            @csrf
                            <button class="mv-button-primary" type="submit">Aprovar ata</button>
                        </form>
                    @endcan
                @endif
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.alert tone="warning">
                Esta ata contém um snapshot processual interno. O payload de suporte deve ser tratado como documento interno e consultado apenas por utilizadores autorizados.
            </x-mv.alert>

            <x-mv.section title="Metadados">
                <dl class="grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-ink-500">Número</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $procedureMinute->minute_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estado</dt>
                        <dd class="mt-1">
                            <x-mv.badge :tone="$isApproved ? 'success' : 'neutral'">
                                {{ $procedureMinute->status->label() }}
                            </x-mv.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Concurso</dt>
                        <dd class="mt-1 font-medium text-ink-900">
                            {{ $procedureMinute->contest?->code ?? '—' }}
                            @if ($procedureMinute->contest?->title)
                                · {{ $procedureMinute->contest->title }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Candidatura</dt>
                        <dd class="mt-1 font-medium text-ink-900">
                            {{ $procedureMinute->application?->application_number ?? '—' }}
                            @if ($procedureMinute->application?->user?->name)
                                · {{ $procedureMinute->application->user->name }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Gerada em</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $procedureMinute->generated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Aprovada em</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $procedureMinute->approved_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-mv.section>

            <x-mv.section title="Conteúdo da ata">
                <div class="prose max-w-none">
                    {!! $procedureMinute->content_snapshot !!}
                </div>
            </x-mv.section>

            <x-mv.section>
                <details>
                    <summary class="cursor-pointer text-sm font-semibold text-ink-900">
                        Payload de suporte
                    </summary>
                    <pre class="mt-4 overflow-auto rounded-2xl bg-ink-50 p-4 text-xs text-ink-700">{{ json_encode($procedureMinute->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
