# PHPStan Quality Gate Enterprise

## Objetivo

Impedir regressões PHPStan e manter a análise estática global sem erros após o fecho da dívida histórica na PHPSTAN-19.

Esta política aplica-se ao estado pós-PHPSTAN-19 e deve ser usada em CI/CD local ou remoto.

## Política

O quality gate deve falhar quando acontecer qualquer uma destas condições:

- `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` devolver qualquer erro;
- o número normalizado de erros PHPStan for superior a `0`;
- existir qualquer erro novo por assinatura exata sem linha numa comparação de regressão;
- `./vendor/bin/pint --test` falhar;
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` falhar;
- `php artisan route:list --except-vendor` falhar;
- forem adicionados `@phpstan-ignore`, `@phpstan-ignore-line` ou `@phpstan-ignore-next-line`;
- forem adicionados `ignoreErrors` ou baseline PHPStan para esconder dívida técnica.

## Assinatura de erro

A comparação deve usar:

```text
file | identifier | message
```

A linha não entra na assinatura para evitar falsos positivos quando uma correção desloca código sem introduzir erro novo.

## Comandos locais recomendados

```bash
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-current.json
php scripts/phpstan-count-errors.php storage/phpstan/phpstan-current.json
```

O resultado esperado de `phpstan-count-errors.php` é:

```json
{
    "wrapper_errors": 0,
    "normalized_errors": 0,
    "files": 0,
    "identifiers": []
}
```

Quando existir comparação entre duas execuções, pode ser usado:

```bash
php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-previous.json storage/phpstan/phpstan-current.json
```

O resultado esperado é `status=passed`, `new=0` e `current_normalized_errors=0`.

## Nota operacional

Neste ambiente, `php artisan test` não deve ser usado como validação principal porque os processos filhos ficam limitados a `128MB`.

Usar:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

## Política para pull requests

- Código novo deve entrar sem novos erros PHPStan.
- Erros PHPStan não podem ser reintroduzidos.
- Correções devem ser agrupadas por domínio funcional.
- Não usar suppressions como substituto de tipagem correta.
- Domínios de segurança, RGPD, auditoria, documentos privados, elegibilidade, ranking, contratos e pagamentos exigem testes dirigidos quando forem alterados.

## Suppressions proibidos

Não é permitido introduzir:

- `ignoreErrors`;
- baseline PHPStan;
- `@phpstan-ignore`;
- `@phpstan-ignore-line`;
- `@phpstan-ignore-next-line`;
- widening artificial de tipos apenas para silenciar análise estática.
