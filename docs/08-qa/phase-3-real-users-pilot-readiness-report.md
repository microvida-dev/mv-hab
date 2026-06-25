# Phase 3 Real Users Pilot Readiness Report

## 1. Sumario executivo

A Fase 3 preparou a MV HAB para piloto municipal controlado com utilizadores reais, reforcando dashboards/KPIs, reports/exports seguros, incident response, observabilidade, suporte e alinhamento RGPD.

Nao foram implementadas integracoes externas nem funcionalidades de negocio fora do ambito aceite.

## 2. Estado inicial

Base local:

- branch `qa/phase-3-real-users-pilot-readiness`;
- topo base `4105be8 test: validar readiness municipal controlada da fase 2`;
- Fase 1 e Fase 2 presentes no historico;
- documento ausente registado: `docs/08-qa/deep-research-report-v2.md`.

## 3. Decisao municipal sobre CMD e pagamentos fora de ambito

Continuam `Out of scope by municipal decision`:

- CMD;
- Autenticacao.gov;
- assinatura digital qualificada;
- pagamentos via plataforma;
- MB WAY;
- Multibanco;
- cartao;
- gateway de pagamentos;
- reconciliacao bancaria automatica;
- importacao SEPA automatica.

Alternativa aceite:

- autenticacao local;
- contratos/assinaturas com gestao administrativa/manual;
- rendas/pagamentos com gestao administrativa/manual;
- comprovativos e registos internos;
- auditoria;
- Work Tasks;
- suporte municipal e tecnico.

## 4. QA-45 Dashboards, KPIs e relatorios municipais

Criado `MunicipalKpiService` para snapshot agregado de:

- concursos;
- candidaturas;
- documentos;
- aperfeicoamento;
- visitas;
- tickets;
- Work Tasks/SLA;
- listas;
- contratos;
- rendas manuais;
- manutencao;
- vistorias;
- RGPD;
- auditoria.

Documentacao criada:

- `docs/11-operacoes/municipal-dashboard-kpi-catalog.md`;
- `docs/11-operacoes/reporting-export-guardrails.md`;
- `docs/08-qa/qa-45-dashboards-kpis-municipal-reports-report.md`.

## 5. QA-45-T Reporting, exports e performance

Validado:

- autorizacao de dashboards;
- candidato/inquilino bloqueados;
- auditor read-only;
- exports privados;
- downloads auditados;
- formula injection mitigada;
- payload de KPI agregado e minimizado.

## 6. QA-46 Incidentes, observabilidade e suporte

Criado:

- `mvhab:operations:health`;
- `OperationalHealthService`;
- `incident-response-runbook.md`;
- `observability-logging-runbook.md`;
- `support-escalation-runbook.md`;
- `municipal-pilot-raci.md`;
- `incident-drill-checklist.md`.

## 7. QA-46-T Incident drill e aceitacao operacional

Drill documental cobre:

- login/backoffice indisponivel;
- upload documental falhado;
- documento privado exposto;
- job falhado;
- IA documental indisponivel;
- candidatura bloqueada;
- visita/ticket SLA vencido;
- export sensivel indevido;
- erro em listas/contratos/rendas;
- rollback.

## 8. QA-47 RGPD final municipal

Criado:

- `rgpd-final-policy-alignment.md`;
- `data-retention-anonymization-policy.md`;
- `data-subject-request-playbook.md`;
- `rgpd-pilot-dpo-validation-checklist.md`;
- `qa-47-rgpd-final-policy-alignment-report.md`.

## 9. QA-47-T RGPD end-to-end

Validado por testes:

- pedido do titular com prazo;
- exportacao RGPD privada/auditada;
- retencao e anonimizacao auditadas;
- access logs sensiveis append-only;
- documentos privados nao publicos;
- IA documental assistiva documentada.

## 10. Ficheiros/documentacao alterada

- `app/Services/Reports/MunicipalKpiService.php`;
- `app/Services/Operations/OperationalHealthService.php`;
- `app/Console/Commands/OperationalHealthCommand.php`;
- testes QA45-QA47 em `tests/Feature`, `tests/Feature/Reports`, `tests/Feature/Operations`, `tests/Feature/Rgpd`, `tests/Unit`;
- documentacao operacional em `docs/11-operacoes`;
- relatorios QA em `docs/08-qa`.

## 11. Testes executados

Resultados finais registados em `storage/qa/phase-3-*.txt`.

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `phpunit` completo | PASS: 458 testes, 2944 assertions |
| `phpunit --filter QA45` | PASS |
| `phpunit --filter QA46` | PASS |
| `phpunit --filter QA47` | PASS |
| `phpunit --filter Reports` | PASS |
| `phpunit --filter Operations` | PASS |
| `phpunit --filter Rgpd` | PASS |
| `phpunit --filter Security` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `phpstan analyse --memory-limit=1G -v` | PASS: 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| `php artisan schedule:list` | PASS |
| `php artisan queue:work --stop-when-empty` | PASS |
| `php artisan mvhab:operations:queue-health` | WARN local: `QUEUE_CONNECTION=sync` apenas aceitavel em local/testes |
| `php artisan mvhab:operations:health` | WARN local: `APP_DEBUG` ativo e `QUEUE_CONNECTION=sync` apenas aceitaveis em local/testes |
| `php scripts/check-secrets.php` | PASS |
| `php scripts/check-release-artifact-safety.php` | PASS |

Grep de seguranca:

- grep global encontrou ocorrencias historicas ja existentes em docs/artefactos antigos e views funcionais;
- grep limitado aos ficheiros alterados nesta Fase 3 encontrou apenas mencoes documentais/controladas a `APP_DEBUG`, `QUEUE_CONNECTION=sync`, `APP_KEY`, `DB_PASSWORD`, `NIF`, `morada`, `rendimento`, `storage_path` em testes negativos, route-list e documentacao RGPD;
- nao foram encontrados segredos reais, chaves privadas, paths pessoais novos, dumps ou documentos reais nos ficheiros alterados.

## 12. Quality gate

Comandos obrigatorios:

- `composer validate --strict`;
- `php artisan optimize:clear`;
- `./vendor/bin/pint --test`;
- `phpunit` completo e filtros QA45/QA46/QA47/Reports/Operations/Rgpd/Security;
- `php artisan route:list --except-vendor`;
- `phpstan analyse --memory-limit=1G -v`;
- `npm run build`;
- `git diff --check`;
- schedule/queue/health/security scripts quando disponiveis.

## 13. Riscos residuais

- validacao juridica/DPO final ainda e responsabilidade municipal;
- drill operacional com equipa real deve ser executado antes de expandir piloto;
- teste de carga com dados volumosos deve ocorrer em staging;
- restore/rollback real continua dependente de ambiente nao produtivo descartavel;
- integracoes externas permanecem fora de ambito por decisao municipal.
- ambiente local reportou `APP_DEBUG` ativo e `QUEUE_CONNECTION=sync`; estes valores estao documentados como aceitaveis apenas em local/testes e devem estar desativado/database|redis no ambiente piloto.

## 14. Decisao final

`READY_FOR_REAL_USERS_CONTROLLED_PILOT`

Justificacao: a Fase 3 reforca reporting, exports seguros, observabilidade, incidentes, suporte e RGPD, mantendo Fase 1/Fase 2 validas e preservando a exclusao formal de CMD, pagamentos digitais, assinatura digital e reconciliacao automatica.
