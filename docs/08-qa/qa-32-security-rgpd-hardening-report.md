# QA-32 — Security & RGPD Hardening

## 1. Sumário executivo

A QA-32 reforçou a segurança transversal da MV HAB sobre a base QA-30/QA-31, sem alterar regras de elegibilidade, scoring, ranking, listas, contratos, rendas ou workflows de decisão.

Decisão final: **PASS**.

Notas relevantes:

- A branch QA-32 não continha inicialmente a base QA-30/QA-31 apesar de estar apontada para `main`; foi integrado `qa/qa-31-work-task-competency-workflow` por merge antes da implementação.
- O commit base de `main` continha views compiladas em `storage/framework/views`; esses artefactos foram removidos do versionamento durante a resolução do merge e ficou apenas `.gitkeep`.
- `php artisan optimize` passou, mas deixa cache local que interfere com PHPUnit se não for limpa. A execução válida de testes foi feita após `php artisan optimize:clear`, e esta limitação ficou documentada como risco operacional.

## 2. Ficheiros analisados

| Área | Ficheiros principais |
| --- | --- |
| Guardrails | `AGENTS.md`, `docs/README.md`, `docs/09-seguranca-rgpd/security-rgpd-guardrails.md`, `docs/08-qa/enterprise-quality-gate.md`, `docs/02-arquitetura/domain-boundaries.md` |
| QA base | `docs/08-qa/qa-30-user-role-competency-management-report.md`, `docs/08-qa/qa-31-work-task-competency-workflow-report.md` |
| Auditoria | `AuditTrailService`, `AuditEventFormatter`, `AuditEvent`, `AccessLog`, `SensitiveDataAccessLog`, `DocumentAccessLog` |
| Documentos | `DocumentAccessService`, `Candidate\DocumentController`, `Admin\DocumentReviewController`, `DocumentSubmissionPolicy` |
| MFA e sessões | `MfaEnforcementService`, `MfaDeviceService`, `LoginRequest`, `AuthenticatedSessionController`, `UserAdministrationService` |
| RGPD/exportações | `DataExportService`, `AnonymizationService`, `RetentionExecutionService`, `DataExportPackagePolicy` |
| Work Tasks | `WorkTaskCreationService`, `WorkTaskSlaService`, `WorkTaskPolicy`, `WorkTaskAuthorizationTest` |
| RBAC | `config/mvhab.php`, `SystemAccessSeeder`, roles/permissões QA-30 |

## 3. Hardening implementado

### Auditoria documental

- Visualização documental passa a criar:
  - `document_access_logs`;
  - `access_logs` com `document_view`;
  - `sensitive_data_access_logs`;
  - `audit_events` com `document_viewed`.
- Download documental mantém `document.downloaded` e passa também a emitir `document_downloaded`.
- Acesso negado a documento passa a emitir `document_access_denied`.
- Controllers continuam finos: autorização/negação e delegação para `DocumentAccessService`.

### Imutabilidade de logs

Foram tornados append-only por model events:

- `AccessLog`;
- `SensitiveDataAccessLog`;
- `DocumentAccessLog`.

### Login e sessões

Criado:

- `LoginHistoryService`;
- `SessionRevocationService`.

Fluxos reforçados:

- login bem-sucedido cria `login_success`;
- falha de login cria `login_failed` sem password/token e sem fallback para `Auth::id()`;
- logout cria `logout`;
- desativação de utilizador revoga sessões persistidas e audita `all_sessions_revoked`, `session_revoked` e `user_deactivated_session_revoked`.

### MFA

- Confirmação MFA passa a emitir `mfa_enabled`.
- Desativação MFA passa a emitir `mfa_disabled`.
- Imposição administrativa mantém `user_mfa_enforced` e passa também a emitir `mfa_enforced`.

### Exportações sensíveis

Exportações RGPD mantêm storage privado e passam a auditar:

- `sensitive_export_created`;
- `rgpd_export_requested`;
- `sensitive_export_downloaded`.

Os paths técnicos continuam sem nome/email do titular.

### RGPD, retenção e DPO

Criado:

- migration `2026_06_25_000032_create_rgpd_approvals_table.php`;
- `RgpdApproval`;
- `DpoApprovalService`;
- `DpoApprovalPolicy`;
- factory de teste.

Eventos reforçados:

- `rgpd_anonymization_requested`;
- `rgpd_anonymization_approved`;
- `rgpd_anonymization_executed`;
- `rgpd_retention_simulated`;
- `rgpd_retention_approved`;
- `rgpd_retention_executed`;
- `dpo_approval_approved`;
- `dpo_approval_executed`.

### Work Tasks

- Metadata de tarefas remove agora também email, telefone, morada e rendimento, além de password/token/secret/NIF/storage paths.
- `WorkTaskSlaService` ganhou auditoria dirigida para `work_task_sla_changed`.

## 4. Permissões

Foram adicionadas permissões ao catálogo:

| Módulo | Permissões |
| --- | --- |
| `security` | `view_access_logs`, `revoke_sessions`, `audit_sensitive_access` |
| `exports` | `view`, `create`, `download`, `audit`, `delete`, `rgpd`, `sensitive.create`, `sensitive.download`, `sensitive.audit` |
| `rgpd` | `retention.view`, `retention.manage`, `anonymization.request`, `anonymization.approve`, `anonymization.execute`, `dpo.approve` |

`administrator` mantém `*`. `auditor` recebeu permissões explícitas de consulta/auditoria para logs sensíveis, exports e retenção, sem capacidade mutável.

## 5. Testes criados

- `tests/Feature/QA32SecurityRgpdHardeningTest.php`
- `tests/Feature/Security/SensitiveAccessAuditTest.php`
- `tests/Feature/Security/MfaMandatorySensitiveRolesTest.php`
- `tests/Feature/Security/SessionRevocationTest.php`
- `tests/Feature/Security/LoginHistoryTest.php`
- `tests/Feature/Security/SensitiveExportAuditTest.php`
- `tests/Feature/Security/RetentionPolicyTest.php`
- `tests/Feature/Security/AnonymizationApprovalTest.php`
- `tests/Feature/Security/DpoApprovalWorkflowTest.php`
- `tests/Feature/Security/WorkTaskSensitiveDataLeakTest.php`

Cobertura adicionada:

- visualização/download/negação documental auditados;
- logs sensíveis append-only;
- login success/failed/logout auditados;
- falha de login com sessão obsoleta não grava `user_id` inválido;
- revogação de sessões;
- MFA obrigatório e eventos `mfa_enabled`/`mfa_disabled`;
- exportação RGPD sensível auditada;
- retenção simulada/aprovada/executada auditada;
- anonimização aprovada/executada e irreversível para perfil de teste;
- aprovação DPO;
- minimização de metadata em Work Tasks;
- auditoria de alteração SLA.

## 6. Comandos executados

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA32` | PASS, 2 testes, 18 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security` | PASS, 37 testes, 232 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd` | PASS, 11 testes, 78 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Export` | PASS, 3 testes, 29 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Audit` | PASS, 37 testes, 256 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter WorkTask` | PASS, 20 testes, 61 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS, 343 testes, 2109 asserções |
| `php artisan optimize:clear` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

Evidências locais:

- `storage/qa/qa-32-composer.txt`
- `storage/qa/qa-32-optimize.txt`
- `storage/qa/qa-32-optimize-clear.txt`
- `storage/qa/qa-32-pint.txt`
- `storage/qa/qa-32-qa32-tests.txt`
- `storage/qa/qa-32-security-tests.txt`
- `storage/qa/qa-32-rgpd-tests.txt`
- `storage/qa/qa-32-export-tests.txt`
- `storage/qa/qa-32-audit-tests.txt`
- `storage/qa/qa-32-work-task-security-tests.txt`
- `storage/qa/qa-32-phpunit.txt`
- `storage/qa/qa-32-route-list.txt`
- `storage/qa/qa-32-phpstan.txt`
- `storage/qa/qa-32-build.txt`
- `storage/qa/qa-32-diff-check.txt`

## 7. Bugs/gaps encontrados

| Item | Severidade | Estado |
| --- | --- | --- |
| Visualização documental sem `audit_events`/`access_logs`/`sensitive_data_access_logs` | Alta | Corrigido |
| Acesso documental negado sem evento específico | Alta | Corrigido para controllers documentais principais |
| Logs sensíveis permitiam update/delete por model | Média | Corrigido |
| Desativação de utilizador não revogava sessões persistidas | Alta | Corrigido |
| Login success/logout não tinham `audit_events` explícitos | Média | Corrigido |
| `login_failed` podia gravar `user_id` obsoleto de sessão e violar a FK de `audit_events` | Alta | Corrigido |
| Exportação RGPD não emitia nomenclatura QA-32 de export sensível | Média | Corrigido |
| Fluxo DPO genérico inexistente | Média | Criado modelo/service/policy mínimo |
| Metadata Work Tasks ainda aceitava email/telefone/morada/rendimento | Alta | Corrigido |
| `php artisan optimize` antes de PHPUnit deixa cache local incompatível com ambiente de teste | Operacional | Documentado; mitigação: `php artisan optimize:clear` antes de testes |

## 8. Segurança/RGPD

Validações cumpridas:

- documentos continuam em storage privado;
- downloads continuam por controller/policy;
- visualização e download ficam auditados;
- negações ficam auditadas;
- exportações RGPD ficam em `local`, com paths técnicos;
- eventos auditados mascaram chaves sensíveis via `AuditEventFormatter`;
- logs sensíveis são append-only;
- sessões persistidas podem ser revogadas;
- MFA sensível mantém enforcement e auditoria;
- Work Tasks não copiam dados pessoais desnecessários para metadata.

## 9. Riscos residuais

| Risco | Mitigação |
| --- | --- |
| Alguns módulos antigos de documentos fora dos controllers principais podem precisar de hooks equivalentes de `document_access_denied` | Cobrir incrementalmente quando forem promovidos a fluxos ativos |
| O fluxo DPO criado é programático; não foi criada UI backoffice nesta sprint | Seguro para staging; UI deve ser sprint própria se o Município exigir operação manual completa |
| `php artisan optimize` antes de PHPUnit pode induzir testes contra cache local | Runbook deve limpar cache antes de qualquer suite automatizada local/CI |
| Exportações grandes continuam a depender dos guardrails já existentes de reports/RGPD | Evoluir para queue/chunking quando houver datasets reais volumosos |

## 10. Critérios de aceitação

| Critério | Estado |
| --- | --- |
| Visualizações auditadas | PASS |
| Downloads protegidos | PASS |
| Exportações auditadas | PASS |
| Scoring/ranking rastreáveis | Preservado; sem alteração nesta sprint |
| MFA ativo | PASS |
| Sessões revogáveis | PASS |
| Login history existente | PASS |
| Retenção modelada | PASS |
| Anonimização testada | PASS |
| Aprovação DPO funcional | PASS |
| Tarefas seguras | PASS |
| Nenhum documento público | PASS |
| Nenhum path privado exposto em eventos novos | PASS |
| PHPStan 0 erros | PASS |
| PHPUnit OK | PASS |
| Pint OK | PASS |
| Build OK | PASS |
| route:list OK | PASS |

## 11. Resultado final

**PASS**

A plataforma fica mais endurecida para staging municipal com auditoria sensível, RGPD, MFA, sessões, retenção, aprovação DPO e Work Tasks protegidas. Não foi declarada produção final e não foram alteradas regras de negócio críticas.
