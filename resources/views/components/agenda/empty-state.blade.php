@props([
    'title' => 'Sem eventos agendados',
    'description' => 'Não existem eventos para o período selecionado. Experimente alterar a data ou remover filtros.',
])

<div class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-12 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-2xl shadow-sm">
        📅
    </div>
    <p class="mt-5 text-lg font-bold text-slate-800">{{ $title }}</p>
    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
</div>
