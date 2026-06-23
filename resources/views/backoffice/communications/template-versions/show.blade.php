<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">{{ $notificationTemplateVersion->template->name }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Versão {{ $notificationTemplateVersion->version_number }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <section class="rounded-md border border-ink-100 bg-white p-6"><p class="text-sm text-ink-500">{{ $notificationTemplateVersion->status->label() }}</p><h2 class="mt-2 text-lg font-semibold">{{ $notificationTemplateVersion->title }}</h2><p class="mt-5 whitespace-pre-line text-sm leading-7">{{ $notificationTemplateVersion->body }}</p></section>
        <div class="flex flex-wrap gap-3">
            @can('approve', $notificationTemplateVersion)
                <form method="POST" action="{{ route('backoffice.communications.template-versions.approve', $notificationTemplateVersion) }}">@csrf<x-primary-button>Aprovar</x-primary-button></form>
                <form method="POST" action="{{ route('backoffice.communications.template-versions.activate', $notificationTemplateVersion) }}">@csrf<x-secondary-button>Ativar</x-secondary-button></form>
            @endcan
            @can('update', $notificationTemplateVersion)
                <form method="POST" action="{{ route('backoffice.communications.template-versions.archive', $notificationTemplateVersion) }}">@csrf<x-danger-button>Arquivar</x-danger-button></form>
            @endcan
        </div>
    </div></div>
</x-app-layout>
