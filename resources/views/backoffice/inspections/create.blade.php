<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistorias"
            title="Criar vistoria"
            description="Registe uma nova vistoria para um fogo municipal."
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.inspections.store') }}" class="space-y-6">
        @csrf

        <x-mv.section title="Dados da vistoria">
            <div class="grid gap-4">
                <select class="mv-input" name="housing_unit_id" required>
                    @foreach ($housingUnits as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->code }} · {{ $unit->address }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="inspection_checklist_template_id">
                    <option value="">Sem template</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="inspection_type">
                    <option value="initial">Inicial</option>
                    <option value="periodic">Periódica</option>
                    <option value="final">Final</option>
                    <option value="extraordinary">Extraordinária</option>
                </select>

                <input class="mv-input" type="datetime-local" name="scheduled_for">
                <textarea class="mv-input" name="summary" placeholder="Resumo inicial"></textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
