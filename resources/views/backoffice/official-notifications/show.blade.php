<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Notificação oficial"
            :title="$officialNotification?->subject ?? 'Criar notificação'"
            description="Notificações internas registadas sem envio real por email/SMS nesta fase."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if ($officialNotification)
                <x-mv.section title="Mensagem">
                    <div class="flex flex-wrap gap-2">
                        <x-mv.badge>{{ $officialNotification->status->label() }}</x-mv.badge>
                        <x-mv.badge>{{ $officialNotification->channel->label() }}</x-mv.badge>
                    </div>
                    <p class="mt-4 whitespace-pre-line text-sm">{{ $officialNotification->body }}</p>
                    <p class="mt-4 text-xs text-ink-500">Sem envio real por email/SMS nesta sprint.</p>
                </x-mv.section>
            @else
                <form method="POST" action="{{ route('backoffice.official-notifications.store') }}">
                    @csrf
                    <x-mv.section title="Criar notificação">
                        <x-text-input name="user_id" placeholder="ID do utilizador" class="w-full" />
                        <select name="notification_type" class="mv-input mt-4 w-full text-sm">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="subject" placeholder="Assunto" class="mt-4 w-full" />
                        <textarea name="body" class="mv-input mt-4 w-full text-sm" placeholder="Corpo"></textarea>
                        <button type="submit" class="mv-button-primary mt-4">Criar</button>
                    </x-mv.section>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
