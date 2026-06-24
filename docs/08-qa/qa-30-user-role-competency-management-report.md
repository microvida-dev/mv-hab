# QA-30 — Gestão de Utilizadores, Perfis, Equipas e Competências

## 1. Sumário executivo

QA-30 evolui a base RBAC da MV HAB para um módulo administrativo municipal com gestão de utilizadores, roles, equipas, MFA imposto, histórico de alterações de acesso e auditoria imutável.

Resultado: **PASS_WITH_ACCEPTED_RISKS**.

Motivo da decisão: os controlos administrativos críticos foram implementados e testados, com PHPUnit completo, PHPStan, Pint, build, route list e diff check a passar. Mantém-se um risco aceite: os novos perfis institucionais foram adicionados à matriz RBAC, mas não foram adicionados ao middleware global legado de backoffice para evitar abertura indiscriminada de rotas antigas sem revisão granular rota-a-rota.

## 2. Estado inicial do RBAC

Ficheiros analisados:

| Área | Ficheiros |
| --- | --- |
| Configuração RBAC | `config/mvhab.php` |
| Utilizador e roles | `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php` |
| Seeder de acesso | `database/seeders/SystemAccessSeeder.php` |
| Middleware | `EnsureUserHasRole`, `BlockInactiveBackofficeUsers`, `EnsureBackofficeMfaVerified`, `RequireSensitivePermission` |
| Auditoria | `app/Services/Audit/AuditTrailService.php`, `app/Models/AuditEvent.php` |
| MFA | `app/Services/Security/MfaEnforcementService.php`, `MfaController` |
| Policies existentes | `UserPolicy`, `PermissionReviewPolicy`, `AccessLogPolicy` |
| Testes existentes | `tests/Feature/Security/PermissionMatrixTest.php`, `tests/Feature/FoundationAccessControlTest.php` |
| Rotas | `routes/web.php` |

Base confirmada:

| Item | Resultado |
| --- | --- |
| Branch | `qa/qa-30-user-role-competency-management` |
| Último commit inicial | `f149975 chore: ignore phpstan cache` |
| Tag base | `v0.9.0-staging-readiness` é ancestral do HEAD |
| Rotas após QA-30 | 1105 rotas |

## 3. Roles existentes preservadas

Roles preservadas:

- `administrator`
- `municipal_technician`
- `jury`
- `financial_manager`
- `maintenance_manager`
- `candidate`
- `auditor`

Não foram removidas roles existentes nem enfraquecidas permissões existentes. O seeder continua idempotente.

## 4. Novos perfis adicionados

Perfis adicionados em `config/mvhab.php`:

| Perfil | Competências incluídas | Guardrails |
| --- | --- | --- |
| `legal_manager` | contratos, reclamações, listas, consulta documental autorizada, auditoria jurídica | sem `scoring.update`, sem pagamentos, sem gestão de roles |
| `housing_manager` | habitações, atribuições, ocupação, contratos operacionais, visitas | sem `roles.assign`, sem scoring |
| `inspection_manager` | vistorias, evidências técnicas, manutenção relacionada | sem contratos jurídicos, pagamentos ou gestão de utilizadores |
| `support_agent` | atendimento, suporte, FAQ contextual, comunicações operacionais | sem documentos sensíveis, scoring, listas, contratos ou pagamentos |

## 5. Equipas municipais

Foram criadas:

- migration reversível `2026_06_24_000030_create_user_role_competency_management_tables.php`;
- model `MunicipalTeam`;
- pivot `municipal_team_user`;
- seeder idempotente `MunicipalTeamSeeder`.

Equipas base:

| Equipa | Estado | Escopo |
| --- | --- | --- |
| Gabinete Técnico | ativa | candidaturas, documentos, elegibilidade |
| Gabinete Jurídico | ativa | contratos, reclamações, audiência |
| Gabinete Financeiro | ativa | financeiro, cobranças, relatórios |
| Gabinete de Habitação | ativa | habitações, atribuições, contratos |
| Manutenção | ativa | manutenção |
| Vistorias | ativa | vistorias |
| Atendimento | ativa | suporte e experiência do cidadão |
| Auditoria | ativa | auditoria, RGPD, controlo de acessos |

Equipas inativas não aceitam novos membros.

## 6. Matriz de permissões

Novos módulos:

| Módulo | Permissões |
| --- | --- |
| `users` | `view`, `create`, `update`, `delete`, `deactivate`, `reactivate`, `force_mfa`, `reset_password`, `audit` |
| `roles` | `view`, `assign`, `remove`, `audit` |
| `teams` | `view`, `create`, `update`, `manage_members`, `audit` |
| `access_audit` | `view`, `export`, `audit` |

`administrator` mantém `*`. `auditor` pode consultar por `*.view`/`*.audit`, mas não altera utilizadores, roles ou equipas.

## 7. Policies/Gates criadas ou reforçadas

Criadas:

- `UserAdministrationPolicy`
- `RoleAssignmentPolicy`
- `TeamManagementPolicy`
- `AccessAuditPolicy`

Reforçada:

- `UserPolicy` com `deactivate`, `reactivate`, `forceMfa`, `resetPassword`.

## 8. Auditoria implementada

Foi criada a tabela `access_change_events` com bloqueio de update/delete no model.

Eventos cobertos:

- `user_created`
- `user_updated`
- `user_deactivated`
- `user_reactivated`
- `user_password_reset_requested`
- `user_mfa_enforced`
- `role_assigned`
- `role_removed`
- `team_created`
- `team_updated`
- `team_member_added`
- `team_member_removed`

Cada evento regista actor, alvo, role/equipa quando aplicável, justificação, origem request quando disponível e valores antigos/novos minimizados.

## 9. Segurança contra role escalation

Implementado:

- bloqueio de self-promotion;
- bloqueio de atribuição de `administrator` por actor não administrator;
- bloqueio de role acima do escopo efetivo do actor;
- bloqueio de remoção da última role operacional;
- bloqueio de remoção/desativação do último administrator ativo;
- bloqueio de auto-desativação crítica de administrator;
- auditor não altera utilizadores;
- support agent não acede à gestão de roles.

## 10. MFA

`MfaEnforcementService` passou a considerar:

- roles sensíveis existentes;
- novos perfis sensíveis `legal_manager`, `housing_manager`, `inspection_manager`;
- flag administrativa `mfa_required`.

`support_agent` não recebe MFA obrigatório por perfil, mas pode ficar com MFA imposto por administração.

## 11. UX backoffice

Criadas páginas Blade no padrão existente:

- `backoffice/users`;
- `backoffice/users/create`;
- `backoffice/users/{user}`;
- `backoffice/users/{user}/edit`;
- `backoffice/roles`;
- `backoffice/teams`;
- `backoffice/teams/create`;
- `backoffice/teams/{municipalTeam}`;
- `backoffice/teams/{municipalTeam}/edit`;
- `backoffice/access-audit`.

Inclui filtros por role, equipa, estado e pesquisa, badge MFA/estado, ações rápidas e histórico.

## 12. Testes executados

| Comando | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA30` | PASS, 3 testes, 32 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UserAdministration` | PASS, 6 testes, 10 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter RoleEscalation` | PASS, 4 testes, 5 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter TeamManagement` | PASS, 3 testes, 18 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security` | PASS, 21 testes, 144 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter MfaEnforcement` | PASS, 3 testes, 14 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Permission` | PASS, 15 testes, 87 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS, 312 testes, 1971 asserções |
| `php artisan route:list --except-vendor` | PASS, 1105 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| Verificação ampla de padrões sensíveis | executada; apenas falsos positivos técnicos/históricos, sem valores reais novos nos artefactos QA-30 |

Evidências em `storage/qa/qa-30-*.txt`.

## 13. PHPStan/Pint/build

- PHPStan: 0 erros.
- Pint: passou.
- Build Vite: passou.
- `git diff --check`: passou.

## 14. Riscos residuais

| Risco | Estado | Mitigação |
| --- | --- | --- |
| Novos perfis não foram adicionados ao middleware global legado de backoffice | aceite | evita abertura ampla de rotas antigas; ativação granular deve ser sprint própria ou hardening por rota |
| Reset seguro de credenciais não invalida sessões existentes | aceite | fluxo usa broker Laravel; invalidação de sessões fica como melhoria se a estratégia de sessão produtiva o suportar |
| Gestão de permissões finas por equipa ainda é escopo funcional, não enforcement automático | aceite | equipas têm escopos auditáveis; permissões efetivas continuam via RBAC |
| Alteração de email não está disponível no módulo | aceite | bloqueio intencional por exigir política própria |

## 15. Critérios de aceitação

| Critério | Estado |
| --- | --- |
| Utilizadores geríveis por backoffice autorizado | PASS |
| Roles atribuíveis/removíveis com segurança | PASS |
| Equipas municipais geríveis | PASS |
| Novos perfis existem e estão protegidos | PASS |
| MFA pode ser imposto | PASS |
| Role escalation bloqueado | PASS |
| Último administrator protegido | PASS |
| Histórico preservado | PASS |
| Auditoria completa | PASS |
| PHPStan 0 erros | PASS |
| PHPUnit passa | PASS |
| Pint passa | PASS |
| Build passa | PASS |
| route:list passa | PASS |
| git diff --check passa | PASS |

## 16. Decisão final

**PASS_WITH_ACCEPTED_RISKS**

QA-30 está concluída para evolução segura do backoffice administrativo sobre a base READY_FOR_STAGING, sem declarar produção final e sem alterar fluxos críticos das QA-21 a QA-29.
