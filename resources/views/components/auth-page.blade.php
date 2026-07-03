@props([
    'title',
    'description' => null,
    'logoWidth' => 'w-56',
])

<x-auth-portal-link />

<x-auth-card
    :title="$title"
    :description="$description"
    :logo-width="$logoWidth"
>
    {{ $slot }}
</x-auth-card>
