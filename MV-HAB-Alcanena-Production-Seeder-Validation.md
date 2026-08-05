# Resultado de validação

## Ficheiros

- `database/seeders/Production/AlcanenaProductionSeeder.php`
- `tests/Feature/Seeders/AlcanenaProductionSeederTest.php`
- `scripts/production/install-alcanena-production-seeder.sh`

## Gates executados

```text
PHP lint — seeder: PASS
PHP lint — teste: PASS
Bash syntax: PASS
git diff --check: PASS
PHPStan dirigido: PASS — 0 erros
```

## PHPUnit

O teste dirigido foi criado com três cenários:

1. criação da baseline sem criar Município nem publicar;
2. replay sem duplicações e com preservação de alteração manual;
3. falha fechada sem onboarding concluído.

A execução PHPUnit não pôde arrancar no runtime isolado desta sessão porque o binário PHP disponível não contém `dom`, `mbstring`, `xml` e `xmlwriter`. O ficheiro deve ser executado no ambiente normal do projeto, que já possui os requisitos PHP 8.4 usados pelos gates oficiais.

## Análise arquitetural

O seeder permanece compatível com os códigos e definições canónicas do serviço existente:

- `AlcanenaInitialCatalogService::PROGRAM_SLUG`;
- `AlcanenaInitialCatalogService::CONTEST_CODE`;
- `AlcanenaInitialCatalogService::CONTEST_SLUG`;
- programa e concurso em `draft`;
- `regulatory_profile_id`, `regulatory_snapshot_id` e `legal_regime` permanecem nulos;
- nenhuma publicação ou entitlement é criado.
