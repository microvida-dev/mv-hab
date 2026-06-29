@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-hidden mv-surface']) }}>
    <div class="overflow-x-auto">
        <table class="mv-table">
            @if ($headers !== [])
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-ink-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
