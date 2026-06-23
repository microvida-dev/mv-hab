@props([
    'title' => 'Arrendamento Acessível',
    'description' => 'Portal municipal de Arrendamento Acessível. Consulte programas, concursos e prazos publicados.',
    'canonical' => null,
    'ogImage' => null,
    'jsonLd' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description }}">
        <meta property="og:title" content="{{ $title }} · MV HAB">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:type" content="website">
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
        @endif

        <title>{{ $title }} · MV HAB</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if (! empty($jsonLd))
            <script type="application/ld+json">
                {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endif
    </head>
    <body class="min-h-screen bg-white font-sans text-ink-900 antialiased">
        <header class="border-b border-ink-100 bg-white">
            <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.portal') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-auto fill-current text-civic-700" />
                    <div>
                        <p class="text-base font-bold text-ink-900">MV HAB</p>
                        <p class="text-xs font-medium text-ink-500">Arrendamento Acessível</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 md:flex" aria-label="Navegação pública">
                    <a href="{{ route('public.housing-offer.index') }}" class="text-sm font-semibold text-ink-600 hover:text-civic-700">Oferta habitacional</a>
                    <a href="{{ route('public.programs.index') }}" class="text-sm font-semibold text-ink-600 hover:text-civic-700">Programas</a>
                    <a href="{{ route('public.contests.index') }}" class="text-sm font-semibold text-ink-600 hover:text-civic-700">Concursos</a>
                    <a href="{{ route('public.simulator.show') }}" class="text-sm font-semibold text-ink-600 hover:text-civic-700">Simulador</a>
                    <a href="{{ route('public.faq') }}" class="text-sm font-semibold text-ink-600 hover:text-civic-700">Perguntas frequentes</a>
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="mv-button-secondary">
                            {{ Auth::user()->hasRole('candidate') ? 'Área do candidato' : 'Backoffice' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mv-button-secondary">Sair</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="mv-button-secondary">Entrar</a>
                        <a href="{{ route('register') }}" class="mv-button-primary hidden sm:inline-flex">Criar conta</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-ink-100 bg-ink-50">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <p class="font-semibold text-ink-900">MV HAB</p>
                    <p class="mt-2 text-sm leading-6 text-ink-500">Informação pública sobre programas municipais e concursos de Arrendamento Acessível.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink-900">Consultar</p>
                    <div class="mt-3 grid gap-2 text-sm text-ink-500">
                        <a href="{{ route('public.programs.index') }}" class="hover:text-civic-700">Programas</a>
                        <a href="{{ route('public.contests.index') }}" class="hover:text-civic-700">Concursos</a>
                        <a href="{{ route('public.housing-units.index') }}" class="hover:text-civic-700">Habitações</a>
                        <a href="{{ route('public.simulator.show') }}" class="hover:text-civic-700">Simulador</a>
                        <a href="{{ route('public.faq') }}" class="hover:text-civic-700">Perguntas frequentes</a>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink-900">Privacidade</p>
                    <p class="mt-3 text-sm leading-6 text-ink-500">As páginas públicas não apresentam dados pessoais de candidatos.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
