@if (session('success'))
    <div class="flex gap-3 rounded-lg border border-civic-100 bg-civic-50 px-4 py-3 text-sm text-civic-900">
        <x-ui-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>{{ session('success') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
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
