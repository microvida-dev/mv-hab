@props(['contests'])

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-civic-700">Concursos</p>
            <h2 class="mt-2 text-3xl font-bold text-ink-900">Oportunidades publicadas</h2>
            <p class="mt-3 max-w-2xl text-base leading-7 text-ink-600">
                Consulte concursos municipais publicados, prazos oficiais e habitações associadas.
            </p>
        </div>

        <a href="{{ route('public.contests.index') }}" class="hidden text-sm font-semibold text-civic-700 hover:text-civic-900 sm:block">
            Ver todos
        </a>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($contests as $contest)
            <x-public-contest-card :contest="$contest" />
        @empty
            <div class="mv-surface col-span-full p-10 text-center">
                <p class="font-semibold text-ink-900">Não existem concursos publicados neste momento.</p>
                <p class="mt-2 text-sm text-ink-500">Consulte novamente esta página para acompanhar novas oportunidades.</p>
            </div>
        @endforelse
    </div>
</section>
