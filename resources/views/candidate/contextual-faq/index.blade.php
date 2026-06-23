<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">FAQ contextual</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Ajuda rápida</h1>
                <p class="mt-1 text-sm text-ink-500">Perguntas frequentes filtradas pelo contexto do processo.</p>
            </div>
            <a href="{{ route('candidate.support-tickets.create', ['context' => request('context')]) }}" class="mv-button-primary">Pedir apoio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 border-y border-ink-100 py-5 sm:grid-cols-[1fr_auto]">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Pesquisar" class="rounded-md border-ink-300 text-sm">
                <button type="submit" class="mv-button-primary">Pesquisar</button>
            </form>

            <section class="space-y-4">
                @forelse ($faqs as $faq)
                    <article class="mv-surface p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-ink-900">{{ $faq->question }}</h2>
                            <a href="{{ route('candidate.contextual-faq.index', ['viewed' => $faq->id] + request()->except('viewed')) }}" class="text-sm font-semibold text-civic-700">Marcar vista</a>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $faq->answer }}</p>
                    </article>
                @empty
                    <section class="mv-surface p-6 text-center text-sm text-ink-500">Não foram encontradas perguntas para este contexto.</section>
                @endforelse
            </section>

        </div>
    </div>
</x-app-layout>
