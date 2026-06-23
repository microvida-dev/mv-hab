<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $requestRecord->request_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="mv-surface p-5">
            <p class="text-sm text-ink-500">{{ $requestRecord->request_type?->label() }} · {{ $requestRecord->status?->label() }} · prazo {{ $requestRecord->due_at?->format('d/m/Y') }}</p>
            <p class="mt-3 text-ink-700">{{ $requestRecord->description }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('backoffice.security.privacy.requests.assign', $requestRecord) }}" class="flex gap-2">@csrf<select name="assigned_to" class="mv-input">@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><button class="mv-button-secondary">Atribuir</button></form>
                <form method="POST" action="{{ route('backoffice.security.privacy.requests.exports.store', $requestRecord) }}">@csrf<button class="mv-button-secondary">Gerar exportação</button></form>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <form method="POST" action="{{ route('backoffice.security.privacy.requests.complete', $requestRecord) }}" class="grid gap-2">@csrf<textarea name="summary" class="mv-input" rows="2" placeholder="Resumo de conclusão"></textarea><button class="mv-button-primary w-fit">Concluir</button></form>
                <form method="POST" action="{{ route('backoffice.security.privacy.requests.reject', $requestRecord) }}" class="grid gap-2">@csrf<textarea name="reason" class="mv-input" rows="2" placeholder="Fundamento de rejeição"></textarea><button class="mv-button-secondary w-fit">Rejeitar</button></form>
            </div>
        </section>
        <section class="grid gap-6 xl:grid-cols-2">
            <div class="mv-surface overflow-hidden"><div class="p-5 font-semibold">Ações</div><div class="divide-y divide-ink-100">@foreach ($requestRecord->actions as $action)<p class="p-4 text-sm">{{ $action->performed_at?->format('d/m/Y H:i') }} · {{ $action->action_type?->label() }} · {{ $action->description }}</p>@endforeach</div></div>
            <div class="mv-surface overflow-hidden"><div class="p-5 font-semibold">Exportações</div><div class="divide-y divide-ink-100">@foreach ($requestRecord->exports as $package)<a class="block p-4 text-sm hover:bg-ink-50" href="{{ route('backoffice.security.privacy.exports.show', $package) }}">{{ $package->package_number }} · {{ $package->filename }}</a>@endforeach</div></div>
        </section>
    </div></div>
</x-app-layout>
