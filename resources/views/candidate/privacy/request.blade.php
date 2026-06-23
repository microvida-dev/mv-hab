<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $requestRecord->request_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-500">{{ $requestRecord->request_type?->label() }} · {{ $requestRecord->status?->label() }} · prazo {{ $requestRecord->due_at?->format('d/m/Y') }}</p>
            <p class="mt-3 text-ink-700">{{ $requestRecord->description }}</p>
            <form method="POST" action="{{ route('candidate.privacy.requests.export', $requestRecord) }}" class="mt-4">
                @csrf
                <button class="mv-button-secondary">Gerar exportação dos meus dados</button>
            </form>
        </section>
        <section class="mv-surface overflow-hidden">
            <div class="p-5 font-semibold">Histórico</div>
            <div class="divide-y divide-ink-100">
                @foreach ($requestRecord->actions as $action)
                    <p class="p-4 text-sm text-ink-600">{{ $action->performed_at?->format('d/m/Y H:i') }} · {{ $action->description }}</p>
                @endforeach
            </div>
        </section>
        <section class="mv-surface overflow-hidden">
            <div class="p-5 font-semibold">Exportações</div>
            <div class="divide-y divide-ink-100">
                @foreach ($requestRecord->exports as $package)
                    <a class="block p-4 text-sm hover:bg-ink-50" href="{{ route('candidate.privacy.exports.show', $package) }}">{{ $package->package_number }} · {{ $package->filename }}</a>
                @endforeach
            </div>
        </section>
    </div></div>
</x-app-layout>
