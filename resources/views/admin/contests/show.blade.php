<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Concurso {{ $contest->code }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $contest->title }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $contest)
                    <a href="{{ route('admin.contests.edit', $contest) }}" class="mv-button-secondary">Editar</a>
                @endcan
                @can('publish', $contest)
                    @if ($contest->status !== \App\Enums\ContestStatus::Published)
                        <form method="POST" action="{{ route('admin.contests.publish', $contest) }}">
                            @csrf
                            <button type="submit" class="mv-button-primary">Publicar</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span class="rounded-md bg-ink-50 px-3 py-1.5 font-semibold text-ink-700">{{ $contest->status->label() }}</span>
                        <span class="rounded-md bg-ink-50 px-3 py-1.5 text-ink-600">{{ $contest->program->name }}</span>
                    </div>
                    <p class="mt-5 text-base leading-7 text-ink-600">{{ $contest->summary }}</p>
                    <div class="mt-6 whitespace-pre-line text-sm leading-7 text-ink-600">{{ $contest->description }}</div>

                    <h2 class="mt-8 text-lg font-semibold text-ink-900">Prazos</h2>
                    <div class="mt-3 divide-y divide-ink-100 border-y border-ink-100">
                        @foreach ($contest->deadlines as $deadline)
                            <div class="py-4">
                                <p class="font-semibold text-ink-900">{{ $deadline->label }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $deadline->ends_at->format('d/m/Y H:i') }} · {{ $deadline->type->label() }}</p>
                            </div>
                        @endforeach
                    </div>

                    <h2 class="mt-8 text-lg font-semibold text-ink-900">Júri</h2>
                    <div class="mt-3 divide-y divide-ink-100 border-y border-ink-100">
                        @forelse ($contest->juryMembers as $member)
                            <div class="py-4">
                                <p class="font-semibold text-ink-900">{{ $member->user->name }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $member->role_in_jury }}</p>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-ink-500">Sem membros de júri associados.</p>
                        @endforelse
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Período</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-ink-500">Abertura</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $contest->opens_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Encerramento</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $contest->closes_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Publicado em</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $contest->published_at?->format('d/m/Y H:i') ?? 'Ainda não publicado' }}</dd>
                            </div>
                        </dl>

                        @if ($contest->status === \App\Enums\ContestStatus::Published)
                            <a href="{{ route('public.contests.show', $contest->slug) }}" class="mv-button-secondary mt-5 w-full">Abrir no portal</a>
                        @endif
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
