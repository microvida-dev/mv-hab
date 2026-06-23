<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Receção administrativa</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Candidaturas sem processo</h1>
            </div>
            <form method="POST" action="{{ route('backoffice.application-intake.create-processes-batch') }}">@csrf<button class="mv-button-primary">Criar processos em lote</button></form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-5 py-3">Candidatura</th><th class="px-5 py-3">Candidato</th><th class="px-5 py-3">Concurso</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($applications as $application)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-ink-900">{{ $application->application_number }}</td>
                                <td class="px-5 py-4 text-ink-700">{{ $application->user->name }}</td>
                                <td class="px-5 py-4 text-ink-700">{{ $application->contest->title }}</td>
                                <td class="px-5 py-4 text-right"><form method="POST" action="{{ route('backoffice.application-intake.create-process', $application) }}">@csrf<button class="font-semibold text-civic-700">Criar processo</button></form></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Não existem candidaturas pendentes de receção administrativa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $applications->links() }}
        </div>
    </div>
</x-app-layout>
