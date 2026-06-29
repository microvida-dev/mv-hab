<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">
            Criar pedido de manutenção
        </h1>
    </x-slot>

    <form
        method="POST"
        action="{{ route('candidate.maintenance.requests.store') }}"
        enctype="multipart/form-data"
        class="mv-card grid gap-5"
    >
        @csrf

        <p class="text-sm leading-6 text-ink-600">
            Descreva o problema com o máximo de detalhe possível. Pode anexar fotografias para ajudar os serviços municipais a avaliar a situação.
        </p>

        <x-ui.field label="Categoria" for="maintenance_category_id" name="maintenance_category_id">
            <x-ui.select id="maintenance_category_id" name="maintenance_category_id">
                <option value="">Categoria</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Urgência" for="urgency" name="urgency">
            <x-ui.select id="urgency" name="urgency">
                @foreach ($urgencies as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Título" for="title" name="title" required>
            <x-ui.input id="title" name="title" required />
        </x-ui.field>

        <x-ui.field label="Descrição" for="description" name="description" required>
            <x-ui.textarea id="description" name="description" rows="5" required />
        </x-ui.field>

        <x-ui.field label="Localização no imóvel" for="location_in_property" name="location_in_property">
            <x-ui.input id="location_in_property" name="location_in_property" />
        </x-ui.field>

        <x-ui.field label="Disponibilidade para contacto/intervenção" for="tenant_availability" name="tenant_availability">
            <x-ui.textarea id="tenant_availability" name="tenant_availability" rows="3" />
        </x-ui.field>

        <x-ui.field label="Anexos" for="attachments" name="attachments">
            <input id="attachments" class="mv-input" type="file" name="attachments[]" multiple>
        </x-ui.field>

        <div class="flex justify-end">
            <button class="mv-button-primary" type="submit">
                Submeter pedido
            </button>
        </div>
    </form>
</x-app-layout>
