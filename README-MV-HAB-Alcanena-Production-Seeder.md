# MV-HAB — Seeder de produção de Alcanena

## Objetivo

Criar a baseline inicial de produção para o Município de Alcanena, já existente e com onboarding concluído.

O pacote cria apenas:

- 16 tipos documentais provenientes do catálogo anteriormente validado;
- 4 finalidades RGPD;
- Programa Municipal de Arrendamento Acessível de Alcanena em `draft`;
- 6 regras descritivas do programa;
- Concurso n.º 01/2026 em `draft`;
- prazo provisório de candidaturas.

O pacote não cria nem altera:

- Município;
- utilizadores;
- roles ou permissions;
- assignments de operador;
- entitlements;
- documentos obrigatórios;
- habitações;
- critérios de elegibilidade ou scoring;
- júri;
- notificações;
- publicações.

## Proteções

- exige o Município `ALCANENA` ativo;
- exige onboarding municipal concluído;
- resolve o operador global e o administrador municipal a partir do ledger de onboarding;
- falha se existirem códigos/slugs em conflito;
- não restaura registos soft-deleted;
- não atualiza registos já existentes;
- programa e concurso ficam sem `published_at`;
- execução integral dentro de transação;
- segunda execução não duplica dados.

## Instalação no repositório

Descompactar o pacote na raiz do MV-HAB, preservando as pastas:

```bash
cd "$HOME/Documents/MV-HAB"
unzip -o /caminho/MV-HAB-Alcanena-Production-Seeder.zip
```

Validar:

```bash
bash scripts/production/install-alcanena-production-seeder.sh
```

O script executa sintaxe e o teste dirigido, mas não aplica dados reais por defeito.

## Execução real

Antes da execução:

```bash
php artisan about
php artisan migrate:status
php artisan db:show
```

Criar backup da base de dados e confirmar:

```text
Municipality code = ALCANENA
Onboarding status = completed
Programs = 0
Contests = 0
```

Executar explicitamente:

```bash
RUN_PRODUCTION_SEEDER=1 \
  bash scripts/production/install-alcanena-production-seeder.sh
```

Comando Artisan equivalente:

```bash
php artisan db:seed \
  --class='Database\\Seeders\\Production\\AlcanenaProductionSeeder' \
  --force
```

## Validação posterior

```bash
php artisan tinker --execute="
dump([
    'municipalities' => App\\Models\\Municipality::query()->count(),
    'document_types' => App\\Models\\DocumentType::query()->count(),
    'consent_purposes' => App\\Models\\ConsentPurpose::query()->count(),
    'program' => App\\Models\\Program::query()
        ->where('slug', 'programa-municipal-arrendamento-acessivel-alcanena')
        ->first()?->only(['id', 'municipality_id', 'slug', 'status', 'published_at']),
    'contest' => App\\Models\\Contest::query()
        ->where('code', 'ALC-RAA-01-2026')
        ->first()?->only(['id', 'program_id', 'code', 'status', 'published_at']),
]);
"
```

Resultado esperado:

```text
Municípios novos: 0
Tipos documentais: 16 no catálogo base, sem duplicados
Finalidades RGPD: 4 no catálogo base, sem duplicados
Programa: draft, published_at null
Concurso: draft, published_at null
Prazos do concurso: 1 prazo provisório de candidaturas
```

## Reexecução

O seeder é create-only. Não substitui alterações efetuadas posteriormente no backoffice. Ainda assim, depois da validação manual, a reexecução deve ocorrer apenas com revisão operacional explícita.
