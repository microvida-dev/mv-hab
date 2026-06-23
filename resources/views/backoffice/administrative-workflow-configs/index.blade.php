<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Configuração</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Workflow administrativo</h1>
            </div>
            <a href="{{ route('backoffice.administrative-workflow-configs.create') }}" class="mv-button-primary">Nova configuração</a>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <tbody class="divide-y divide-ink-100">
                    @foreach ($configs as $config)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-ink-900">{{ $config->name }}</td>
                            <td class="px-5 py-4 text-ink-700">{{ $config->contest?->title ?? $config->program?->name }}</td>
                            <td class="px-5 py-4 text-ink-600">{{ $config->default_correction_deadline_days }} dias</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.administrative-workflow-configs.edit', $config) }}" class="font-semibold text-civic-700">Editar</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        {{ $configs->links() }}
    </div></div>
</x-app-layout>
