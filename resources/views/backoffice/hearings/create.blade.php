<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiências"
            title="Criar audiência"
            description="Registe uma audiência e o respetivo prazo de pronúncia para o candidato."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.hearings.store') }}">
                @csrf
                <x-mv.section title="Dados da audiência">
                    <div>
                        <x-input-label for="application_id" value="Candidatura" />
                        <select id="application_id" name="application_id" class="mv-input mt-1 w-full text-sm">
                            @foreach ($applications as $application)
                                <option value="{{ $application->id }}">{{ $application->application_number ?? $application->public_id }} · {{ $application->user?->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="hearing_type" value="Tipo" />
                        <select id="hearing_type" name="hearing_type" class="mv-input mt-1 w-full text-sm">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="subject" value="Assunto" />
                        <x-text-input id="subject" name="subject" class="mt-1 w-full" required />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="message" value="Mensagem" />
                        <textarea id="message" name="message" class="mv-input mt-1 w-full text-sm" required></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="grounds" value="Fundamentos" />
                        <textarea id="grounds" name="grounds" class="mv-input mt-1 w-full text-sm" required></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="deadline_at" value="Prazo" />
                        <x-text-input type="datetime-local" id="deadline_at" name="deadline_at" class="mt-1 w-full" required />
                    </div>

                    <div class="mt-5">
                        <input type="hidden" name="candidate_visible" value="0">
                        <x-mv.checkbox-card name="candidate_visible" label="Visível após emissão" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="mv-button-primary">Criar</button>
                    </div>
                </x-mv.section>
            </form>
        </div>
    </div>
</x-app-layout>
