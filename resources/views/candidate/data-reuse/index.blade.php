<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Reutilização de dados</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Dados para futuras candidaturas</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface p-6">
                <p class="text-sm leading-6 text-ink-700">Os dados reutilizados devem ser revistos e confirmados antes de nova candidatura. Dados desatualizados, incompletos ou alterados podem afetar a elegibilidade, a pontuação ou a análise do processo.</p>
                <form method="POST" action="{{ route('candidate.data-reuse.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <x-input-label for="source_reuse_profile_id" value="Origem dos dados" />
                        <select id="source_reuse_profile_id" name="source_reuse_profile_id" required class="mt-1 block w-full rounded-md border-ink-200">
                            @foreach ($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->profile_number }} · {{ $profile->last_confirmed_at?->format('d/m/Y') ?? 'por confirmar' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="target_application_id" value="Candidatura de destino" />
                        <select id="target_application_id" name="target_application_id" class="mt-1 block w-full rounded-md border-ink-200">
                            <option value="">Preparar sem aplicar</option>
                            @foreach ($applications as $application)
                                <option value="{{ $application->id }}">{{ $application->application_number ?? $application->public_id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label value="Secções" />
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach (['dados_pessoais' => 'Dados pessoais', 'agregado' => 'Agregado', 'rendimentos' => 'Rendimentos', 'situacao_habitacional' => 'Situação habitacional', 'preferencias' => 'Preferências'] as $value => $label)
                                <label class="flex gap-2 text-sm text-ink-700"><input type="checkbox" name="sections[]" value="{{ $value }}" class="rounded border-ink-300"> {{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-2"><button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Preparar reutilização</button></div>
                </form>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Reutilizações</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($reuses as $reuse)
                        <div class="rounded-md border border-ink-100 p-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $reuse->status->label() }}</p>
                            <p class="mt-1 text-ink-500">Secções: {{ implode(', ', $reuse->sections ?? []) }}</p>
                            @if ($reuse->status->value !== 'applied')
                                <form method="POST" action="{{ route('candidate.data-reuse.confirm', $reuse) }}" class="mt-3 flex flex-wrap items-center gap-3">
                                    @csrf
                                    <select name="target_application_id" required class="rounded-md border-ink-200 text-sm">
                                        @foreach ($applications as $application)
                                            <option value="{{ $application->id }}">{{ $application->application_number ?? $application->public_id }}</option>
                                        @endforeach
                                    </select>
                                    @foreach (($reuse->sections ?? []) as $section)
                                        <input type="hidden" name="sections[]" value="{{ $section }}">
                                    @endforeach
                                    <label class="text-sm text-ink-700"><input type="checkbox" name="confirm_review_required" value="1" class="rounded border-ink-300"> Revisto</label>
                                    <button class="rounded-md border border-civic-200 px-3 py-2 text-sm font-semibold text-civic-700">Confirmar</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Ainda não existem reutilizações preparadas.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
