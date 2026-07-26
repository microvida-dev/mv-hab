<x-mv.section title="Não foi possível continuar">
    <x-mv.alert
        tone="danger"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        {{ $publicMessage }}
    </x-mv.alert>

    <p class="mt-4 text-sm leading-6 text-ink-600">
        Se considerar que deveria ter acesso, contacte o responsável municipal e indique a referência
        <span class="font-mono font-semibold text-ink-900">{{ $requestId }}</span>.
    </p>

    @if (is_array($landingPage ?? null))
        <div class="mt-6">
            <a href="{{ $landingPage['url'] }}" class="mv-button-primary">
                {{ $landingPage['label'] }}
            </a>
        </div>
    @endif
</x-mv.section>
