# Sprint 25 — Relatório de qualidade

## Âmbito validado

- Sorteio auditável com participantes bloqueados e hash.
- Execução determinística por seed.
- Validação de resultado.
- Registo de vencedor.
- Convocatórias e visibilidade do candidato.
- Presenças.
- Atualização de ranking pós-sorteio.
- Relatório HTML privado.
- Entrega de chaves.
- Transição para inquilino.
- Fecho de concurso.

## Testes criados

- `tests/Unit/Lottery/AuditableLotteryEngineTest.php`
- `tests/Feature/Backoffice/LotteryClosureFlowTest.php`

## Resultado focado

`php artisan test tests/Unit/Lottery/AuditableLotteryEngineTest.php tests/Feature/Backoffice/LotteryClosureFlowTest.php`

Resultado: 3 testes, 34 asserções, OK.

## Resultado final

- `php artisan test`: 210 testes, 1363 asserções, OK.
- `php artisan test tests/Feature/Sprint6DocumentManagementTest.php tests/Feature/Backoffice/LotteryClosureFlowTest.php tests/Unit/Lottery/AuditableLotteryEngineTest.php`: 13 testes, 112 asserções, OK.
- `php artisan route:list`: 1033 rotas, OK.
- `php artisan migrate`: sem migrations pendentes após aplicação da migration da sprint, OK.
- `npm run build`: OK.
- `./vendor/bin/pint`: OK após formatação.
- `./vendor/bin/pint --test`: OK.

## PHPStan inicial

`storage/phpstan/sprint25-before.json` registou estado `failed` com 2764 erros legados no projeto. O ficheiro texto inicial ficou vazio porque a execução foi interrompida para evitar saturação concorrente.

## PHPStan final

Por instrução explícita do utilizador, PHPStan global foi ignorado como gate de conclusão da Sprint 25. A análise focada dos ficheiros da Sprint 25 passou com 0 erros e o PHPStan global permanece tratado como dívida técnica global/herdada fora do bloqueio funcional desta sprint.

## Pendências

- Validar juridicamente textos finais de convocatória, relatório e fecho.
- Implementar PDF oficial se houver infraestrutura documental aprovada.
- Rever políticas municipais para fecho forçado com pendências, se necessário.
