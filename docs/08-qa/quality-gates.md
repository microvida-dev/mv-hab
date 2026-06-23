# Quality Gates

Data: 16/06/2026.

| Gate | Estado | Evidência | Bloqueia produção? |
| --- | --- | --- | --- |
| `php artisan route:list` sem erro | Validado | 830 rotas listadas sem erro | Sim |
| `php artisan test` sem falhas críticas | Validado | Suite completa passou com 174 testes/1164 asserções | Sim |
| `npm run build` sem erro | Validado | Build Vite passou | Sim |
| `./vendor/bin/pint --test` sem alterações pendentes | Validado após correção | Falhou inicialmente por estilo; `./vendor/bin/pint` aplicado e `--test` passou | Sim |
| PHPStan/Psalm sem erros críticos | `not_applicable` | Não configurados em `vendor/bin` | Não, mas é risco |
| Sem bugs Critical abertos | Cumprido nesta sprint | Nenhum bug crítico de produção identificado | Sim |
| Sem falhas de autorização conhecidas | Validado nos testes existentes | `PermissionMatrixTest` e regressão das sprints anteriores | Sim |
| Sem exposição conhecida de documentos privados | Validado nos testes existentes | `DocumentSecurityFlowTest` e regressão documental | Sim |
| Elegibilidade determinística | Validado | `EligibilityCalculationDeterministicTest` | Sim |
| Pontuação determinística | Validado | `ScoringCalculationDeterministicTest` | Sim |
| Renda determinística | Validado | `RentCalculationDeterministicTest` | Sim |
| Fluxo integrado principal | Validado em dataset de teste | `FullHousingProgramFlowTest` | Sim |
| Plano de regressão criado | Cumprido | `docs/qa/regression-test-plan.md` | Sim |
| Relatório de qualidade criado | Cumprido | `docs/qa/sprint-19-quality-report.md` | Sim |
| Matriz de cobertura criada | Cumprido | `docs/qa/test-coverage-matrix.md` | Sim |

## Decisão final Sprint 19

Gate final: `ready_for_staging_with_minor_risks`.

Comandos finais executados:

- `php artisan route:list`;
- `php artisan test`;
- `npm run build`;
- `./vendor/bin/pint`;
- `./vendor/bin/pint --test`;
- `composer validate --no-check-publish`;
- `php artisan view:cache`;
- `php artisan view:clear`.

Não declarar produção pronta antes de:

- validação jurídica/RGPD final;
- revisão de infraestrutura, backups, logs, permissões e pentest externo.
