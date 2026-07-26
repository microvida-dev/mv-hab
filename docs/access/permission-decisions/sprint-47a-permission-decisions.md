# Decisões de permissions — Sprint 47A.1

## Âmbito

Este registo resolve as 38 lacunas semânticas da Sprint 47A.1 identificadas
no inventário inicial:

- 35 em Administração e segurança;
- 2 em Utilizadores e equipas;
- 1 em RGPD.

As restantes lacunas do universo inicial de 134 pertencem às Sprints 47B a
47H. O manifesto imutável da 47A.1 contém 72 rotas; as outras 34 já tinham
uma permission candidata semanticamente adequada e foram migradas sem criar
uma nova decisão de catálogo.

## Regras comuns aprovadas

- Todas as rotas abaixo têm risco `critical` e exigem MFA.
- O inventário inicial não tinha um mapeamento semântico seguro para estas
  ações. A decisão manual separou leitura, mutação, resolução, aprovação,
  execução e gestão do MFA próprio.
- `Nova` significa que foi acrescentada uma permission exata ao catálogo.
  `Reutilizada` significa que a revisão confirmou uma permission exata já
  existente, em vez de degradar a ação para `update`.
- As permissions mutáveis são atribuídas apenas ao template estrutural
  `administrator`.
- As permissions de leitura são atribuídas ao `administrator` e apenas aos
  templates explicitamente indicados na tabela.
- `security.manage_own_mfa` é atribuída aos perfis humanos de backoffice e só
  autoriza o dispositivo do próprio utilizador.
- O template `auditor` permanece read-only. Não recebe `privacy.export`,
  approvals, execução, resolução ou mutações.
- O template `candidate` não recebe acesso a rotas backoffice; os seus fluxos
  próprios continuam separados e protegidos pelo middleware candidate.
- Nenhuma permission é atribuída diretamente a utilizadores.
- Não foi introduzida qualquer `FeatureKey`.

## Evidência de testes

| Código | Teste |
| --- | --- |
| `APR` | `AdministrationAccessPermissionRoutesTest` |
| `AMB` | `AdministrationMunicipalBoundaryTest` |
| `SPR` | `SecurityPermissionRoutesTest` |
| `SMB` | `SecurityMunicipalBoundaryTest` |
| `PPR` | `PrivacyPermissionRoutesTest` |
| `PMB` | `PrivacyMunicipalBoundaryTest` |
| `MFA` | `PermissionSensitiveMfaTest` |

## Matriz de decisão

| # | Rota e ação | Permission final | Origem e razão | Templates que recebem | Label portuguesa | Testes |
| ---: | --- | --- | --- | --- | --- | --- |
| 1 | `backoffice.security.alert-rules.store` · criar regra | `security.update` | Nova; mutação de configuração de segurança não podia usar leitura | administrator | Alterar segurança | SPR, SMB, MFA |
| 2 | `backoffice.security.alert-rules.update` · alterar regra | `security.update` | Nova; não existia ação de gestão segura | administrator | Alterar segurança | SPR, SMB, MFA |
| 3 | `backoffice.security.alerts.index` · listar alertas | `security.view` | Nova; leitura não podia depender de role fixa | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 4 | `backoffice.security.alerts.resolve` · resolver alerta | `security.resolve` | Nova; resolver é transição distinta de atualizar | administrator | Resolver alerta de segurança | SPR, SMB, MFA |
| 5 | `backoffice.security.alerts.review` · rever alerta | `security.update` | Nova; revisão muta dados sem os resolver | administrator | Alterar segurança | SPR, SMB, MFA |
| 6 | `backoffice.security.audit.access-logs.index` · consultar acessos | `security.view_access_logs` | Reutilizada; equivalente exato confirmado | administrator, auditor | Consultar registos de acesso | SPR, SMB, MFA |
| 7 | `backoffice.security.audit.events.index` · listar auditoria | `audit_logs.view` | Reutilizada; equivalente exato confirmado | administrator, legal_manager, auditor | Consultar auditoria | SPR, SMB, MFA |
| 8 | `backoffice.security.audit.events.show` · ver evento | `audit_logs.view` | Reutilizada; equivalente exato confirmado | administrator, legal_manager, auditor | Consultar auditoria | SPR, SMB, MFA |
| 9 | `backoffice.security.audit.sensitive-logs.index` · consultar acessos sensíveis | `security.audit_sensitive_access` | Reutilizada; equivalente exato confirmado | administrator, auditor | Auditar acessos sensíveis | SPR, SMB, MFA |
| 10 | `backoffice.security.backups.index` · listar revisões | `security.view` | Nova; leitura separada da aprovação | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 11 | `backoffice.security.backups.store` · registar revisão | `security.update` | Nova; registo operacional mutável | administrator | Alterar segurança | SPR, SMB, MFA |
| 12 | `backoffice.security.checklist-items.update` · alterar item | `security.update` | Nova; mutação distinta de leitura | administrator | Alterar segurança | SPR, SMB, MFA |
| 13 | `backoffice.security.checklists.approve` · aprovar checklist | `security.approve` | Nova; aprovação não pode usar `update` | administrator | Aprovar controlo de segurança | SPR, SMB, MFA |
| 14 | `backoffice.security.checklists.index` · listar checklists | `security.view` | Nova; leitura permission-first | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 15 | `backoffice.security.checklists.show` · ver checklist | `security.view` | Nova; leitura permission-first | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 16 | `backoffice.security.checklists.store` · criar checklist | `security.update` | Nova; criação reservada à gestão de segurança | administrator | Alterar segurança | SPR, SMB, MFA |
| 17 | `backoffice.security.dashboard` · consultar painel | `security.view` | Nova; leitura transversal sem mutação | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 18 | `backoffice.security.encrypted-fields.index` · consultar campos cifrados | `security.view` | Nova; GET ficou estritamente read-only | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 19 | `backoffice.security.mfa.confirm` · confirmar MFA próprio | `security.manage_own_mfa` | Nova; não permite gerir MFA de terceiros | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 20 | `backoffice.security.mfa.disable` · desativar MFA próprio | `security.manage_own_mfa` | Nova; scope obrigatório ao próprio dispositivo | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 21 | `backoffice.security.mfa.enable` · ativar MFA próprio | `security.manage_own_mfa` | Nova; scope obrigatório ao próprio dispositivo | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 22 | `backoffice.security.mfa.index` · consultar MFA próprio | `security.manage_own_mfa` | Nova; evita acesso administrativo implícito | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 23 | `backoffice.security.mfa.recovery-codes.regenerate` · regenerar códigos | `security.manage_own_mfa` | Nova; operação sensível limitada ao próprio | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 24 | `backoffice.security.mfa.verify` · verificar desafio | `security.manage_own_mfa` | Nova; operação sensível limitada ao próprio | perfis humanos de backoffice | Gerir MFA próprio | SPR, SMB, MFA |
| 25 | `backoffice.security.permission-reviews.complete` · concluir revisão | `permission_reviews.complete` | Nova; conclusão é transição distinta de update | administrator | Concluir revisão de permissões | SPR, SMB, MFA |
| 26 | `backoffice.security.permission-reviews.index` · listar revisões | `permission_reviews.view` | Nova; leitura separada da gestão | administrator, auditor | Consultar revisões de permissões | SPR, SMB, MFA |
| 27 | `backoffice.security.permission-reviews.show` · ver revisão | `permission_reviews.view` | Nova; leitura separada da gestão | administrator, auditor | Consultar revisões de permissões | SPR, SMB, MFA |
| 28 | `backoffice.security.permission-reviews.store` · criar revisão | `permission_reviews.create` | Nova; criação não usa permission genérica | administrator | Criar revisão de permissões | SPR, SMB, MFA |
| 29 | `backoffice.security.privacy.anonymization.run` · executar anonimização | `rgpd.anonymization.execute` | Reutilizada; execução exata já existia | administrator | Executar anonimização | PPR, PMB, MFA |
| 30 | `backoffice.security.privacy.exports.download` · descarregar exportação | `privacy.export` | Reutilizada; exportação exata já existia | administrator no backoffice | Exportar dados RGPD | PPR, PMB, MFA |
| 31 | `backoffice.security.privacy.requests.assign` · atribuir pedido | `privacy.assign` | Nova; atribuição não pode usar update | administrator | Atribuir pedido RGPD | PPR, PMB, MFA |
| 32 | `backoffice.security.privacy.requests.complete` · aprovar/concluir pedido | `privacy.approve` | Reutilizada; conclusão administrativa equivale à aprovação existente | administrator | Aprovar pedido RGPD | PPR, PMB, MFA |
| 33 | `backoffice.security.privacy.requests.exports.store` · gerar exportação | `privacy.export` | Reutilizada; criação de pacote é parte da exportação autorizada | administrator no backoffice | Exportar dados RGPD | PPR, PMB, MFA |
| 34 | `backoffice.security.privacy.retention-executions.run` · executar retenção | `rgpd.retention.execute` | Nova; execução não pode usar manage/update | administrator | Executar política de retenção | PPR, PMB, MFA |
| 35 | `backoffice.security.storage.index` · consultar storage | `security.view` | Nova; diagnóstico passou a leitura explícita | administrator, auditor | Consultar segurança | SPR, SMB, MFA |
| 36 | `backoffice.cases.rgpd.show` · consultar caso RGPD | `privacy.view` | Reutilizada; leitura exata com scope municipal | administrator, legal_manager, auditor no backoffice | Consultar RGPD | PPR, PMB, MFA |
| 37 | `backoffice.users.deactivate` · desativar utilizador | `users.deactivate` | Reutilizada; transição exata já existia | administrator | Desativar utilizador | APR, AMB, MFA |
| 38 | `backoffice.users.reactivate` · reativar utilizador | `users.reactivate` | Reutilizada; transição exata já existia | administrator | Reativar utilizador | APR, AMB, MFA |

## Decisão

As 38 lacunas foram resolvidas sem wildcard adicional, sem permission direta
por utilizador e sem ampliar templates mutáveis. As decisões foram
implementadas no catálogo, no `SystemAccessSeeder`, nas Policies, nos Form
Requests, no catálogo de sensibilidade MFA e nos testes indicados.

