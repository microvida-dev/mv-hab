<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamação"
            :title="$complaint->complaint_number"
            description="Análise municipal da reclamação e respetivas decisões administrativas."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Resumo da reclamação">
                <dl class="grid gap-4 text-sm md:grid-cols-3">
                    <div>
                        <dt class="text-ink-500">Candidato</dt>
                        <dd>{{ $complaint->candidate?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estado</dt>
                        <dd><x-mv.badge>{{ $complaint->status->label() }}</x-mv.badge></dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Lista</dt>
                        <dd>{{ $complaint->provisionalList?->list_number }}</dd>
                    </div>
                </dl>

                <h2 class="mt-6 font-semibold">{{ $complaint->subject }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $complaint->grounds }}</p>
            </x-mv.section>

            <x-mv.section title="Ações processuais">
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('backoffice.complaints.mark-received', $complaint) }}">
                        @csrf
                        <button type="submit" class="mv-button-secondary">Marcar recebida</button>
                    </form>
                    <form method="POST" action="{{ route('backoffice.complaints.start-review', $complaint) }}">
                        @csrf
                        <button type="submit" class="mv-button-secondary">Iniciar análise</button>
                    </form>
                    <a href="{{ route('backoffice.complaint-decisions.create', $complaint) }}" class="mv-button-primary">Criar decisão</a>
                    <a href="{{ route('backoffice.additional-information-requests.create', $complaint) }}" class="mv-button-secondary">Pedir informação</a>
                </div>
            </x-mv.section>

            <form method="POST" action="{{ route('backoffice.complaints.assign', $complaint) }}">
                @csrf
                <x-mv.section title="Responsável técnico">
                    <x-input-label for="assigned_to" value="Técnico responsável" />
                    <div class="mt-2 flex gap-2">
                        <select id="assigned_to" name="assigned_to" class="mv-input w-full text-sm">
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="mv-button-secondary">Atribuir</button>
                    </div>
                </x-mv.section>
            </form>

            @if ($complaint->decision)
                <x-mv.section title="Decisão">
                    <p class="text-sm">{{ $complaint->decision->summary }}</p>
                    <a class="mt-3 inline-block font-semibold text-civic-700" href="{{ route('backoffice.complaint-decisions.show', $complaint->decision) }}">Abrir decisão</a>
                </x-mv.section>
            @endif
        </div>
    </div>
</x-app-layout>
