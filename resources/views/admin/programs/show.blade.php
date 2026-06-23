<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Programa</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $program->name }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $program)
                    <a href="{{ route('admin.programs.edit', $program) }}" class="mv-button-secondary">Editar</a>
                @endcan
                @can('publish', $program)
                    @if ($program->status !== \App\Enums\ProgramStatus::Published)
                        <form method="POST" action="{{ route('admin.programs.publish', $program) }}">
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

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span class="rounded-md bg-ink-50 px-3 py-1.5 font-semibold text-ink-700">{{ $program->status->label() }}</span>
                        <span class="rounded-md bg-ink-50 px-3 py-1.5 text-ink-600">{{ $program->municipality->name }}</span>
                    </div>
                    <p class="mt-5 text-base leading-7 text-ink-600">{{ $program->summary }}</p>
                    <div class="mt-6 whitespace-pre-line text-sm leading-7 text-ink-600">{{ $program->description }}</div>

                    <h2 class="mt-8 text-lg font-semibold text-ink-900">Regras</h2>
                    <div class="mt-3 divide-y divide-ink-100 border-y border-ink-100">
                        @foreach ($program->rules as $rule)
                            <div class="py-4">
                                <p class="font-semibold text-ink-900">{{ $rule->title }}</p>
                                <p class="mt-1 text-sm leading-6 text-ink-500">{{ $rule->description }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Publicação</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-ink-500">Slug</dt>
                                <dd class="mt-1 break-all font-semibold text-ink-900">{{ $program->slug }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Publicado em</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $program->published_at?->format('d/m/Y H:i') ?? 'Ainda não publicado' }}</dd>
                            </div>
                        </dl>
                        @if ($program->status === \App\Enums\ProgramStatus::Published)
                            <a href="{{ route('public.programs.show', $program->slug) }}" class="mv-button-secondary mt-5 w-full">Abrir no portal</a>
                        @endif
                    </section>

                    <section class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Concursos associados</h2>
                        <p class="mt-2 text-3xl font-semibold text-ink-900">{{ $program->contests->count() }}</p>
                        @can('create', \App\Models\Contest::class)
                            <a href="{{ route('admin.contests.create', ['program_id' => $program->id]) }}" class="mv-button-secondary mt-4 w-full">Novo concurso</a>
                        @endcan
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
