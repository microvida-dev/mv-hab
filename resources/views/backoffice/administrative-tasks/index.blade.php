<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Tarefas administrativas</h1></x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($tasks as $task)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-ink-900">{{ $task->title }}</td>
                                <td class="px-5 py-4 text-ink-700">{{ $task->administrativeProcess->process_number }}</td>
                                <td class="px-5 py-4 text-ink-600">{{ $task->status->label() }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-5 py-4 text-right"><form method="POST" action="{{ route('backoffice.administrative-tasks.complete', $task) }}">@csrf<button class="font-semibold text-civic-700">Concluir</button></form></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
            {{ $tasks->links() }}
        </div>
    </div>
</x-app-layout>
