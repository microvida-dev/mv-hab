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

        <link rel="icon" type="image/png" href="{{ asset('images/brand/logo-mvhab-semfundo-comp.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/logo-mvhab-semfundo-comp.png') }}">

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

        <header
            x-data="{ mobileMenuOpen: false }"
            class="sticky top-0 z-50 border-b border-ink-100 bg-white/95 backdrop-blur-md"
        >
            <div class="mx-auto flex min-h-24 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('public.portal') }}" class="flex shrink-0 items-center">
                    <img
                        src="{{ asset('images/brand/logo-mvhab-semfundo-comp.png') }}"
                        alt="MV HAB — Arrendamento Acessível"
                        class="h-16 w-auto"
                    >
                </a>

                @php
                    $publicNavItems = [
                        ['label' => 'Oferta habitacional', 'route' => 'public.housing-offer.index', 'active' => request()->routeIs('public.housing-offer.*') || request()->routeIs('public.housing-units.*')],
                        ['label' => 'Programas', 'route' => 'public.programs.index', 'active' => request()->routeIs('public.programs.*')],
                        ['label' => 'Concursos', 'route' => 'public.contests.index', 'active' => request()->routeIs('public.contests.*')],
                        ['label' => 'Simulador', 'route' => 'public.simulator.show', 'active' => request()->routeIs('public.simulator.*')],
                        ['label' => 'Perguntas frequentes', 'route' => 'public.faq', 'active' => request()->routeIs('public.faq')],
                    ];
                @endphp

                <nav class="hidden items-center gap-2 lg:flex" aria-label="Navegação pública">
                    @foreach ($publicNavItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'rounded-2xl px-3 py-2 text-sm font-semibold transition duration-200',
                                'bg-mvhab-surface text-mvhab-primary' => $item['active'],
                                'text-ink-600 hover:bg-mvhab-surface hover:text-mvhab-primary' => ! $item['active'],
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <div class="hidden items-center gap-2 sm:flex">
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

                            <a href="{{ route('register') }}" class="mv-button-primary">
                                Criar conta
                            </a>
                        @endauth
                    </div>

                    <button
                        type="button"
                        class="mv-button-secondary lg:hidden"
                        aria-controls="mobile-public-menu"
                        :aria-expanded="mobileMenuOpen.toString()"
                        @click="mobileMenuOpen = ! mobileMenuOpen"
                    >
                        Menu
                    </button>
                </div>
            </div>

            <div
                id="mobile-public-menu"
                x-cloak
                x-show="mobileMenuOpen"
                x-transition
                class="border-t border-ink-100 bg-white lg:hidden"
            >
                <div class="mv-container grid gap-2 py-4">
                    @foreach ($publicNavItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'rounded-2xl px-4 py-3 text-sm font-semibold transition duration-200',
                                'bg-mvhab-surface text-mvhab-primary' => $item['active'],
                                'text-ink-600 hover:bg-mvhab-surface hover:text-mvhab-primary' => ! $item['active'],
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    <div class="mt-3 grid gap-2 border-t border-ink-100 pt-3">
                        @auth
                            @if (Auth::user()->hasRole('candidate'))
                                <a href="{{ route('candidate.dashboard') }}" class="mv-button-secondary justify-center">
                                    Área do candidato
                                </a>
                            @elseif (Auth::user()->hasRole('tenant'))
                                <a href="{{ route('tenant.dashboard') }}" class="mv-button-secondary justify-center">
                                    Área do inquilino
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="mv-button-secondary justify-center">
                                    Área reservada
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="mv-button-secondary w-full justify-center">
                                    Sair
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="mv-button-secondary justify-center">
                                Entrar
                            </a>

                            <a href="{{ route('register') }}" class="mv-button-primary justify-center">
                                Criar conta
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main id="conteudo-principal" tabindex="-1">
            {{ $slot }}
        </main>

       <footer class="border-t border-ink-100 bg-white">
            <div class="mv-container grid gap-10 py-12 md:grid-cols-[minmax(0,1.5fr)_1fr_1fr]">
                <div>
                    <img
                        src="{{ asset('images/brand/logo-mvhab-semfundo-comp.png') }}"
                        alt="MV HAB"
                        class="h-14 w-auto"
                    >

                    <p class="mv-section-description mt-5 max-w-md">
                        Plataforma municipal para consulta de programas, concursos, habitações e acompanhamento dos processos de Arrendamento Acessível.
                    </p>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Consultar</p>

                    <div class="mt-4 grid gap-3 text-sm">
                        <a href="{{ route('public.housing-offer.index') }}" class="mv-link">Oferta habitacional</a>
                        <a href="{{ route('public.programs.index') }}" class="mv-link">Programas</a>
                        <a href="{{ route('public.contests.index') }}" class="mv-link">Concursos</a>
                        <a href="{{ route('public.simulator.show') }}" class="mv-link">Simulador</a>
                        <a href="{{ route('public.faq') }}" class="mv-link">Perguntas frequentes</a>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-ink-900">Privacidade</p>

                    <p class="mv-section-description mt-4">
                        As páginas públicas não apresentam dados pessoais de candidatos nem documentos reservados.
                    </p>

                    <p class="mt-4 text-xs leading-5 text-ink-400">
                        © {{ now()->year }} MV HAB. Plataforma municipal de habitação.
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>
