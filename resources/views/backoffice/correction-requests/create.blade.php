<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Pedido de aperfeiçoamento</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.correction-requests.store', $process) }}" class="mv-surface space-y-5 p-6">
                @csrf
                <input name="subject" class="w-full rounded-md border-ink-300 text-sm" placeholder="Assunto">
                <textarea name="message" rows="4" class="w-full rounded-md border-ink-300 text-sm" placeholder="Mensagem ao candidato"></textarea>
                <textarea name="instructions" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Instruções"></textarea>
                <input type="datetime-local" name="response_deadline_at" class="w-full rounded-md border-ink-300 text-sm">
                <div class="rounded-md border border-ink-100 p-4">
                    <h2 class="text-sm font-semibold text-ink-900">Item obrigatório</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <input name="items[0][title]" class="rounded-md border-ink-300 text-sm" placeholder="Título do item">
                        <select name="items[0][issue_type]" class="rounded-md border-ink-300 text-sm">@foreach ($issueTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                        <select name="items[0][required_action]" class="rounded-md border-ink-300 text-sm">@foreach ($actions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                        <select name="items[0][document_type_id]" class="rounded-md border-ink-300 text-sm"><option value="">Sem tipo documental</option>@foreach ($documentTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
                    </div>
                    <textarea name="items[0][description]" rows="3" class="mt-3 w-full rounded-md border-ink-300 text-sm" placeholder="Descrição"></textarea>
                    <input type="hidden" name="items[0][is_required]" value="1">
                </div>
                <button class="mv-button-primary">Guardar rascunho</button>
            </form>
        </div>
    </div>
</x-app-layout>
