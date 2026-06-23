<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Pré-preenchimento</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $applicationPrefill->status->label() }}</h1>
            <p class="mt-1 text-sm text-ink-500">Confirme os dados antes de os aplicar ao rascunho da candidatura.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <section class="mv-surface p-6">
                <x-flash-message />
                <h2 class="text-lg font-semibold text-ink-900">Dados incluídos</h2>
                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach (($applicationPrefill->fields_included ?? []) as $field)
                        <div class="rounded-md border border-ink-100 p-4">
                            <dt class="text-xs font-semibold uppercase text-ink-500">Incluído</dt>
                            <dd class="mt-1 font-semibold text-ink-900">{{ ucfirst((string) $field) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (($applicationPrefill->warnings ?? []) !== [])
                    <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        @foreach ($applicationPrefill->warnings as $warning)
                            <p>{{ $warning }}</p>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="mv-surface h-fit p-6">
                <h2 class="text-lg font-semibold text-ink-900">Ações</h2>
                @if ($applicationPrefill->application)
                    <a href="{{ route('candidate.applications.show', $applicationPrefill->application) }}" class="mv-button-secondary mt-4 w-full justify-center">Ver rascunho</a>
                @endif
                <form method="POST" action="{{ route('candidate.application-prefills.confirm', $applicationPrefill) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="confirm_data_reviewed" value="1">
                    <button class="mv-button-secondary w-full justify-center">Confirmar dados</button>
                </form>
                <form method="POST" action="{{ route('candidate.application-prefills.apply', $applicationPrefill) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="confirm_apply_to_draft" value="1">
                    <button class="mv-button-primary w-full justify-center">Aplicar ao rascunho</button>
                </form>
                <form method="POST" action="{{ route('candidate.application-prefills.cancel', $applicationPrefill) }}" class="mt-3">
                    @csrf
                    <button class="mv-button-secondary w-full justify-center">Cancelar</button>
                </form>
            </aside>
        </div>
    </div>
</x-app-layout>
