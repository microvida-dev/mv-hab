@auth
    <x-app-layout>
        <x-slot name="header">
            <x-mv.page-header
                eyebrow="Segurança"
                title="Acesso não autorizado"
                description="A operação foi recusada sem alterar qualquer dado."
            />
        </x-slot>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                @include('errors.partials.403-content')
            </div>
        </div>
    </x-app-layout>
@else
    <x-guest-layout>
        <div class="space-y-6">
            <x-mv.page-header
                eyebrow="Segurança"
                title="Acesso não autorizado"
                description="A operação foi recusada sem alterar qualquer dado."
            />
            @include('errors.partials.403-content')
        </div>
    </x-guest-layout>
@endauth
