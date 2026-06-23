<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Portal público</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Configurações da oferta habitacional</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.public-portal.settings.update') }}" class="grid gap-5 rounded-md border border-ink-100 bg-white p-6">
            @csrf
            @method('PUT')
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="show_map" value="1" @checked($settings['show_map'] ?? true) class="rounded border-ink-300"> Mostrar mapa público</label>
            <label><span class="text-sm font-semibold text-ink-700">Título</span><input name="portal_title" value="{{ $settings['portal_title'] ?? '' }}" class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
            <label><span class="text-sm font-semibold text-ink-700">Descrição</span><textarea name="portal_description" rows="3" class="mt-1 w-full rounded-md border-ink-200 text-sm">{{ $settings['portal_description'] ?? '' }}</textarea></label>
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="text-sm font-semibold text-ink-700">Latitude centro</span><input type="number" step="0.0000001" name="map_center_lat" value="{{ $settings['map_center_lat'] ?? 39.4595 }}" class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
                <label><span class="text-sm font-semibold text-ink-700">Longitude centro</span><input type="number" step="0.0000001" name="map_center_lng" value="{{ $settings['map_center_lng'] ?? -8.6674 }}" class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
                <label><span class="text-sm font-semibold text-ink-700">Zoom</span><input type="number" min="1" max="18" name="map_zoom" value="{{ $settings['map_zoom'] ?? 12 }}" class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
            </div>
            <button class="mv-button-primary">Guardar configurações</button>
        </form>
    </div></div>
</x-app-layout>
