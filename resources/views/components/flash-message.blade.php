@if (session('success'))
    <div
        class="flex gap-3 rounded-2xl border border-mvhab-support/40 bg-mvhab-surface px-4 py-3 text-sm text-mvhab-primary"
        role="status"
        aria-live="polite"
    >
        <x-ui-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('warning'))
    <div
        class="flex gap-3 rounded-2xl border border-signal-200 bg-signal-50 px-4 py-3 text-sm text-signal-900"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
    >
        <x-ui-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>{{ session('warning') }}</div>
    </div>
@endif

@if ($errors->any())
    <div
        class="flex gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        role="alert"
        aria-live="assertive"
    >
        <x-ui-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
            <p class="font-semibold">Existem dados por corrigir.</p>
            <ul class="mt-2 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
