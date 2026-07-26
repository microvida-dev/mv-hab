# Sprint 45D — Catálogo de funcionalidades municipais

## 1. Resumo executivo

A Sprint 45D introduziu a camada mínima de disponibilização funcional por Município para os fluxos de candidaturas. O catálogo ficou deliberadamente limitado a três funcionalidades tipadas:

- `applications.intake` — Recolha de candidaturas;
- `applications.review` — Análise de candidaturas;
- `applications.export` — Exportação de candidaturas.

A decisão operacional passou a obedecer à fórmula:

```text
municipalityHasFeature
&& userHasPermission
&& policyAllowsRecordScope
```

Os entitlements não criam permissões, não alteram roles e não substituem Policies. A ausência de Município ou de uma linha ativa falha de forma fechada. A administração é exclusiva de operadores de plataforma, exige MFA, permissão e justificação, e todas as alterações são transacionais e auditadas.

Branch: `sprint-45d-municipality-feature-entitlements`

Commit-base: `c6da7703e59446118e02b5e01a538c9b8a19bc6c`

Data de fecho técnico: 23 de julho de 2026

Decisão: `PASS_WITH_ACCEPTED_RISKS`

## 2. Commits técnicos

- `352357da feat(entitlements): adicionar catálogo municipal tipado`
- `5745f1fd feat(entitlements): persistir funcionalidades por município`
- `74e2e1c9 feat(entitlements): aplicar dependências e auditoria transacional`
- `175c0b32 feat(entitlements): adicionar administração de plataforma`
- `4d3b8c62 feat(access): aplicar funcionalidades aos fluxos de candidaturas`
- `14ad9add fix(access): proteger cronologia e atualizar auditoria de rotas`
- `81ab187b test(entitlements): alinhar fixtures ao contexto municipal`
- `426b1e88 fix(entitlements): corrigir tipagem estática do serviço`
- `8a49e0bc fix(migrations): encurtar índices de funcionalidades municipais`

Antes deste relatório, a sprint alterava 106 ficheiros. O volume inclui 44 testes de regressão atualizados para declarar explicitamente Município e entitlements, preservando o comportamento fail-closed em produção.

## 3. Catálogo tipado

Foi criado `App\Enums\FeatureKey` como fonte única do catálogo.

| Case | Valor técnico | Label | Dependências |
|---|---|---|---|
| `ApplicationIntake` | `applications.intake` | Recolha de candidaturas | nenhuma |
| `ApplicationReview` | `applications.review` | Análise de candidaturas | `applications.intake` |
| `ApplicationExport` | `applications.export` | Exportação de candidaturas | `applications.intake` |

Não existem wildcards, registos dinâmicos de catálogo, pacotes ou bundles. Os testes confirmam os três cases exatos, valores estáveis, labels, dependências e ausência de ciclos.

## 4. Persistência e migration

Foi criada a migration:

- `database/migrations/2026_07_23_000035_create_municipality_feature_entitlements_table.php`.

Estrutura final comprovada na base MySQL:

```text
id
municipality_id
feature_key
enabled
created_at
updated_at
```

Constraints e índices:

- FK para `municipalities.id`, com eliminação em cascata compatível com a relação proprietária;
- unique `mfe_municipality_feature_unique` em `municipality_id + feature_key`;
- índice `mfe_municipality_enabled_index` em `municipality_id + enabled`.

Os nomes explícitos dos índices evitam ultrapassar o limite de 64 caracteres do MySQL. A primeira execução real revelou o nome automático demasiado longo; a tabela nova estava vazia e a migration permanecia pendente. Foi removida exclusivamente essa tabela incompleta, os índices receberam nomes curtos e a execução seguinte concluiu sem afetar dados existentes.

### 4.1. Compatibilidade e backfill

- ausência de linha significa funcionalidade desativada;
- Municípios existentes no deploy recebem as três funcionalidades ativas;
- Municípios criados depois da migration não recebem entitlements automaticamente;
- o backfill usa strings literais e `insertOrIgnore`, sem depender do enum histórico;
- o processamento é feito em chunks de 500 Municípios.

Resultado na base local:

```text
Municípios existentes: 2
Entitlements criados: 6
Entitlements ativos: 6
Migration: batch 6, Ran
```

Não foram alteradas roles, permissões nem associações de utilizadores durante o backfill.

### 4.2. Reversibilidade

O ciclo foi validado numa SQLite temporária isolada:

```bash
php artisan migrate:fresh --force
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
```

Após rollback:

- `municipality_feature_entitlements` ausente;
- `users` presente;
- `roles` presente.

Após reaplicação, as três tabelas esperadas estavam presentes. O teste de persistência passou com 4 testes e 11 asserções.

## 5. Modelo, relação e factory

### `MunicipalityFeatureEntitlement`

- fillable explícito para `municipality_id`, `feature_key` e `enabled`;
- cast `feature_key` para `FeatureKey`;
- cast booleano de `enabled`;
- relação `municipality()`;
- scopes `enabled()`, `forMunicipality()` e `forFeature()`;
- nenhuma autorização no Model.

### `Municipality`

Foi adicionada a relação tipada `featureEntitlements()`.

### Factory

`MunicipalityFeatureEntitlementFactory` começa desativada e disponibiliza states explícitos:

- `enabled()`;
- `disabled()`;
- `forFeature(FeatureKey $feature)`.

A factory de Município não ativa funcionalidades implicitamente.

## 6. Serviço central

`MunicipalityEntitlementService` centraliza:

- `enabledFor()`;
- `enabledForUser()`;
- `activeFor()`;
- `enableFor()`;
- `disableFor()`;
- `ensureEnabledFor()`.

Comportamento:

- ausência de linha devolve `false`;
- ausência de Município no utilizador devolve `false`;
- as funcionalidades ativas são carregadas numa query e memoizadas apenas durante o request;
- a memoização é invalidada após ativação ou desativação;
- não existe cache persistente nem PII em cache;
- o serviço não atribui roles ou permissões e não decide a Policy do registo.

## 7. Dependências, concorrência e invariantes

Ativação e desativação decorrem numa transação. O serviço bloqueia a linha do Município com `lockForUpdate()`, volta a ler os entitlements ativos dentro da transação, valida as invariantes e só depois executa `updateOrCreate()`.

Regras implementadas:

- intake pode ser ativada sem pré-condição;
- review sem intake é rejeitada;
- export sem intake é rejeitada;
- intake não pode ser desativada enquanto review estiver ativa;
- intake não pode ser desativada enquanto export estiver ativa;
- review e export são independentes;
- não existe desativação em cascata;
- uma chamada direta ao service não contorna as regras;
- operações inválidas não criam evento de sucesso.

`FeatureDependencyException` produz uma mensagem controlada na interface e não origina erro 500.

## 8. Middleware de feature

Foi criado `EnsureMunicipalityFeatureIsEnabled` e registado em `bootstrap/app.php` com o alias:

```text
municipality.feature
```

O middleware:

1. converte o parâmetro com `FeatureKey::tryFrom()`;
2. devolve 404 para uma chave desconhecida;
3. exige um utilizador autenticado com `municipality_id`;
4. resolve a relação municipal existente do utilizador;
5. consulta o serviço central;
6. devolve 403 com mensagem neutra quando a funcionalidade está indisponível;
7. não concede permissões e não executa a Policy.

Mensagem pública:

> Esta funcionalidade não está disponível para o Município atual.

Não existe linguagem comercial na resposta.

## 9. Administração de plataforma

Foi adicionada uma área mínima no backoffice existente, sem criar um segundo painel.

### 9.1. Permissões

- `municipality_features.view`;
- `municipality_features.update`;
- `municipality_features.audit`.

As permissões são criadas idempotentemente pelo catálogo estrutural existente. Foram adicionadas labels portuguesas ao `PermissionCatalogService`. `update` e `audit` são classificadas como sensíveis para MFA.

### 9.2. Policy

`MunicipalityFeatureEntitlementPolicy` implementa:

- `viewAny`;
- `view`;
- `update`;
- `audit`.

O scope de plataforma é explícito no contexto atual: operador não-candidato sem `municipality_id`. Assim:

- administrador municipal com Município associado não gere entitlements;
- candidate fica bloqueado;
- auditor pode consultar auditoria quando autorizado, mas não muta;
- mutação exige `municipality_features.update`;
- nenhuma rota administrativa depende de middleware fixo por role.

### 9.3. Form Requests

`EnableMunicipalityFeatureRequest` e `DisableMunicipalityFeatureRequest`:

- autorizam por Gate/Policy sobre o Município route-bound;
- só aceitam `justification`;
- fazem trim;
- exigem entre 10 e 1000 caracteres;
- rejeitam HTML;
- não aceitam Município, feature ou booleano manipulável no payload.

### 9.4. Controller e interface

`MunicipalityFeatureController` permanece fino e delega mutações no service. A interface permite:

- listar Municípios com paginação de 25;
- consultar as três funcionalidades;
- ver código, label, estado e dependências;
- perceber por texto por que uma ação está bloqueada;
- ativar ou desativar com justificação;
- consultar auditoria paginada.

Foram criadas três views Blade:

- `backoffice/platform/municipality-features/index.blade.php`;
- `show.blade.php`;
- `audit.blade.php`.

## 10. Rotas administrativas

| Rota | Método | Permissão |
|---|---|---|
| `backoffice.platform.municipality-features.index` | GET | `municipality_features.view` |
| `backoffice.platform.municipality-features.show` | GET | `municipality_features.view` |
| `backoffice.platform.municipality-features.enable` | POST | `municipality_features.update` |
| `backoffice.platform.municipality-features.disable` | POST | `municipality_features.update` |
| `backoffice.platform.municipality-features.audit` | GET | `municipality_features.audit` |

Todas resolvem efetivamente:

- `auth`;
- `active.backoffice`;
- `mfa.backoffice`;
- `log.backoffice`;
- `permission:municipality_features.<ação>`.

O middleware fixo herdado do grupo exterior é removido apenas nestas rotas com `withoutMiddleware(...)`.

## 11. Matriz de fluxos operacionais

Foram identificados 26 endpoints com middleware municipal direto.

| Grupo de rotas | Feature | Permission middleware | Policy/scope |
|---|---|---|---|
| `backoffice.application-intake.*` (3) | `applications.intake` | `administrative_processes.create` | `AdministrativeProcessPolicy::create/createForApplication` + scope municipal |
| `admin.document-reviews.*` (8) | `applications.review` | `documents.view/approve/reject` | `DocumentSubmissionPolicy` + query municipal |
| `backoffice.application-reviews.*` (4) | `applications.review` | `administrative_processes.create/view/update` | `ApplicationReviewPolicy` + processo municipal |
| candidaturas, Case Workspace e cronologia (4) | `applications.review` | `applications.view` ou `applications.audit,applications.view` | `ApplicationPolicy`/autorização do caso + scope municipal |
| `backoffice.eligibility.*` (4) | `applications.review` | `eligibility.view/create/update` | `EligibilityCheckPolicy` + candidatura municipal |
| relatório de candidatura (3) | `applications.export` | `applications.export` e permissões `reports.*` adequadas | `ApplicationReportPolicy` + scope municipal |

Rotas diretamente protegidas incluem:

- intake: índice, criação unitária de processo e criação em lote;
- revisão documental: índice, detalhe, preview, download, análise IA, colocar em análise, validar e rejeitar;
- revisão administrativa: criar, guardar, consultar e concluir;
- candidaturas: índice, detalhe, Case Workspace e timeline;
- elegibilidade: índice, detalhe, executar e reexecutar;
- relatórios de candidatura: consultar, gerar e descarregar.

Os endpoints genéricos de relatórios não receberam um bloqueio global. `ReportPermissionService` identifica primeiro o tipo de relatório e exige `applications.export` apenas para relatórios de candidaturas. `ReportRunService`, `ReportDownloadService` e os controllers de run/export/auditoria aplicam ainda o scope municipal do artefacto.

## 12. Isolamento municipal do registo

Foi criado `MunicipalRecordScopeService` para aplicar escopo a:

- candidaturas;
- processos administrativos;
- documentos submetidos;
- verificações de elegibilidade;
- revisões;
- relatórios de candidatura;
- runs, exports e logs de relatórios.

As Policies de `Application`, `AdministrativeProcess`, `ApplicationReview`, `DocumentSubmission`, `EligibilityCheck`, `ApplicationReport`, `ReportRun` e `ReportExport` foram reforçadas para validar o Município do recurso.

Consequências comprovadas:

- feature ativa no Município A não autoriza um registo do Município B;
- utilizador sem Município falha fechado nas operações municipais;
- platform admin sem contexto municipal não obtém acesso operacional implícito;
- documentos soltos só entram na query quando o contexto do candidato/adesão pertence ao Município;
- listas e métricas são filtradas antes da renderização.

## 13. Navegação, dashboard e pesquisa

Foram ajustados apenas os pontos relacionados com candidaturas:

- `WorkspaceService`;
- `DashboardAuthorizationService`;
- `DashboardMetricService`;
- `DashboardDeadlineService`;
- `DashboardQuickActionService`;
- `DashboardWidgetRegistry`;
- `ApplicationSearchSource`;
- `CommandSearchSource`;
- `SearchResultAuthorizationService`;
- catálogo e autorização de relatórios.

Regras:

- feature desativada oculta módulo, widget, ação e resultado de pesquisa;
- permission ausente também oculta;
- feature e permission ativas só apresentam recursos do Município;
- a URL direta continua protegida por middleware e Policy;
- contagens de candidaturas/documentos no dashboard são agregadas com scope municipal;
- favoritos e pesquisa não transformam navegação escondida num bypass HTTP.

## 14. Auditoria

Eventos adicionados:

- `municipality_feature_enabled`;
- `municipality_feature_disabled`.

Metadata minimizada:

- `municipality_id`;
- `feature_key`;
- `before`;
- `after`;
- `dependencies`;
- `actor`;
- `justification`.

O evento é escrito dentro da mesma transação da alteração. Não são registados passwords, tokens, MFA, documentos, candidaturas ou PII operacional.

## 15. Fórmula de autorização comprovada

| Feature | Permission efetiva | Policy/scope | Resultado |
|---:|---:|---:|---|
| false | false | false | 403 |
| false | true | true | 403 |
| true | false | true | 403 |
| true | true | false | 403 |
| true | true | true | permitido |

Confirmações adicionais:

- role inativa equivale a permission efetiva false;
- o middleware de feature não concede permission;
- desativar feature não remove permissões da role;
- reativar feature restaura acesso quando permission e Policy continuam válidas;
- MFA continua obrigatório para permissões sensíveis;
- candidate não entra no backoffice;
- auditor não executa mutações.

## 16. Segurança e RGPD

- Entitlements não contêm dados pessoais.
- Nenhum conteúdo documental ou de candidatura é persistido na nova tabela.
- A interface usa apenas nome do Município, código da feature, estado e auditoria administrativa minimizada.
- As queries operacionais são filtradas antes da renderização.
- Não foi criado bypass a Policy, Gate, Form Request, MFA ou middleware de permissão.
- Não existe `permission_user`; permissões continuam exclusivamente por roles.
- A tabela e o Model não contêm preços, valores, trials, expiração, limites, quotas, grace period, read-only comercial, subscrição, faturação, pacote ou plano.
- Não foi adicionada dependência externa nem package de feature flags.

## 17. Performance

- três features carregadas numa query por Município/request;
- mapa indexado em memória por código técnico;
- invalidação imediata após mutação;
- listagem administrativa paginada e com eager loading dos entitlements;
- queries de candidaturas/documentos/relatórios scoped no SQL;
- sem `Model::all()` em tabelas operacionais;
- sem queries em Blade;
- nenhuma cache persistente ou com PII.

## 18. Testes dedicados criados

Foram criados nove ficheiros de teste e um concern de fixtures:

- `tests/Unit/Entitlements/FeatureKeyTest.php`;
- `tests/Feature/Entitlements/MunicipalityFeatureEntitlementPersistenceTest.php`;
- `tests/Feature/Entitlements/MunicipalityEntitlementServiceTest.php`;
- `tests/Feature/Entitlements/MunicipalityFeatureDependencyTest.php`;
- `tests/Feature/Entitlements/MunicipalityFeatureNavigationTest.php`;
- `tests/Feature/Backoffice/MunicipalityFeatureManagementTest.php`;
- `tests/Feature/Security/MunicipalityFeatureAuditTest.php`;
- `tests/Feature/Security/MunicipalityFeatureMiddlewareTest.php`;
- `tests/Feature/Security/ApplicationFeatureEntitlementAccessTest.php`;
- `tests/Concerns/InteractsWithMunicipalFeatures.php`.

Execução dedicada final:

```text
33 testes
144 asserções
0 falhas
```

Cobertura:

- catálogo e ciclos;
- migration, casts, scopes, unique e backfill;
- ativação/desativação e dependências;
- concorrência lógica e chamada direta ao service;
- administração, payload manipulado e unknown feature;
- auditoria;
- middleware efetivo;
- fórmula feature + permission + Policy;
- dois Municípios;
- role inativa e MFA;
- dashboard, workspace e pesquisa.

## 19. Testes existentes alterados

Foram atualizados 44 ficheiros de regressão. A causa foi objetiva: com a regra fail-closed, fixtures antigas sem Município ou sem entitlement deixaram de representar um contexto municipal válido.

Principais grupos ajustados:

- gestão documental e IA documental;
- relatórios e exportações municipais;
- Policies e rotas de candidaturas, artefactos, intake, revisão e tracking;
- MFA, RBAC, perfis municipais e auditoria de rotas;
- Sprints 6, 7, 8, 9, 17, 23 e 24;
- Case Workspace, dashboard, pesquisa universal, acessibilidade e terminologia UX;
- testes unitários de Case Workspace, dashboard e pesquisa.

As alterações passaram a:

- associar explicitamente utilizador e recurso ao mesmo Município;
- ativar explicitamente a feature necessária;
- preservar middleware, Policy e `User::hasPermission()` reais.

Não foram usados:

- `withoutMiddleware()` em testes;
- `assertTrue(true)`;
- `markTestSkipped()`;
- `assertStatus(500)`;
- permissions diretas por utilizador.

## 20. Suite completa e UX

### Baseline 45C

```text
993 testes
7 076 asserções
0 falhas
```

### Resultado final 45D

```text
1 026 testes
7 264 asserções
0 falhas
```

Diferença:

- +33 testes;
- +188 asserções;
- zero skipped/warnings reportados pelo runner;
- zero regressões.

### UX

```text
129 testes
642 asserções
0 falhas
```

O total UX permaneceu estável face à baseline.

## 21. Composer, cache, build, rotas e diff

Comandos finais:

```bash
composer validate --strict
php artisan optimize:clear
npm run build
php artisan migrate:status
php artisan route:list --except-vendor
git diff --check
```

Resultados:

- Composer: PASS;
- optimize clear: PASS;
- Vite 8.0.16: PASS;
- migration 45D: Ran;
- route list: PASS;
- build: `app-B4zsUYm7.css` e `app-DO2nEFzp.js`;
- diff check: PASS.

Contagem de rotas:

- `route:list --json`: 1 165;
- `route:list --except-vendor --json`: 1 162;
- diferença: 3 rotas, exclusivamente vendor, tal como na baseline.

## 22. Pint

### Ficheiros alterados

Todos os ficheiros PHP alterados passaram:

```text
Pint changed: PASS
Ficheiros alterados presentes na dívida global: 0
```

### Global

| Momento | Ficheiros com dívida |
|---|---:|
| Baseline | 73 |
| Final | 65 |

A dívida global diminuiu em oito ficheiros e não aumentou. Os 65 ficheiros restantes são anteriores à 45D e ficam fora do âmbito para evitar churn transversal.

## 23. PHPStan

O resultado normalizado foi reproduzido no commit-base através de worktree isolado e comparado com o HEAD final.

| Momento | Diagnósticos normalizados | Ficheiros |
|---|---:|---:|
| Baseline `c6da7703` | 30 | 8 |
| Final | 30 | 8 |

Distribuição idêntica antes/depois:

- `AuditAccessRoutes.php`: 8;
- `TimelineEvent.php`: 2;
- `DocumentReviewController.php`: 15;
- `AgendaController.php`: 1;
- `ProcedureMinuteController.php`: 1;
- `SimulationController.php`: 1;
- `FavoriteController.php`: 1;
- `StoreCandidateSimulationRequest.php`: 1.

Os novos ficheiros de entitlement e os blocos corrigidos passam PHPStan com zero erros. `DocumentReviewController` foi tocado apenas para injetar o scope municipal e mantém 15 diagnósticos em métodos herdados, exatamente iguais no commit-base. Não foi criada baseline permanente nem qualquer suppressão.

Conclusão: zero diagnósticos novos e dívida global inalterada.

## 24. Auditoria de rotas antes/depois

| Métrica | Antes | Depois | Diferença |
|---|---:|---:|---:|
| `total_routes` | 1 160 | 1 165 | +5 |
| `fixed_role_routes` | 928 | 926 | -2 |
| `backoffice_fixed_role_routes` | 708 | 706 | -2 |
| `candidate_fixed_role_routes` | 220 | 220 | 0 |
| `permission_middleware_routes` | 188 | 195 | +7 |
| `backoffice_fixed_role_without_active_backoffice` | 596 | 594 | -2 |
| `backoffice_fixed_role_without_mfa_backoffice` | 596 | 594 | -2 |
| `backoffice_fixed_role_without_log_backoffice` | 596 | 594 | -2 |

Interpretação:

- as cinco rotas novas são permission-first;
- as rotas fixas não aumentaram;
- duas rotas funcionais adicionais deixaram de depender de role fixa;
- as novas rotas administrativas usam os três guards;
- a dívida de rotas legadas diminuiu e não foi expandida transversalmente.

`AuditAccessRoutesCommandTest` foi atualizado apenas com estes valores reais.

## 25. Confirmações de âmbito

- Exatamente três features no enum.
- Exatamente uma tabela nova.
- Sem colunas preparatórias ou comerciais.
- Sem soft deletes.
- Sem permissões diretas.
- Sem alteração automática de roles.
- Sem migração transversal das 926 rotas fixas restantes.
- Sem aplicação do entitlement ao portal público ou simulador.
- Sem bloqueio global de relatórios de outros domínios.
- Sem dependência entre review e export.
- Sem cascata implícita.
- Sem package externa.

## 26. Riscos residuais e limitações aceites

### 26.1. Scope de operador de plataforma

O contexto atual distingue o operador de plataforma por ausência de `municipality_id`, combinada com permission e Policy. É seguro para esta sprint e testado, mas merece no futuro um atributo/scope estrutural explícito se a administração global crescer.

### 26.2. Roles sem tenancy materializada

A tabela `roles` continua sem `municipality_id`, dívida já documentada na 45C. A 45D não tentou resolver este tema. O isolamento dos registos operacionais é aplicado por Município em query e Policy.

### 26.3. Catálogo deliberadamente reduzido

Contratos, finanças, manutenção, concursos e restantes domínios não possuem feature entitlement. Isto é uma limitação intencional, não uma omissão da implementação.

### 26.4. Rotas legadas

Persistem:

- 926 rotas com middleware fixo;
- 706 rotas backoffice com middleware fixo;
- 594 rotas backoffice fixas sem cada guard `active/mfa/log`.

### 26.5. Dívida global de qualidade

- Pint global: 65 ficheiros herdados;
- PHPStan global: 30 diagnósticos herdados em 8 ficheiros.

Nenhuma destas dívidas aumentou e nenhuma regressão funcional ou de segurança foi introduzida.

## 27. Backlog recomendado

1. Criar scope estrutural explícito para operadores de plataforma quando existir desenho multi-tenant global aprovado.
2. Materializar tenancy de perfis numa sprint dedicada, com migration e constraints próprias.
3. Continuar a migração permission-first por bounded context, sem conversão global.
4. Liquidar os 30 diagnósticos PHPStan herdados sem baseline permanente.
5. Corrigir os 65 ficheiros Pint numa intervenção isolada.
6. Avaliar novas features municipais apenas quando existirem requisitos funcionais concretos.
7. Manter testes de dois Municípios em qualquer novo domínio com entitlement.

## 28. Decisão final

`PASS_WITH_ACCEPTED_RISKS`

A funcionalidade 45D está completa e operacional:

- catálogo tipado;
- persistência e backfill;
- migration MySQL/SQLite reversível;
- serviço fail-closed e transacional;
- dependências sem cascata;
- administração permission-first;
- justificação e auditoria;
- middleware de feature;
- permissions e Policies preservadas;
- isolamento municipal por registo;
- navegação, dashboard, pesquisa e relatórios integrados;
- formula AND comprovada;
- suite completa, UX, Composer, build, route list, diff check e Pint direcionado verdes;
- PHPStan global sem aumento;
- nenhuma permission direta ou campo comercial.

O PASS puro não é atribuído exclusivamente pela dívida global herdada de Pint/PHPStan e pelo volume residual de rotas legadas, todos documentados e sem agravamento nesta sprint.
