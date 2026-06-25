# QA-36 — Produção Municipal Sem Integrações Externas

## 1. Sumário executivo

A QA-36 prepara a MV HAB para entrega municipal dentro do âmbito aceite, sem implementar novas funcionalidades de negócio e sem introduzir integrações externas.

Decisão final: **READY_FOR_STAGING_NOT_PRODUCTION**.

Justificação: todos os quality gates locais passaram, mas restore e rollback reais não foram ensaiados num ambiente não produtivo descartável. Pela regra da sprint, sem esse rehearsal a decisão máxima permanece staging, mesmo com integrações externas formalmente fora de âmbito.

## 2. Decisão municipal de exclusão de integrações externas

As integrações de assinatura digital, Autenticação.gov/CMD, MB WAY, Multibanco, cartão, gateway de pagamentos, reconciliação bancária automática e importação SEPA automática ficam documentadas como **Out of scope by municipal decision**.

Referência operacional: `docs/11-operacoes/out-of-scope-integrations.md`.

## 3. Âmbito final aceite

Incluído:

- concursos;
- candidaturas;
- documentos;
- elegibilidade;
- scoring;
- listas;
- audiência/reclamações;
- contratos;
- rendas com gestão administrativa/manual;
- área do inquilino;
- manutenção;
- vistorias;
- portal público;
- visitas;
- tickets;
- FAQ;
- auditoria;
- RGPD;
- IA documental assistiva sem decisão automática;
- roles, equipas, Work Tasks e SLA.

## 4. Funcionalidades incluídas

As QA-30 a QA-35 estão integradas na base da branch e cobrem RBAC municipal, Work Tasks, segurança/RGPD, IA documental assistiva, portal público avançado e atendimento/visitas/tickets/FAQ.

## 5. Funcionalidades fora de âmbito

| Funcionalidade | Estado |
| --- | --- |
| Assinatura digital | Out of scope by municipal decision |
| Autenticação.gov/CMD | Out of scope by municipal decision |
| MB WAY/Multibanco/cartão | Out of scope by municipal decision |
| Gateway de pagamentos | Out of scope by municipal decision |
| Reconciliação bancária automática | Out of scope by municipal decision |
| Importação SEPA automática | Out of scope by municipal decision |

Alternativa aceite: gestão administrativa/manual com auditoria, comprovativos internos e Work Tasks.

## 6. Estado da configuração de produção

Documentado em `docs/11-operacoes/production-environment-checklist.md`.

Pontos chave:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_TIMEZONE=Europe/Lisbon`;
- `.env` fora do Git;
- storage privado fora de `public/`;
- `QUEUE_CONNECTION=database|redis`;
- `SESSION_DRIVER=database|redis`;
- `LOG_CHANNEL=daily`.

Inventário local:

| Item | Resultado |
| --- | --- |
| Branch | `qa/qa-36-municipal-production-no-external-integrations` |
| Commit base | `c9d6750 feat: implement QA-35 visits and candidate support` |
| PHP | 8.4.21 |
| Laravel | 13.12.0 |
| Node | 24.11.0 |
| npm | 11.6.1 |
| Timezone local após ajuste | `Europe/Lisbon` |
| Rotas | 1119 |

Evidência: `storage/qa/qa-36-release-inventory.txt`.

## 7. Scheduler/queues/workers

Documentado em `docs/11-operacoes/scheduler-queues-workers-runbook.md`.

Validações previstas:

- `php artisan schedule:list`;
- `php artisan queue:work --stop-when-empty`;
- `php artisan queue:restart`;
- triagem de failed jobs.

Resultado local:

| Comando | Resultado |
| --- | --- |
| `php artisan schedule:list` | PASS; sem tarefas agendadas registadas |
| `php artisan queue:work --stop-when-empty` | PASS; sem jobs pendentes, output vazio |

Nota: a ausência de tarefas no scheduler não bloqueia staging, mas deve ser revista antes de produção se SLA/overdue ou rotinas periódicas forem obrigatórias no ambiente municipal.

## 8. Backups

Documentado em `docs/11-operacoes/backup-restore-runbook.md`.

Inclui base de dados, storage privado, `.env` fora do Git, artefactos de release, checksums, retenção e encriptação.

## 9. Restore

Restore real não foi executado nesta máquina por ausência de ambiente não produtivo descartável. A limitação fica aceite e a decisão máxima permanece `READY_FOR_STAGING_NOT_PRODUCTION`.

Evidência: `storage/qa/qa-36-restore-test.txt`.

## 10. Rollback

Documentado em `docs/11-operacoes/rollback-runbook.md`.

Inclui rollback de código, build, DB apenas quando necessário, storage privado, `queue:restart` e smoke pós-rollback.

Rollback real não foi executado nesta máquina. Evidência: `storage/qa/qa-36-rollback-test.txt`.

## 11. Smoke tests municipais

Checklist criada em `docs/11-operacoes/municipal-smoke-test-checklist.md`.

Cobertura: portal público, candidato, backoffice, inquilino, documentos privados, visitas, tickets, FAQ, auditoria e RGPD.

## 12. Segurança/RGPD

Checklist criada em `docs/11-operacoes/security-rgpd-operational-checklist.md`.

Guardrails preservados:

- documentos privados por defeito;
- downloads via controller/policy;
- MFA sensível;
- auditoria crítica;
- exportações protegidas;
- `.env` e segredos fora do Git.

Verificação pré-commit focada nos ficheiros QA-36 encontrou apenas placeholders/termos de checklist (`APP_KEY`, `DB_PASSWORD`), exclusões documentadas (`cartao`) e nomes de rotas como `rendimentos`/`reset-password`. Não foram identificados valores reais de segredos, chaves privadas, tokens, dumps, documentos reais ou dados pessoais reais novos.

## 13. Logs e permissões

Produção deve usar `LOG_CHANNEL=daily`, permissões de escrita controladas em `storage` e `bootstrap/cache`, e acesso operacional restrito.

## 14. Documentação operacional criada

- `docs/11-operacoes/deploy-runbook.md`
- `docs/11-operacoes/rollback-runbook.md`
- `docs/11-operacoes/backup-restore-runbook.md`
- `docs/11-operacoes/municipal-admin-guide.md`
- `docs/11-operacoes/out-of-scope-integrations.md`
- `docs/11-operacoes/production-environment-checklist.md`
- `docs/11-operacoes/scheduler-queues-workers-runbook.md`
- `docs/11-operacoes/municipal-smoke-test-checklist.md`
- `docs/11-operacoes/security-rgpd-operational-checklist.md`

## 15. Quality gate

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS, 386 testes, 2446 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA36` | PASS, 2 testes, 22 asserções |
| `php artisan route:list --except-vendor` | PASS, 1119 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| `php artisan schedule:list` | PASS, sem tarefas agendadas registadas |
| `php artisan queue:work --stop-when-empty` | PASS, sem output por ausência de jobs pendentes |

## 16. Evidências

Evidências locais esperadas:

- `storage/qa/qa-36-composer.txt`
- `storage/qa/qa-36-optimize-clear.txt`
- `storage/qa/qa-36-pint.txt`
- `storage/qa/qa-36-phpunit.txt`
- `storage/qa/qa-36-qa36-tests.txt`
- `storage/qa/qa-36-phpstan.txt`
- `storage/qa/qa-36-route-list.txt`
- `storage/qa/qa-36-build.txt`
- `storage/qa/qa-36-diff-check.txt`
- `storage/qa/qa-36-schedule-list.txt`
- `storage/qa/qa-36-queue-worker.txt`
- `storage/qa/qa-36-backup-test.txt`
- `storage/qa/qa-36-restore-test.txt`
- `storage/qa/qa-36-rollback-test.txt`
- `storage/qa/qa-36-smoke-tests.txt`
- `storage/qa/qa-36-release-inventory.txt`

## 17. Riscos residuais

| Risco | Estado |
| --- | --- |
| Restore real ainda exige ambiente não produtivo descartável | Aceite para staging; blocker para produção sem ensaio |
| Rollback real ainda exige rehearsal no ambiente alvo | Aceite para staging; blocker para produção sem ensaio |
| `schedule:list` não apresenta tarefas agendadas | Aceite para staging; rever antes de produção se rotinas periódicas forem obrigatórias |
| Integracoes externas excluidas | Aceite por decisão municipal |
| Configuração final do servidor municipal não verificável localmente | Validar no deploy |

## 18. Decisão final

**READY_FOR_STAGING_NOT_PRODUCTION**

Todos os gates locais passaram e as exclusões municipais estão formalizadas. A decisão não sobe para **READY_FOR_MUNICIPAL_DEPLOYMENT_WITH_ACCEPTED_SCOPE** porque restore/rollback reais não foram ensaiados num ambiente não produtivo descartável e a configuração final do servidor municipal ainda não foi validada.
