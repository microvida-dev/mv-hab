# Phase 2 Controlled Municipal Staging Readiness Report

## 1. Sumario executivo

A Fase 2 fechou readiness operacional para staging municipal controlado, cobrindo disaster recovery em modo seco, acessibilidade pragmatica, parametrizacao legal Alcanena e matriz RBAC/equipas.

Nao foram implementadas funcionalidades de negocio novas nem integracoes externas.

## 2. Estado inicial

Base validada a partir da Fase 1:

- branch `qa/phase-2-controlled-municipal-staging-readiness`;
- ultimo commit base `980bad7 test: validar hardening municipal da fase 1`;
- QA-36 e Fase 1 presentes na historia local.

Documento ausente registado:

- `docs/08-qa/deep-research-report-v2.md`.

## 3. Exclusoes municipais

Continuam `Out of scope by municipal decision`:

- CMD;
- Autenticacao.gov;
- assinatura digital;
- pagamentos via plataforma;
- MB WAY;
- Multibanco;
- cartao;
- gateway de pagamentos;
- reconciliacao bancaria automatica;
- importacao SEPA automatica.

## 4. QA-41 Backup/Restore/Rollback

Criados comando, service, runbook e checklists para ensaio seco:

- `mvhab:operations:dr-rehearsal --dry-run`;
- `DisasterRecoveryChecklistService`;
- checklists de restore/rollback em staging.

Restore/rollback real nao foi executado neste ambiente.

## 5. QA-42 WCAG

Reforcos:

- skip link;
- main landmark estavel;
- fallback textual de mapa;
- validacao de labels e instrucoes de upload documental.

## 6. QA-43 Alcanena

Validado:

- elegibilidade Artigo 8;
- impedimentos Artigo 9;
- documentacao Artigo 12;
- aperfeicoamento Artigo 14;
- listas/audiencia/reclamacoes Artigo 17;
- scoring/desempates;
- taxa de esforco 35%.

## 7. QA-44 RBAC/equipas

Validado:

- roles existentes preservadas;
- equipas municipais;
- auditor read-only;
- support/maintenance/financial sem acessos sensiveis indevidos;
- candidato/inquilino funcional sem backoffice;
- Work Tasks por equipa.

## 8. Ficheiros alterados

- `app/Console/Commands/DisasterRecoveryRehearsalCommand.php`
- `app/Services/Operations/DisasterRecoveryChecklistService.php`
- `resources/views/components/public-layout.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `scripts/check-release-artifact-safety.php`
- testes QA-41 a QA-44;
- documentacao operacional em `docs/11-operacoes`;
- relatorios QA em `docs/08-qa`.

## 9. Quality gate

Resultados finais registados em `storage/qa/phase-2-*.txt`.

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `phpunit` completo | PASS: 436 testes, 2818 assertions |
| `phpunit --filter QA41` | PASS |
| `phpunit --filter QA42` | PASS |
| `phpunit --filter QA43` | PASS |
| `phpunit --filter QA44` | PASS |
| `phpunit --filter Security` | PASS |
| `phpunit --filter Operations` | PASS |
| `phpunit --filter Regulatory` | PASS |
| `phpunit --filter Accessibility` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `phpstan analyse --memory-limit=1G -v` | PASS: 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| `php artisan schedule:list` | PASS |
| `php artisan queue:work --stop-when-empty` | PASS |
| `php artisan mvhab:operations:queue-health` | PASS |
| `php artisan mvhab:operations:dr-rehearsal --dry-run` | PASS |
| `php scripts/check-secrets.php` | PASS |
| `php scripts/check-release-artifact-safety.php` | PASS |

## 10. Riscos residuais

- Restore/rollback real nao ensaiado em ambiente descartavel.
- Validacao WCAG com leitor de ecra/browser real recomendada antes de producao plena.
- Parametrizacao Alcanena ainda depende de edital final.
- Revisao de acessos reais deve ocorrer antes de usar dados reais.
- O gate de seguranca textual nao encontrou artefactos bloqueantes nos alvos versionaveis da Fase 2.
- O grep de seguranca global encontrou ocorrencias historicas ja existentes; o grep limitado aos ficheiros alterados mostrou apenas mencoes documentais/controladas a exclusoes municipais, termos legais, route-list e testes negativos, sem segredos reais.

## 11. Decisao final

`READY_FOR_STAGING_NOT_PRODUCTION`

Justificacao: Fase 2 valida staging controlado, mas sem restore/rollback real em ambiente nao produtivo descartavel a decisao nao deve subir para readiness municipal plena.
