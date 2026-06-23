<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Nova configuração administrativa</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.administrative-workflow-configs.store') }}" class="mv-surface space-y-4 p-6">
            @csrf
            @include('backoffice.administrative-workflow-configs.form', ['config' => null])
        </form>
    </div></div>
</x-app-layout>
