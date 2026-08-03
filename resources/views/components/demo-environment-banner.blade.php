@if (app(\App\Support\Demo\MunicipalApplicationDemoContext::class)->displayBanner())
    <div
        role="status"
        aria-label="Ambiente de demonstração"
        class="relative z-50 border-b border-amber-300 bg-amber-50 px-4 py-2 text-center text-sm font-semibold text-amber-950"
    >
        Ambiente de demonstração
        <span aria-hidden="true">·</span>
        Dados fictícios
        <span aria-hidden="true">·</span>
        Sem efeitos administrativos
    </div>
@endif
