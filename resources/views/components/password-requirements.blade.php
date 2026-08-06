@props([
    'id' => 'password-requirements',
])

<div
    id="{{ $id }}"
    role="note"
    {{ $attributes->merge([
        'class' => 'rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700',
    ]) }}
>
    <p class="font-semibold text-slate-800">
        {{ __('auth.password_requirements.title') }}
    </p>

    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>
            {{ __('auth.password_requirements.length', [
                'min' => \App\Services\Security\PasswordPolicyService::MIN_LENGTH,
                'max' => \App\Services\Security\PasswordPolicyService::MAX_LENGTH,
            ]) }}
        </li>
        <li>{{ __('auth.password_requirements.mixed_case') }}</li>
        <li>{{ __('auth.password_requirements.number') }}</li>
        <li>{{ __('auth.password_requirements.symbol') }}</li>
    </ul>
</div>
