@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border-ink-100 bg-white text-ink-900 shadow-sm placeholder:text-ink-500 focus:border-civic-500 focus:ring-civic-500']) }}>
