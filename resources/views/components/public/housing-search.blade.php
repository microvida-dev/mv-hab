<section class="mv-section bg-ink-50">
    <div class="mv-container">
        <div class="max-w-3xl">
            <p class="mv-caption">Encontrar habitação</p>

            <h2 class="mv-heading mt-3">
                Procure a habitação mais adequada
            </h2>

            <p class="mv-description mt-6">
                Consulte a oferta habitacional disponível e filtre por freguesia, tipologia e valor da renda.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('public.housing-offer.index') }}"
            class="mv-card mt-10 grid gap-4 p-6 md:grid-cols-2 lg:grid-cols-5"
        >
            <label>
                <span class="mv-data-label">Pesquisa</span>
                <input
                    type="search"
                    name="q"
                    class="mv-input mt-1"
                    placeholder="Localidade ou descrição"
                >
            </label>

            <label>
                <span class="mv-data-label">Freguesia</span>
                <input
                    type="search"
                    name="parish"
                    class="mv-input mt-1"
                    placeholder="Ex.: Alcanena"
                >
            </label>

            <label>
                <span class="mv-data-label">Tipologia</span>
                <select name="typology" class="mv-select mt-1">
                    <option value="">Todas</option>
                    <option value="T0">T0</option>
                    <option value="T1">T1</option>
                    <option value="T2">T2</option>
                    <option value="T3">T3</option>
                    <option value="T4">T4</option>
                    <option value="T5">T5+</option>
                </select>
            </label>

            <label>
                <span class="mv-data-label">Renda máxima</span>
                <input
                    type="number"
                    name="rent_max"
                    min="0"
                    step="1"
                    class="mv-input mt-1"
                    placeholder="€"
                >
            </label>

            <div class="flex items-end">
                <button type="submit" class="mv-button-primary w-full">
                    Pesquisar
                </button>
            </div>
        </form>
    </div>
</section>
