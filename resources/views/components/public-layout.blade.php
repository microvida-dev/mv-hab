@props([
    'title' => 'Arrendamento Acessível',
    'description' => 'Portal municipal de Arrendamento Acessível. Consulte programas, concursos e prazos publicados.',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'twitterCard' => 'summary_large_image',
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
        <meta property="og:type" content="{{ $ogType }}">
        <meta name="twitter:card" content="{{ $twitterCard }}">
        <meta name="twitter:title" content="{{ $title }} · MV HAB">
        <meta name="twitter:description" content="{{ $description }}">

        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
            <meta property="og:url" content="{{ $canonical }}">
        @endif

        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            <meta name="twitter:image" content="{{ $ogImage }}">
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

    <body class="min-h-screen bg-mvhab-surface font-sans text-ink-900 antialiased">
        <a href="#conteudo-principal" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-2xl focus:bg-mvhab-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
            Saltar para o conteúdo principal
        </a>

        <header class="border-b border-ink-100 bg-white">
            <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.portal') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-auto fill-current text-mvhab-primary" />

                    <div>
                        <p class="text-base font-bold text-ink-900">MV HAB</p>
                        <p class="text-xs font-medium text-ink-500">Arrendamento Acessível</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 md:flex" aria-label="Navegação pública">
                    <a href="{{ route('public.housing-offer.index') }}" class="text-sm font-semibold text-ink-600 hover:text-mvhab-primary">Oferta habitacional</a>
                    <a href="{{ route('public.programs.index') }}" class="text-sm font-semibold text-ink-600 hover:text-mvhab-primary">Programas</a>
                    <a href="{{ route('public.contests.index') }}" class="text-sm font-semibold text-ink-600 hover:text-mvhab-primary">Concursos</a>
                    <a href="{{ route('public.simulator.show') }}" class="text-sm font-semibold text-ink-600 hover:text-mvhab-primary">Simulador</a>
                    <a href="{{ route('public.faq') }}" class="text-sm font-semibold text-ink-600 hover:text-mvhab-primary">Perguntas frequentes</a>
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        @if (Auth::user()->hasRole('candidate'))
                            <a href="{{ route('candidate.dashboard') }}" class="mv-button-secondary">
                                Área do candidato
                            </a>
                        @elseif (Auth::user()->hasRole('tenant'))
                            <a href="{{ route('tenant.dashboard') }}" class="mv-button-secondary">
                                Área do inquilino
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="mv-button-secondary">
                                Área reservada
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="mv-button-secondary">
                                Sair
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="mv-button-secondary">
                            Entrar
                        </a>

                        <a href="{{ route('register') }}" class="mv-button-primary hidden sm:inline-flex">
                            Criar conta
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="conteudo-principal" tabindex="-1">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-ink-100 bg-ink-50">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <p class="font-semibold text-ink-900">MV HAB</p>
                    <p class="mt-2 text-sm leading-6 text-ink-500">
                        Informação pública sobre programas municipais e concursos de Arrendamento Acessível.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Consultar</p>
                    <div class="mt-3 grid gap-2 text-sm text-ink-500">
                        <a href="{{ route('public.programs.index') }}" class="hover:text-mvhab-primary">Programas</a>
                        <a href="{{ route('public.contests.index') }}" class="hover:text-mvhab-primary">Concursos</a>
                        <a href="{{ route('public.housing-units.index') }}" class="hover:text-mvhab-primary">Habitações</a>
                        <a href="{{ route('public.simulator.show') }}" class="hover:text-mvhab-primary">Simulador</a>
                        <a href="{{ route('public.faq') }}" class="hover:text-mvhab-primary">Perguntas frequentes</a>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Privacidade</p>
                    <p class="mt-3 text-sm leading-6 text-ink-500">
                        As páginas públicas não apresentam dados pessoais de candidatos.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
