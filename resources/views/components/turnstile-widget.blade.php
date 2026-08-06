@if ($enabled)
    <div class="space-y-2" data-turnstile-context="{{ $context }}">
        @if ($siteKey)
            <div
                class="cf-turnstile"
                data-sitekey="{{ $siteKey }}"
                data-action="{{ $action }}"
                data-theme="auto"
                data-language="pt"
                data-response-field-name="turnstile_token"
            ></div>
        @else
            <div
                role="alert"
                class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900"
            >
                A verificação de segurança está temporariamente indisponível.
            </div>
        @endif

        @error('turnstile_token')
            <p class="text-sm font-medium text-red-600" role="alert">
                {{ $message }}
            </p>
        @enderror

        <noscript>
            <p class="text-sm font-medium text-amber-800">
                Ative o JavaScript para concluir a verificação de segurança.
            </p>
        </noscript>
    </div>

    @if ($siteKey)
        @once
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endonce
    @endif
@endif
