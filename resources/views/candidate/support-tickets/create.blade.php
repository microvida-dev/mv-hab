<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Apoio</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo pedido de apoio</h1>
            <p class="mt-1 text-sm text-ink-500">Os pedidos ficam registados com histórico e visibilidade controlada.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.support-tickets.store') }}" class="mv-surface space-y-5 p-6">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="category" value="Categoria" />
                        <select id="category" name="category" class="mt-1 w-full rounded-md border-ink-300 text-sm" required>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', request('category')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="priority" value="Prioridade" />
                        <select id="priority" name="priority" class="mt-1 w-full rounded-md border-ink-300 text-sm">
                            @foreach ($priorities as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="application_id" value="Candidatura" />
                    <select id="application_id" name="application_id" class="mt-1 w-full rounded-md border-ink-300 text-sm">
                        <option value="">Sem associação</option>
                        @foreach ($applications as $application)
                            <option value="{{ $application->id }}" @selected(old('application_id') == $application->id)>{{ $application->application_number ?? 'Rascunho' }} · {{ $application->contest->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="subject" value="Assunto" />
                    <input id="subject" name="subject" value="{{ old('subject') }}" class="mt-1 w-full rounded-md border-ink-300 text-sm" required>
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Mensagem" />
                    <textarea id="description" name="description" rows="6" class="mt-1 w-full rounded-md border-ink-300 text-sm" required>{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <input type="hidden" name="context" value="{{ request('context') }}">
                <div class="flex justify-end gap-3">
                    <a href="{{ route('candidate.support-tickets.index') }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">Criar pedido</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
