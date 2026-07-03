<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MVHAB - Habitação Pública') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-slate-900">
    <a href="#conteudo-principal" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-2xl focus:bg-mvhab-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Saltar para o conteúdo principal
    </a>

    <div class="relative min-h-screen overflow-hidden bg-slate-50">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(96,165,250,0.30),transparent_32%),radial-gradient(circle_at_20%_85%,rgba(124,58,237,0.22),transparent_30%),linear-gradient(115deg,#eef5ff_0%,#f8fbff_45%,#ffffff_100%)]"></div>
        <div class="absolute left-0 top-0 hidden h-full w-1/2 bg-gradient-to-br from-blue-100/80 via-indigo-100/60 to-purple-100/70 lg:block"></div>
        <div class="absolute left-0 top-0 hidden h-full w-1/2 overflow-hidden lg:block">
            <div class="absolute -left-24 -top-20 h-80 w-80 rotate-45 bg-slate-300/15"></div>
            <div class="absolute left-16 top-24 h-56 w-56 rotate-45 bg-white/35"></div>
            <div class="absolute -left-20 top-64 h-72 w-72 rotate-45 bg-blue-900/35"></div>
            <div class="absolute left-52 top-72 h-36 w-36 rotate-45 bg-slate-400/20"></div>
            <div class="absolute left-12 bottom-24 h-52 w-52 rotate-45 bg-white/40"></div>
            <div class="absolute left-64 bottom-10 h-64 w-64 rotate-45 bg-slate-500/20"></div>
            <div class="absolute left-96 bottom-28 h-52 w-52 rotate-45 bg-blue-900/35"></div>
        </div>

        <main id="conteudo-principal" tabindex="-1" class="relative z-10 grid min-h-screen grid-cols-1 lg:grid-cols-2">
            <section class="hidden items-center px-16 lg:flex">
                <div class="max-w-lg">

                    <h1 class="text-4xl font-bold tracking-tight text-blue-950">
                        Plataforma Municipal<br>de Habitação
                    </h1>

                    <div class="mt-6 h-1 w-16 rounded-full bg-gradient-to-r from-blue-700 to-purple-600"></div>

                    <p class="mt-8 text-lg leading-8 text-slate-700">
                        Gerimos processos de habitação pública de forma simples, transparente e eficiente, ao serviço das pessoas e do território.
                    </p>

                    <a
                        href="{{ url('/') }}"
                        class="mt-10 inline-flex items-center gap-2 rounded-2xl bg-white/80 px-5 py-3 text-sm font-bold text-blue-950 shadow-sm ring-1 ring-blue-100 transition hover:bg-white hover:shadow-md focus:outline-none focus:ring-4 focus:ring-blue-200"
                    >
                        <span aria-hidden="true">←</span>
                        Voltar ao portal
                    </a>

                    <div class="mt-28 flex items-center gap-3 text-sm font-medium text-blue-950">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white/70 shadow-sm ring-1 ring-blue-100">
                            <x-mv-icon name="shield" class="h-5 w-5 text-purple-700" />
                        </span>
                        <span>Uma plataforma segura, moderna e dedicada ao bem público.</span>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center px-6 py-10">
                <div class="w-full max-w-xl">
                    {{ $slot }}
                </div>
            </section>
        </main>
    </div>
</body>
</html>
