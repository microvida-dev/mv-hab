# Sprint 45C — Gestão de perfis municipais personalizados

## 1. Resumo executivo

A Sprint 45C implementou a gestão integral de perfis de acesso municipais personalizados sobre o modelo RBAC existente da MV HAB. A solução distingue perfis de sistema de perfis municipais, protege os perfis estruturais, permite gerir o ciclo de vida de perfis personalizados, apresenta as 277 permissões existentes numa matriz legível em português e aplica MFA com base nas permissões sensíveis efetivamente concedidas por perfis ativos.

A intervenção preserva o princípio definido para o projeto:

- toda a autorização continua a resultar de roles;
- não existe `permission_user` nem qualquer concessão direta por utilizador;
- perfis inativos deixam imediatamente de conceder permissões;
- perfis de sistema são apenas de leitura;
- candidatos permanecem fora do backoffice, mesmo perante atribuições anómalas;
- auditores podem consultar quando autorizados, mas não podem mutar perfis;
- operações críticas exigem justificação e produzem auditoria minimizada;
- as rotas necessárias aos três perfis iniciais foram migradas de middleware `role:` para autorização por permissão, sem iniciar uma migração transversal das rotas legadas.

Branch: `sprint-45c-custom-role-management`

Commit-base: `3135d6f21de7e80f47d41a7eb6f2c24f6b0646e7`

Data de fecho técnico: 23 de julho de 2026

## 2. Commits criados

- `fd8ca341 feat(access): proteger perfis de sistema e municipais`
- `540ca2b7 feat(access): gerir perfis municipais personalizados`
- `9aebfc09 feat(access): adicionar matriz de permissões agrupada`
- `9f0266ce feat(access): adicionar modelos de perfis municipais`
- `8546ad48 feat(security): impor MFA por permissões sensíveis ativas`
- `d476de63 security: bloquear candidatos no backoffice municipal`
- `a21fdc10 test(audit): cobrir ciclo de vida dos perfis municipais`
- `5e696dbd fix(access): permitir perfis personalizados nos fluxos de candidaturas`
- `f693e200 test(access): alinhar regressões com MFA e permissões do painel`

Antes deste relatório, a sprint alterava 61 ficheiros, com 3 941 adições e 209 remoções.

## 3. Arquitetura implementada

### 3.1. Classificação de perfis

O modelo `Role` passou a expor explicitamente:

- `isSystem()`;
- `isActive()`;
- `isMunicipalCustom()`;
- scope Eloquent `active()`.

Um perfil municipal personalizado é definido por:

```text
is_system = false
scope = municipal
```

Os perfis de sistema são reconhecidos pelo `SystemRoleDefinitionRegistry`, extraído para runtime e reutilizado pelo `SystemAccessSeeder`. O runtime deixou, assim, de depender de arrays internos de uma classe Seeder.

### 3.2. Permissões efetivas

`User::hasRole()` e `User::hasPermission()` filtram sempre `roles.is_active = true`. Mantêm-se os wildcards já suportados:

- `*`;
- `<modulo>.*`;
- `*.<acao>`.

Não existe cache persistente de permissões por utilizador no desenho atual. Cada verificação consulta roles ativas e as respetivas permissões, pelo que:

- desativar um perfil retira o acesso imediatamente;
- reativar restaura o acesso imediatamente;
- remover uma permissão produz 403 na chamada seguinte;
- um segundo perfil ativo continua a conceder uma permissão mesmo que outro perfil seja desativado.

### 3.3. Proteção em profundidade

A proteção das operações não depende da interface. É aplicada em várias camadas:

- middleware de rota;
- Form Requests;
- `RolePolicy` e `RoleAssignmentPolicy`;
- `RoleManagementService` e `RoleAssignmentService`;
- transações e `lockForUpdate()` nas mutações críticas;
- testes HTTP e testes diretos dos services.

Perfis de sistema não podem ser:

- alterados;
- desativados;
- eliminados;
- convertidos em perfis personalizados;
- ter scope, identificador ou permissões estruturais manipulados.

Podem ser consultados e duplicados. A duplicação cria um perfil municipal independente, ativo e sem utilizadores associados.

## 4. Base de dados e migration

Foi criada uma única migration reversível:

- `database/migrations/2026_07_23_000034_add_management_fields_to_roles_table.php`

Campos adicionados a `roles`:

- `description`: `text`, nullable;
- `is_active`: `boolean`, obrigatório, default `true`, indexado.

Compatibilidade:

- todos os perfis existentes ficam ativos por defeito;
- `is_system`, IDs, nomes técnicos e associações existentes não são alterados;
- a migration não elimina nem transforma dados existentes.

Reversibilidade validada numa base SQLite temporária isolada:

```bash
php artisan migrate:fresh --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
```

O rollback removeu `2026_07_23_000034_add_management_fields_to_roles_table` e a reaplicação concluiu com sucesso. A migration encontra-se aplicada na base local.

## 5. Modelos

### `App\Models\Role`

- novos atributos `description` e `is_active`;
- casts booleanos;
- scope `active()`;
- helpers de classificação e estado;
- relações existentes com utilizadores e permissões preservadas.

### `App\Models\User`

- `hasRole()` ignora perfis inativos;
- `hasPermission()` calcula permissões apenas através de perfis ativos;
- não foi adicionada relação direta User-Permission.

Confirmação estrutural:

- a tabela `permission_user` não existe;
- não foi criada migration para permissões diretas;
- o único pivot de permissões continua a ser `permission_role`.

## 6. Services

### `RoleManagementService`

Responsável por:

- criar perfis municipais;
- alterar label e descrição;
- sincronizar permissões;
- duplicar perfis de sistema ou municipais;
- ativar e desativar perfis municipais;
- eliminar perfis municipais sem utilizadores;
- impedir escalada acima das permissões do operador;
- gerar identificadores técnicos únicos e normalizados;
- auditar cada mutação.

As mutações usam transação. Atualização, duplicação, toggle e delete bloqueiam o registo com `lockForUpdate()`. O delete volta a contar utilizadores dentro da transação para reduzir risco TOCTOU.

### `SystemRoleDefinitionRegistry`

Centraliza:

- identificadores dos perfis de sistema;
- scope esperado;
- permissões estruturais;
- decisão de proteção de um registo `Role`.

### `PermissionCatalogService`

Converte o catálogo técnico numa matriz visual ordenada e segura:

- labels de domínio em português;
- labels de módulos e ações;
- overrides para permissões críticas;
- código técnico preservado como informação secundária;
- fallback explícito para códigos desconhecidos;
- classificação de sensibilidade.

### `MunicipalRoleTemplateRegistry`

Disponibiliza três modelos versionados. O registry:

- não concede permissões diretamente;
- não cria perfis automaticamente;
- não associa utilizadores;
- resolve IDs a partir de nomes técnicos exatos;
- falha de forma controlada se faltar uma permissão obrigatória;
- permite rever a matriz antes da criação.

### `RoleAssignmentService`

Reforçado para:

- rejeitar perfis inativos;
- impedir self-promotion;
- impedir atribuição entre municípios quando ambos os utilizadores têm município definido;
- impedir que o operador atribua um perfil acima das suas próprias permissões;
- impedir remoção insegura do último administrador ativo;
- manter pelo menos um perfil operacional por utilizador;
- auditar associação e remoção com justificação.

### `MfaEnforcementService`

A regra passou a ser cumulativa:

```text
MFA obrigatório = imposição manual
               OU perfil sensível legado ativo
               OU permissão sensível num perfil ativo
```

Uma permissão sensível num perfil inativo não obriga MFA e não concede acesso.

### Integrações ajustadas

Foram também reforçados:

- `UserAdministrationService`;
- `DashboardAuthorizationService`;
- `WorkspaceService`;
- `ReportPermissionService`.

Estes ajustes garantem que perfis personalizados podem entrar nos fluxos estritamente autorizados sem depender de nomes fixos de role.

## 7. Policies

### `RolePolicy`

Abilities cobertas:

- `viewAny`;
- `view`;
- `create`;
- `duplicate`;
- `update`;
- `toggle`;
- `delete`;
- `viewUsers`;
- `audit`.

Regras principais:

- `candidate` nunca lê nem muta perfis de backoffice;
- `auditor` pode ler/auditar quando autorizado, mas nunca mutar;
- update, toggle e delete só se aplicam a perfis municipais personalizados;
- consulta e duplicação respeitam o scope gerível.

### `RoleAssignmentPolicy`

A associação e remoção de perfis usam permissões próprias e são novamente validadas pelo service.

## 8. Form Requests

Foram criados ou reforçados:

- `StoreRoleRequest`;
- `UpdateRoleRequest`;
- `SyncRolePermissionsRequest`;
- `DuplicateRoleRequest`;
- `ToggleRoleStatusRequest`;
- `DeleteRoleRequest`;
- `AssignUserRoleRequest`;
- `StoreBackofficeUserRequest`.

Os requests:

- autorizam através de Gate/Policy;
- exigem justificação nas operações críticas;
- validam IDs de permissões;
- rejeitam IDs inexistentes;
- deduplicam a seleção;
- limitam e normalizam strings;
- não aceitam `is_system`, scope ou atributos técnicos protegidos;
- não permitem atribuir um perfil inativo.

## 9. Controllers e interface

### Controllers

- `RoleManagementController`: CRUD, sync, duplicação, toggle, utilizadores, auditoria, associação e remoção;
- `MunicipalRoleTemplateController`: catálogo e pré-preenchimento para revisão;
- `UserAdministrationController`: integração segura da atribuição inicial de perfis.

Os controllers permanecem finos e delegam mutações nos services.

### Views

Área de gestão:

- `resources/views/backoffice/access/roles/index.blade.php`;
- `create.blade.php`;
- `edit.blade.php`;
- `show.blade.php`;
- `users.blade.php`;
- `audit.blade.php`;
- partials `details.blade.php` e `permissions.blade.php`;
- `resources/views/backoffice/access/role-templates/index.blade.php`.

A interface oferece:

- pesquisa e filtros por classificação/estado;
- badges Sistema, Municipal personalizada, Ativa e Inativa;
- paginação e `withCount('users')`;
- matriz com `fieldset`/`legend`, labels associadas e códigos técnicos;
- pesquisa local, seleção por domínio e contador selecionado;
- estado read-only para perfis de sistema;
- preservação da seleção após erro de validação;
- listagem paginada de utilizadores associados;
- auditoria do ciclo de vida.

## 10. Rotas e middleware

Foram adicionadas/normalizadas rotas sob o backoffice existente:

- `backoffice.roles.index`;
- `backoffice.roles.create`;
- `backoffice.roles.store`;
- `backoffice.roles.show`;
- `backoffice.roles.edit`;
- `backoffice.roles.update`;
- `backoffice.roles.permissions.update`;
- `backoffice.roles.duplicate`;
- `backoffice.roles.activate`;
- `backoffice.roles.deactivate`;
- `backoffice.roles.users`;
- `backoffice.roles.audit`;
- `backoffice.roles.destroy`;
- `backoffice.role-templates.index`;
- `backoffice.role-templates.create`;
- `backoffice.users.roles.assign`;
- `backoffice.users.roles.remove`.

Todas resolvem efetivamente:

- `auth`;
- `active.backoffice`;
- `mfa.backoffice`;
- `log.backoffice`;
- `permission:roles.<acao>`.

O middleware fixo herdado do grupo exterior é removido especificamente nestas rotas através de `withoutMiddleware(...)`. Os testes inspecionam o middleware resolvido e confirmam a ausência efetiva de `role:`.

### Migração incremental de workflows

Foram migradas apenas as rotas necessárias aos perfis iniciais:

- Painel Principal;
- Workspaces, favoritos e preferências;
- Pesquisa Universal e Centro de Comandos;
- Case Workspace de candidatura;
- consulta de elegibilidade;
- área de relatórios;
- exportação, descarga e auditoria de relatórios de candidaturas.

Não foi iniciada uma conversão transversal das centenas de rotas legadas.

## 11. Matriz de permissões

As 277 permissões existentes são agrupadas visualmente em:

- Candidaturas;
- Documentos;
- Elegibilidade;
- Classificação;
- Audiência;
- Listas;
- Contratos;
- Finanças;
- Manutenção;
- Relatórios;
- Administração;
- RGPD;
- Outros, como fallback controlado.

O nome técnico nunca é alterado. Exemplos de apresentação:

- `applications.view` → Consultar candidaturas;
- `applications.create` → Registar candidaturas;
- `applications.export` → Exportar candidaturas;
- `documents.approve` → Validar documentos;
- `roles.audit` → Consultar auditoria de perfis.

## 12. Classificação de sensibilidade e MFA

São sensíveis por módulo, no mínimo:

- auditoria de acessos;
- logs de auditoria;
- contratos;
- exportações;
- finanças e pagamentos;
- privacidade e RGPD;
- perfis e utilizadores;
- segurança.

São sensíveis por ação, no mínimo:

- aprovar e rejeitar;
- eliminar;
- exportar e descarregar;
- auditar;
- publicar;
- atribuir e remover;
- gerir membros/SLA;
- impor MFA;
- repor palavra-passe;
- desativar/reativar;
- reatribuir;
- revogar sessões.

Existem ainda classificações explícitas para decisões administrativas, atribuições, decisões documentais, elegibilidade, listas públicas e classificação.

O wildcard `*` é sempre sensível.

## 13. Decisão: templates em vez de roles de sistema

Foi adotada a opção recomendada: **templates duplicáveis para perfis municipais personalizados**.

Razões:

- evita aumentar o conjunto estrutural de perfis de sistema;
- permite adaptação por município;
- obriga a revisão da matriz antes de guardar;
- preserva o menor privilégio;
- não cria perfis nem associações silenciosas;
- permite evolução versionada sem migrations adicionais.

Na base local existem 11 roles estruturais e zero perfis personalizados criados automaticamente. Isto é intencional: os três modelos só se materializam quando um operador autorizado os revê e cria no backoffice.

## 14. Perfis funcionais iniciais

### Operador de recolha

Permissões:

- `dashboard.view`;
- `applications.view/create/update`;
- `documents.view/create/update`;
- `administrative_processes.view/create`.

Pode registar e atualizar recolha, receber documentos e consultar o processo. Não pode decidir elegibilidade/documentos, exportar, classificar ou aceder a contratos, finanças, manutenção ou administração.

### Analista de candidaturas

Permissões:

- `dashboard.view`;
- `applications.view/update/audit`;
- `documents.view/update/approve/reject/audit`;
- `eligibility.view`;
- `administrative_processes.view/update/audit`;
- `work_tasks.view/claim/update_status/complete`.

Pode analisar candidaturas, validar/rejeitar documentos, consultar elegibilidade e acompanhar o processo. Não pode exportar, classificar, gerir contratos, finanças, manutenção ou perfis.

### Exportador de candidaturas

Permissões:

- `dashboard.view`;
- `applications.view/export`;
- `reports.view/export/audit`.

Pode consultar candidaturas, criar e descarregar exportações autorizadas e consultar a respetiva auditoria. Não pode alterar candidaturas, decidir documentos/elegibilidade ou aceder a módulos proibidos.

O `ReportPermissionService` e o catálogo de relatórios passaram a exigir também a permissão de domínio adequada. Assim, `reports.export` não transforma o perfil num exportador universal: relatórios de candidaturas exigem `applications.export`, reclamações exigem `complaints.export` e ocupação exige `housing_units.export`.

## 15. Auditoria implementada

Eventos coerentes e minimizados:

- `role_created`;
- `role_updated`;
- `role_duplicated`;
- `role_activated`;
- `role_deactivated`;
- `role_deleted`;
- `role_assigned`;
- `role_removed`.

Metadata registada conforme a operação:

- snapshot `before`/`after`;
- permissões adicionadas e removidas;
- perfil de origem na duplicação;
- número de utilizadores afetados;
- operador;
- justificação;
- nome técnico e label necessários para manter um delete inteligível.

Não são registados passwords, tokens, códigos MFA, recovery codes, sessões, conteúdo documental ou PII desnecessária.

Uma tentativa bloqueada sobre perfil de sistema não altera dados nem emite um falso evento de sucesso.

## 16. Segurança e RGPD

- Não foram adicionados dados pessoais ao catálogo de permissões.
- A listagem de utilizadores associados é paginada e reutiliza apenas os dados operacionais necessários.
- Nenhum payload documental foi incluído em logs.
- Não foi criado bypass a Policies ou middleware.
- `BlockInactiveBackofficeUsers` bloqueia explicitamente `candidate` antes do acesso ao backoffice, mesmo que exista uma atribuição anómala de perfil personalizado.
- O auditor mantém leitura e auditoria quando autorizado e não consegue executar mutações.
- A matriz impede concessão de permissões superiores às do próprio operador.
- Atribuição entre municípios é bloqueada quando ambos os utilizadores possuem `municipality_id` diferente.
- Ações sensíveis exigem MFA através do cálculo efetivo de permissões ativas.

## 17. Performance e concorrência

- listagem de perfis paginada;
- `withCount('users')` na listagem;
- permissões carregadas de forma agrupada;
- utilizadores associados paginados;
- nenhuma query em loop nas views;
- `lockForUpdate()` nas mutações concorrentes;
- verificação final de utilizadores associados dentro da transação de delete;
- nenhum `Model::all()` introduzido em tabelas operacionais;
- não foi criado cache com PII.

A matriz de 277 permissões é carregada numa query ordenada e agrupada em memória, um volume adequado ao catálogo estrutural atual.

## 18. Testes criados

Foram criados nove ficheiros dedicados:

- `tests/Feature/Backoffice/CustomRoleManagementTest.php`;
- `tests/Feature/Backoffice/MunicipalRoleTemplateTest.php`;
- `tests/Feature/Backoffice/PermissionMatrixTest.php`;
- `tests/Feature/Security/CandidateBackofficeBoundaryTest.php`;
- `tests/Feature/Security/CustomRoleAuditTest.php`;
- `tests/Feature/Security/CustomRoleEffectivePermissionTest.php`;
- `tests/Feature/Security/InitialMunicipalRoleProfilesTest.php`;
- `tests/Feature/Security/PermissionSensitiveMfaTest.php`;
- `tests/Feature/Security/SystemRoleProtectionTest.php`.

Cobertura principal:

- proteção integral de perfis de sistema;
- CRUD e paginação;
- matriz, labels, fallback e preservação após validação;
- templates exatos e ausência de concessão automática;
- auditoria do ciclo de vida;
- roles inativas e wildcards;
- MFA por permissão sensível;
- candidate e auditor;
- middleware efetivo;
- três perfis funcionais contra rotas HTTP reais;
- módulos permitidos e proibidos;
- ausência de `permission_user`.

## 19. Testes existentes alterados

Foram alterados doze ficheiros de regressão:

- `FullHousingProgramFlowTest`: sessão MFA numa rota de relatórios agora corretamente sensível;
- `BasicLoadSmokeTest`: sessão MFA antes da medição do relatório;
- `QA45DashboardsKpisMunicipalReportsTest`: sessão MFA no acesso municipal a relatórios;
- `MunicipalExportsSecurityTest`: compatibilidade com a permissão de domínio da exportação;
- `MunicipalReportsAuthorizationTest`: compatibilidade com a permissão de domínio do relatório;
- `AdministrativeProcessBackofficeRouteAccessTest`: MFA na timeline com permissão de auditoria;
- `ApplicationBackofficeRouteAccessTest`: MFA na timeline com permissão de auditoria;
- `AuditAccessRoutesCommandTest`: contadores substituídos pelos resultados reais da auditoria;
- `DocumentReviewPermissionAccessTest`: alinhamento das expectativas com middleware por permissão;
- `RbacCharacterizationTest`: alinhamento do dashboard/workspace com `dashboard.view`;
- `Sprint17ReportingDashboardTest`: permissões explícitas de domínio e MFA;
- `Sprint7EligibilityEngineTest`: MFA na consulta de elegibilidade protegida.

O perfil estrutural `jury` recebeu `dashboard.view`, corrigindo uma lacuna real do catálogo sem reintroduzir exceções por nome de role.

Não foram removidas asserções para tornar os testes permissivos. O scan exigido não encontrou:

- `assertTrue(true)`;
- `markTestSkipped`;
- `withoutMiddleware` em testes;
- `assertStatus(500)`.

## 20. Validações executadas

### Suite completa

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Resultado final: **993 testes, 7 076 asserções, zero falhas**.

Baseline: 957 testes e 6 642 asserções.

Diferença: +36 testes e +434 asserções no total agregado da suite.

### UX

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit \
  --configuration phpunit.xml \
  --filter UX \
  --stop-on-failure
```

Resultado: **129 testes, 642 asserções, zero falhas**.

### Cobertura direcionada final

Perfis municipais, matriz, auditoria, MFA, RBAC, rotas e quick actions:

Resultado: **61 testes, 585 asserções, zero falhas**.

Os sete ficheiros/cenários que falharam na primeira passagem completa após o endurecimento foram repetidos isoladamente:

Resultado: **37 testes, 234 asserções, zero falhas**.

### Composer e cache

```bash
composer validate --strict
php artisan optimize:clear
```

Resultado: PASS.

### Rotas

```bash
php artisan route:list --except-vendor
```

Resultado: PASS, **1 157 rotas apresentadas**.

### Build

```bash
npm run build
```

Resultado: PASS com Vite 8.0.16.

### Integridade do diff

```bash
git diff --check
```

Resultado: PASS.

## 21. Pint

### Ficheiros alterados

```bash
xargs ./vendor/bin/pint --test < /tmp/changed-files-45c.txt
```

Resultado: **PASS nos 61 ficheiros alterados**.

### Global

```bash
./vendor/bin/pint --test
```

Resultado: FAIL por dívida herdada em 73 ficheiros fora do diff 45C. Nenhum ficheiro alterado nesta sprint consta da lista global de falhas.

Não foi feita uma reformatação transversal fora do âmbito funcional.

## 22. PHPStan

### Ficheiros de produção alterados

```bash
./vendor/bin/phpstan analyse <ficheiros PHP alterados> --memory-limit=1G
```

Resultado: **PASS, zero erros**.

### Comparação global

Baseline:

- wrapper: 157 ocorrências;
- normalizadas: 30;
- ficheiros: 8.

Resultado final:

- wrapper: 157 ocorrências;
- normalizadas: 30;
- ficheiros: 8.

Os ficheiros `/tmp/phpstan-45c-before.json` e `/tmp/phpstan-45c-after.json` são byte a byte idênticos, com SHA-256:

```text
2f923049d010a289d476ef12f138041737bee79bca862cc4765b817ed812dbc7
```

Conclusão: nenhum erro novo, nenhum aumento global e zero diagnósticos em ficheiros alterados.

## 23. Auditoria de rotas antes/depois

| Métrica | Antes | Depois | Diferença |
|---|---:|---:|---:|
| `total_routes` | 1 146 | 1 160 | +14 |
| `fixed_role_routes` | 951 | 928 | -23 |
| `backoffice_fixed_role_routes` | 730 | 708 | -22 |
| `candidate_fixed_role_routes` | 220 | 220 | 0 |
| `permission_middleware_routes` | 151 | 188 | +37 |
| `backoffice_fixed_role_without_active_backoffice` | 608 | 596 | -12 |
| `backoffice_fixed_role_without_mfa_backoffice` | 608 | 596 | -12 |
| `backoffice_fixed_role_without_log_backoffice` | 608 | 596 | -12 |

Interpretação:

- as novas rotas de gestão usam permissões e os três guards;
- as rotas fixas não aumentaram;
- 23 dependências de role fixa foram removidas;
- 37 rotas adicionais passaram a expor middleware de permissão;
- as rotas candidate mantêm a fronteira histórica, reforçada pelo bloqueio explícito de backoffice.

## 24. Confirmações dos critérios críticos

- **Sem permissões diretas:** confirmado; não existe `permission_user`.
- **Role inativa:** deixa de conceder permissões e de obrigar MFA por si só.
- **Role reativada:** restaura permissões.
- **Perfis de sistema:** read-only em Request, Policy, Service e testes.
- **Delete concorrente:** protegido por transação, lock e contagem final de utilizadores.
- **Candidate:** 403 no backoffice mesmo com permissões anómalas.
- **Auditor:** consulta/auditoria autorizada, mutações bloqueadas.
- **MFA sensível:** calculado por permissões efetivas de roles ativas, cumulativo com regras antigas e imposição manual.
- **Menor privilégio:** os três templates usam listas explícitas, sem wildcard.
- **URL direta:** módulos proibidos devolvem 403 em testes HTTP reais.
- **Navegação/pesquisa:** recursos proibidos são omitidos antes da renderização.
- **Auditoria:** ciclo de vida e atribuições registados com justificação e metadata minimizada.

## 25. Riscos residuais e limitações aceites

### 25.1. Tenancy de roles não materializada

A tabela `roles` não possui `municipality_id`. A sprint preservou `scope = municipal` e não introduziu uma migration transversal sem suporte arquitetural completo.

Consequência:

- o isolamento entre utilizadores de municípios diferentes é aplicado na atribuição quando ambos possuem `municipality_id`;
- a propriedade municipal do próprio perfil ainda não pode ser imposta por coluna, binding ou constraint.

Este é o principal risco funcional aceite e deve ser resolvido numa evolução multi-tenant dedicada.

### 25.2. Rotas legadas

Persistem:

- 928 rotas com middleware fixo;
- 708 rotas backoffice com middleware fixo;
- 596 rotas backoffice fixas sem cada um dos guards `active/mfa/log`.

Estes valores melhoraram e não aumentaram, mas exigem migração incremental por bounded context.

### 25.3. Dívida global de qualidade

- Pint global: 73 ficheiros herdados fora do diff;
- PHPStan global: 30 diagnósticos normalizados herdados em 8 ficheiros.

Os ficheiros alterados estão limpos e o total PHPStan permaneceu rigorosamente igual à baseline.

## 26. Backlog recomendado

1. Introduzir tenancy explícita de perfis (`municipality_id` ou modelo equivalente), com constraints, binding scoped e estratégia de migração.
2. Continuar a migração incremental das 708 rotas backoffice ainda dependentes de role fixa.
3. Normalizar os 596 endpoints legados sem `active.backoffice`, `mfa.backoffice` e `log.backoffice`.
4. Liquidar os 30 diagnósticos PHPStan herdados sem recorrer a baseline permanente.
5. Corrigir a dívida Pint global em intervenção isolada para evitar churn funcional.
6. Avaliar cache de autorização apenas se medições reais o justificarem, incluindo chave por utilizador/contexto e invalidação explícita.
7. Acrescentar revisão periódica de perfis inativos, permissões sensíveis e utilizadores associados.

## 27. Recomendação para a sprint seguinte

Priorizar uma sprint de **isolamento multi-município de perfis e continuidade da migração permission-first**, começando por um bounded context de risco elevado e volume controlável, como contratos/finanças ou administração de utilizadores/equipas.

Não é recomendável converter todas as rotas legadas numa única intervenção.

## 28. Decisão final

`PASS_WITH_ACCEPTED_RISKS`

A funcionalidade 45C está completa e verde: gestão de perfis personalizados, proteção dos perfis de sistema, matriz traduzida, templates, auditoria, permissões efetivas, MFA sensível, bloqueio de candidato, auditor read-only, fluxos dos três perfis iniciais, suite completa, UX, build, Composer, route list, diff check, Pint direcionado e PHPStan direcionado passaram.

O PASS puro não é atribuído exclusivamente por:

- dívida global Pint/PHPStan herdada e inalterada;
- ausência de tenancy explícita na tabela `roles`;
- volume residual de rotas legadas com middleware fixo.
