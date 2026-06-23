# Enterprise Quality Gate

## Objetivo

Garantir que o estado alcancado apos a PHPSTAN-19 permanece permanente: PHPStan global a `0` erros, testes completos verdes, formatacao validada e rotas carregaveis.

Este documento complementa `docs/qa/phpstan-quality-gate.md` e define a politica global de QA para sprints, pull requests e pre-release.

## Estado minimo aceitavel

| Area | Regra |
| --- | --- |
| Composer | `composer validate` deve passar. |
| Cache/config | `php artisan optimize:clear` deve passar. |
| Pint | `./vendor/bin/pint --test` deve passar sem formatacao pendente. |
| PHPUnit | `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` deve passar. |
| Routes | `php artisan route:list --except-vendor` deve passar. |
| PHPStan | `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` deve passar com `0` erros. |
| Frontend | `npm run build` deve passar quando houver alteracoes frontend ou em pre-release. |

## Comandos oficiais locais

```bash
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/qa/phpstan-current.json
php scripts/phpstan-count-errors.php storage/qa/phpstan-current.json
npm run build
```

O resultado esperado de PHPStan e:

```json
{
    "wrapper_errors": 0,
    "normalized_errors": 0,
    "files": 0,
    "identifiers": []
}
```

## Regras bloqueantes

O quality gate deve falhar quando existir qualquer uma das seguintes situacoes:

- qualquer erro PHPStan novo ou legado;
- baseline PHPStan;
- `ignoreErrors`;
- `@phpstan-ignore`;
- `@phpstan-ignore-line`;
- `@phpstan-ignore-next-line`;
- alargamento artificial de tipos para esconder erro estatico;
- `mixed` introduzido sem justificacao de fronteira externa;
- alteracao funcional critica sem teste dirigido;
- alargamento de permissao sem teste de autorizacao;
- documento privado exposto fora de controller/policy autorizado;
- exportacao RGPD sem auditoria.

## Checklist de pull request

Antes de aceitar uma alteracao:

- [ ] O ambito da alteracao esta descrito.
- [ ] Nao ha alteracoes destrutivas em migrations, seeders ou dados existentes.
- [ ] Controllers continuam finos.
- [ ] Regras de dominio vivem em services, actions ou objetos testaveis.
- [ ] Validacao HTTP vive em Form Requests quando aplicavel.
- [ ] Autorizacao vive em Policies/Gates ou middleware apropriado.
- [ ] Fluxos criticos alterados tem testes.
- [ ] Dados pessoais e documentos privados continuam protegidos.
- [ ] Queries de listagem usam paginacao.
- [ ] Queries de dashboards, rankings e reports usam eager loading quando necessario.
- [ ] Jobs sao idempotentes ou documentam a razao para nao o serem.
- [ ] Eventos transportam apenas payload minimo.
- [ ] `composer validate`, Pint, PHPUnit, route list e PHPStan passam.

## Exemplo de pipeline CI proposto

O projeto atual nao contem diretorio `.github`. Se for ativado GitHub Actions, usar um workflow equivalente:

```yaml
name: quality

on:
  pull_request:
  push:
    branches: [main]

jobs:
  quality:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, dom, fileinfo, mysql
          coverage: none

      - uses: actions/setup-node@v4
        with:
          node-version: '22'

      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: npm ci
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: composer validate
      - run: php artisan optimize:clear
      - run: ./vendor/bin/pint --test
      - run: php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
      - run: php artisan route:list --except-vendor
      - run: ./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/qa/phpstan-ci.json
      - run: php scripts/phpstan-count-errors.php storage/qa/phpstan-ci.json
      - run: npm run build
```

## Politica de excecoes

Excecoes ao quality gate so podem ser aceites com decisao tecnica explicita e temporaria. Nao e aceite esconder divida com baseline ou suppressions. Em dominios de seguranca, RGPD, auditoria, documentos, elegibilidade, scoring, contratos e rendas, a excecao exige teste dirigido e risco residual documentado.
