@php
    $statusClasses = match ($registration->status) {
        \App\Enums\AdhesionRegistrationStatus::Registered => 'bg-civic-50 text-civic-900',
        \App\Enums\AdhesionRegistrationStatus::Blocked => 'bg-red-50 text-red-800',
        \App\Enums\AdhesionRegistrationStatus::Incomplete => 'bg-signal-50 text-signal-700',
        default => 'bg-ink-100 text-ink-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Área do Candidato</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Registo de Adesão</h1>
                <p class="mt-1 text-sm text-ink-500">Atualizado em {{ $registration->updated_at->format('d/m/Y H:i') }}</p>
            </div>
            <span class="w-fit rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ $registration->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-6">
                    <div class="mv-surface p-6">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <h2 class="font-semibold text-ink-900">Progresso do preenchimento</h2>
                            <span class="font-semibold text-civic-700">{{ $registration->completionPercentage() }}%</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded bg-ink-100">
                            <div class="h-full bg-civic-700" style="width: {{ $registration->completionPercentage() }}%"></div>
                        </div>

                        @if ($registration->missingRequiredFields())
                            <div class="mt-5 rounded-md border border-signal-500 bg-signal-50 p-4">
                                <p class="text-sm font-semibold text-signal-700">Campos necessários para finalizar</p>
                                <ul class="mt-2 grid gap-1 text-sm text-signal-700 sm:grid-cols-2">
                                    @foreach ($registration->missingRequiredFields() as $field)
                                        <li>• {{ $field }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif (! $registration->isAdult())
                            <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">É necessário ter pelo menos 18 anos para finalizar o registo.</div>
                        @endif
                    </div>

                    <div class="mv-surface p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-ink-900">Dados registados</h2>
                            @can('update', $registration)
                                <a href="{{ route('candidate.registration.edit') }}" class="mv-button-secondary">Editar dados</a>
                            @endcan
                        </div>

                        <dl class="mt-6 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                            @foreach ([
                                'Nome completo' => $registration->full_name,
                                'Data de nascimento' => $registration->birth_date?->format('d/m/Y'),
                                'Nacionalidade' => $registration->nationality,
                                'Email' => $registration->email,
                                'Telefone' => $registration->phone,
                                'Telemóvel' => $registration->mobile_phone,
                                'Tipo de documento' => $registration->document_type,
                                'Número do documento' => $registration->document_number,
                                'Validade do documento' => $registration->document_valid_until?->format('d/m/Y'),
                                'NIF' => $registration->nif,
                                'Morada' => $registration->address,
                                'Código postal' => $registration->postal_code,
                                'Localidade' => $registration->city,
                                'Freguesia' => $registration->parish,
                                'Município' => $registration->municipality,
                            ] as $label => $value)
                                <div>
                                    <dt class="text-ink-500">{{ $label }}</dt>
                                    <dd class="mt-1 font-semibold text-ink-900">{{ filled($value) ? $value : 'Não indicado' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="mv-surface p-6">
                        <h2 class="text-base font-semibold text-ink-900">Histórico de estado</h2>
                        <div class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
                            @foreach ($registration->statusHistories as $history)
                                <div class="grid gap-2 py-4 text-sm sm:grid-cols-[10rem_minmax(0,1fr)_10rem]">
                                    <span class="text-ink-500">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="font-semibold text-ink-900">
                                        {{ $history->from_status?->label() ?? 'Criação' }}
                                        →
                                        {{ $history->to_status->label() }}
                                    </span>
                                    <span class="text-ink-500 sm:text-right">{{ $history->changedBy?->name ?? 'Sistema' }}</span>
                                    @if ($history->reason)
                                        <p class="text-ink-500 sm:col-span-3">{{ $history->reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <aside class="space-y-4">
                    @if ($registration->status === \App\Enums\AdhesionRegistrationStatus::Incomplete)
                        <section class="mv-surface p-5">
                            <h2 class="font-semibold text-ink-900">Finalizar registo</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-500">Confirme os dados antes de finalizar. O registo só será aceite se estiver completo e cumprir a idade mínima.</p>
                            <form method="POST" action="{{ route('candidate.registration.finalize') }}" class="mt-4">
                                @csrf
                                <button class="mv-button-primary w-full" @disabled(! $registration->canBeFinalized())>Finalizar Registo</button>
                            </form>
                        </section>
                    @elseif ($registration->status === \App\Enums\AdhesionRegistrationStatus::Registered)
                        <section class="mv-surface p-5">
                            <h2 class="font-semibold text-ink-900">Registo finalizado</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-500">Finalizado em {{ $registration->submitted_at?->format('d/m/Y H:i') }}. Poderá utilizar estes dados em futuras candidaturas.</p>
                        </section>
                    @endif

                    @can('cancel', $registration)
                        <section class="mv-surface p-5">
                            <h2 class="font-semibold text-ink-900">Cancelar registo</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-500">O registo deixará de estar ativo, mantendo o respetivo histórico.</p>
                            <form method="POST" action="{{ route('candidate.registration.cancel') }}" class="mt-4">
                                @csrf
                                <label for="cancel_reason" class="text-xs font-semibold text-ink-600">Motivo opcional</label>
                                <textarea id="cancel_reason" name="reason" rows="2" class="mt-1 block w-full rounded-md border-ink-100 text-sm focus:border-civic-500 focus:ring-civic-500"></textarea>
                                <button class="mv-button-secondary mt-3 w-full">Cancelar registo</button>
                            </form>
                        </section>
                    @endcan

                    @can('delete', $registration)
                        <section class="rounded-lg border border-red-200 bg-red-50 p-5">
                            <h2 class="font-semibold text-red-900">Remover registo</h2>
                            <p class="mt-2 text-sm leading-6 text-red-800">Esta ação retira o registo da área ativa e preserva apenas o histórico necessário.</p>
                            <form method="POST" action="{{ route('candidate.registration.remove') }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <label class="flex items-start gap-3 text-sm text-red-900">
                                    <input type="checkbox" name="confirm_removal" value="1" class="mt-0.5 rounded border-red-300 text-red-700 focus:ring-red-500">
                                    Confirmo que pretendo remover o Registo de Adesão.
                                </label>
                                <button class="mv-button-danger mt-3 w-full">Remover registo</button>
                            </form>
                        </section>
                    @endcan
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
