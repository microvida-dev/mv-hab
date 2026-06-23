<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Novo pedido de manutenção</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <form class="mv-card grid gap-4" method="POST" action="{{ route('tenant.maintenance.store') }}" enctype="multipart/form-data">
            @csrf
            <p class="text-sm text-ink-600">Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.</p>
            <label class="grid gap-1 text-sm font-medium">Categoria
                <select name="maintenance_category_id" class="mv-input">
                    <option value="">Sem categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium">Urgência
                <select name="urgency" class="mv-input" required>
                    @foreach ($urgencies as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium">Título <input class="mv-input" name="title" required maxlength="255"></label>
            <label class="grid gap-1 text-sm font-medium">Descrição <textarea class="mv-input" name="description" rows="5" required></textarea></label>
            <label class="grid gap-1 text-sm font-medium">Local na habitação <input class="mv-input" name="location_in_property" maxlength="255"></label>
            <label class="grid gap-1 text-sm font-medium">Disponibilidade <textarea class="mv-input" name="tenant_availability" rows="3"></textarea></label>
            <button class="mv-button-primary" type="submit">Submeter pedido</button>
        </form>
    </div>
</x-app-layout>
