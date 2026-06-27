<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <a href="#conteudo-principal" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-civic-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
            Saltar para o conteúdo principal
        </a>

        @php
            $showSidebar = ! request()->routeIs('dashboard');
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-ink-50">
            @if ($showSidebar)
                @include('layouts.navigation')
            @endif

            <div @class(['lg:ps-72' => $showSidebar])>
                @isset($header)
                    <header class="border-b border-ink-100 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main id="conteudo-principal" tabindex="-1">
                    @if ($showSidebar)
                        <x-navigation.breadcrumbs />
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
