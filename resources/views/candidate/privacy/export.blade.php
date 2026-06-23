<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $package->package_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-500">Ficheiro: {{ $package->filename }} · expira em {{ $package->expires_at?->format('d/m/Y H:i') }}</p>
            <a href="{{ route('candidate.privacy.exports.download', $package) }}" class="mv-button-primary mt-4 inline-flex">Download</a>
        </section>
    </div></div>
</x-app-layout>
