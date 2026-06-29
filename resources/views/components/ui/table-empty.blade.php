@props([
    'colspan',
    'message' => 'Sem registos para apresentar.',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-10 text-center text-sm text-ink-500">
        {{ $message }}
    </td>
</tr>
