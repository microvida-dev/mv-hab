# Phase 1 — Hardening Before Municipal Presentation

## 1. Sumario executivo

Phase 1 prepara a MV HAB para demonstracao municipal controlada, focando packaging seguro, segredos, filas/scheduler, ambito do piloto e dados demo.

## 2. Estado inicial

- Branch Phase 1 baseada em QA-36 integrada.
- QA-36 tinha decisao `READY_FOR_STAGING_NOT_PRODUCTION`.
- `docs/08-qa/deep-research-report-v2.md` nao existe nesta branch.

## 3. Decisao municipal sobre CMD e pagamentos fora de ambito

CMD, Autenticacao.gov, pagamentos via plataforma, MB WAY, Multibanco, cartao, gateway de pagamentos, reconciliacao bancaria automatica, importacao SEPA automatica e assinatura digital qualificada ficam `Out of scope by municipal decision`.

## 4. QA-37 — Packaging e segredos

Ver `docs/08-qa/qa-37-release-packaging-secrets-hardening-report.md`.

## 5. QA-37-T — Gate de seguranca de artefactos

Gate local criado por `scripts/check-secrets.php` e `scripts/check-release-artifact-safety.php`.

## 6. QA-38 — Queues/scheduler/workers

Ver `docs/08-qa/qa-38-queues-scheduler-workers-report.md`.

## 7. QA-38-T — Rehearsal operacional

Comando `mvhab:operations:queue-health` criado para validar configuracao de filas, failed jobs e storage dos workers.

## 8. QA-39 — Ambito do piloto e dossier externo

Ver `docs/08-qa/qa-39-pilot-scope-dossier-sanitization-report.md`.

## 9. QA-39-T — Aceitacao do ambito municipal

`docs/11-operacoes/pilot-scope-alcanena.md` formaliza o ambito aceite e as exclusoes.

## 10. QA-40 — Seeders e dados demo

Ver `docs/08-qa/qa-40-municipal-demo-data-seeder-hardening-report.md`.

## 11. QA-40-T — Evidencia smoke de demo

Testes demo e seeder foram adicionados e passaram. O smoke Alcanena valida portal publico, concursos, oferta habitacional, detalhe do fogo e FAQ com dados ficticios.

## 12. Ficheiros/documentacao alterada

- `.gitignore`
- `app/Services/Security/SecretPatternScanner.php`
- `app/Services/Operations/QueueHealthService.php`
- `app/Console/Commands/QueueHealthCommand.php`
- `database/seeders/MunicipalPilotStagingSeeder.php`
- `database/seeders/CandidateSupportDemoSeeder.php`
- `database/seeders/DemoAlcanenaAffordableRentSeeder.php`
- `docs/08-qa/qa-37-release-packaging-secrets-hardening-report.md`
- `docs/08-qa/qa-38-queues-scheduler-workers-report.md`
- `docs/08-qa/qa-39-pilot-scope-dossier-sanitization-report.md`
- `docs/08-qa/qa-40-municipal-demo-data-seeder-hardening-report.md`
- `docs/11-operacoes/release-packaging-safety.md`
- `docs/11-operacoes/queue-failed-jobs-playbook.md`
- `docs/11-operacoes/pilot-scope-alcanena.md`
- `docs/11-operacoes/external-dossier-sanitization-checklist.md`
- `docs/11-operacoes/municipal-demo-data-guide.md`
- testes QA37, QA38, QA39, QA40, Security, Operations, Seeders e Demo.

## 13. Testes executados

- `composer validate --strict`: PASS.
- `php artisan optimize:clear`: PASS.
- `./vendor/bin/pint --test`: PASS.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`: PASS, 406 testes, 2604 assercoes.
- `phpunit --filter QA37`: PASS, 3 testes, 24 assercoes.
- `phpunit --filter QA38`: PASS, 2 testes, 13 assercoes.
- `phpunit --filter QA39`: PASS, 2 testes, 35 assercoes.
- `phpunit --filter QA40`: PASS, 2 testes, 39 assercoes.
- `phpunit --filter Security`: PASS, 48 testes, 281 assercoes.
- `phpunit --filter Operations`: PASS, 12 testes, 100 assercoes.
- `phpunit --filter Seeder`: PASS, 9 testes, 171 assercoes.
- `php artisan route:list --except-vendor`: PASS, 1119 rotas.
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`: PASS, 0 erros.
- `npm run build`: PASS.
- `git diff --check`: PASS.
- `php artisan schedule:list`: PASS; sem tarefas agendadas atuais.
- `php artisan queue:work --stop-when-empty`: PASS; sem jobs pendentes/output.
- `php scripts/check-secrets.php`: PASS.
- `php scripts/check-release-artifact-safety.php`: PASS.

## 14. Quality gate

PASS.

## 15. Riscos residuais

- Restore/rollback real ainda exige ambiente nao produtivo descartavel.
- Dossier externo deve ser revisto manualmente antes de envio.
- Scheduler nao tem tarefas agendadas atuais; aceite para demo, mas deve ser revisto antes de piloto operacional com rotinas periodicas.
- `queue:work --stop-when-empty` nao encontrou jobs pendentes; rehearsal operacional deve ser repetido com jobs reais antes de producao plena.

## 16. Decisao final

READY_FOR_MUNICIPAL_DEMO_WITH_ACCEPTED_SCOPE.
