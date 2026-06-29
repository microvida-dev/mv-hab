<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">
            Novo pedido de manutenção
        </h1>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form
            method="POST"
            action="{{ route('tenant.maintenance.store') }}"
            enctype="multipart/form-data"
            class="mv-card grid gap-5"
        >
            @csrf

            <p class="text-sm leading-6 text-ink-600">
                Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.
            </p>

            <x-ui.field label="Categoria" for="maintenance_category_id" name="maintenance_category_id">
                <x-ui.select id="maintenance_category_id" name="maintenance_category_id">
                    <option value="">Sem categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field label="Urgência" for="urgency" name="urgency" required>
                <x-ui.select id="urgency" name="urgency" required>
                    @foreach ($urgencies as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field label="Título" for="title" name="title" required>
                <x-ui.input id="title" name="title" required maxlength="255" />
            </x-ui.field>

            <x-ui.field label="Descrição" for="description" name="description" required>
                <x-ui.textarea id="description" name="description" rows="5" required />
            </x-ui.field>

            <x-ui.field label="Local na habitação" for="location_in_property" name="location_in_property">
                <x-ui.input id="location_in_property" name="location_in_property" maxlength="255" />
            </x-ui.field>

            <x-ui.field label="Disponibilidade" for="tenant_availability" name="tenant_availability">
                <x-ui.textarea id="tenant_availability" name="tenant_availability" rows="3" />
            </x-ui.field>

            <div class="flex justify-end">
                <button class="mv-button-primary" type="submit">
                    Submeter pedido
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
