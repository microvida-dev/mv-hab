<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Backoffice</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">FAQ contextual</h1>
            </div>
            <a href="{{ route('backoffice.contextual-faqs.create') }}" class="mv-button-primary">Nova FAQ</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Contexto</th><th class="px-5 py-3">Pergunta</th><th class="px-5 py-3">Concurso</th><th class="px-5 py-3">Ativa</th><th class="px-5 py-3 text-right">Ações</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($faqs as $faq)
                                <tr>
                                    <td class="px-5 py-4 text-ink-700">{{ $faq->context_key }}</td>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $faq->question }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $faq->contest?->title ?? 'Geral' }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $faq->is_active ? 'Sim' : 'Não' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.contextual-faqs.edit', $faq) }}" class="font-semibold text-civic-700">Editar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">Sem FAQs contextuais.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $faqs->links() }}
        </div>
    </div>
</x-app-layout>
