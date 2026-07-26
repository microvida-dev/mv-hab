<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Administração da plataforma"
            title="Operadores de plataforma"
            description="Associações globais explícitas, auditáveis e revogáveis."
        >
            <x-slot name="actions">
                @can('auditAny', App\Models\PlatformOperatorAssignment::class)
                    <a href="{{ route('backoffice.platform.operators.audit') }}" class="mv-button-secondary">
                        Consultar auditoria
                    </a>
                @endcan
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.alert>
                A associação concede apenas scope global. Cada operação continua dependente de uma permissão atribuída através de um perfil ativo e de MFA verificado.
            </x-mv.alert>

            @can('create', App\Models\PlatformOperatorAssignment::class)
                <x-mv.section
                    title="Conceder scope global"
                    description="Selecione uma conta dedicada, ativa, sem Município e com MFA confirmado."
                >
                    <form method="POST" action="{{ route('backoffice.platform.operators.store') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] lg:items-end">
                        @csrf

                        <div>
                            <x-input-label for="user_id" value="Utilizador" />
                            <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                                <option value="">Selecione uma conta elegível</option>
                                @foreach ($availableUsers as $availableUser)
                                    <option value="{{ $availableUser->id }}" @selected((string) old('user_id') === (string) $availableUser->id)>
                                        #{{ $availableUser->id }} · {{ $availableUser->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="justification" value="Justificação" />
                            <x-text-input
                                id="justification"
                                name="justification"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('justification')"
                                required
                                minlength="10"
                                maxlength="1000"
                            />
                            <x-input-error :messages="$errors->get('justification')" class="mt-2" />
                            <x-input-error :messages="$errors->get('platform_operator')" class="mt-2" />
                        </div>

                        <x-primary-button type="submit">Conceder scope</x-primary-button>
                    </form>
                </x-mv.section>
            @endcan

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <thead>
                            <tr>
                                <th>Utilizador</th>
                                <th>Estado</th>
                                <th>Origem</th>
                                <th>Concedido em</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">{{ $assignment->user?->name ?? 'Conta removida' }}</p>
                                        <p class="text-xs text-ink-500">ID {{ $assignment->user_id }}</p>
                                    </td>
                                    <td>
                                        <x-mv.badge :tone="$assignment->isActive() ? 'success' : 'neutral'">
                                            {{ $assignment->isActive() ? 'Ativo' : 'Revogado' }}
                                        </x-mv.badge>
                                    </td>
                                    <td>{{ $assignment->grant_source->value === 'bootstrap' ? 'Bootstrap aprovado' : 'Operador de plataforma' }}</td>
                                    <td>{{ $assignment->granted_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('backoffice.platform.operators.show', $assignment) }}" class="mv-button-secondary">
                                            Consultar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-ink-500">
                                        Ainda não existem operadores de plataforma associados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $assignments->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
