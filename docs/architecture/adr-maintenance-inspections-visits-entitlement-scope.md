# ADR — Autorização, Entitlements e Scope Municipal da Sprint 47G

## Estado

**Aceite para implementação**

## Contexto

A Sprint 47G migra para um modelo permission-first as rotas dos
contextos de manutenção, vistorias, visitas e Agenda.

O universo imutável foi fixado no manifesto:

- `docs/access/manifests/sprint-47g-route-manifest.json`
- commit de origem: `8bf949b965f3ee64d29c894a29148a3c34f0afd2`
- rotas reconciliadas: **96**
- manutenção: **51**
- vistorias: **26**
- visitas: **18**
- Agenda: **1**
- leituras: **43**
- mutações: **53**

A autorização final deve respeitar cumulativamente:

```text
permission
&& Policy
&& scope municipal
&& estado ou transição válida
&& MFA quando exigido
&& auditoria quando exigida
```

Roles servem apenas para atribuir permissions. Não constituem uma
camada autónoma de autorização e não existe bypass administrativo.

## Decisão

### 1. Entitlements comerciais

A Sprint 47G não introduz novos `FeatureKey`.

O catálogo atual contém apenas features relacionadas com candidaturas:

```text
applications.intake
applications.review
applications.export
```

Nenhuma destas features representa corretamente manutenção, vistorias,
visitas ou Agenda.

Fica proibido reutilizar uma feature de candidaturas para autorizar estes
contextos. A expansão comercial dos entitlements fica reservada ao
Programa 48.

Nesta sprint, `resolved_feature` permanece `null`.

### 2. Permissions semânticas

Cada rota recebe uma permission correspondente à operação real.

Operações de ciclo de vida não podem ser autorizadas através de
permissions genéricas como `view`, `update` ou `approve` quando a ação
representa cancelar, concluir, validar, gerar relatório, bloquear um
slot ou confirmar uma visita.

O catálogo final contém:

- **68** permissions únicas;
- **12** permissions reutilizadas;
- **56** permissions novas.

As abilities de backoffice usam nomes explícitos, como:

```text
viewAnyBackoffice
viewBackoffice
createBackoffice
updateBackoffice
startBackoffice
completeBackoffice
cancelBackoffice
validateBackoffice
downloadBackoffice
```

As Policies devem verificar permission, scope municipal e estado do
recurso. A presença de uma permission não substitui a Policy.

### 3. Scope municipal dos recursos operacionais

O Município é obtido através de relações autoritativas.

#### Manutenção

```text
MaintenanceRequest
-> HousingUnit
-> municipality_id

MaintenanceAssignment
-> MaintenanceRequest
-> HousingUnit
-> municipality_id

MaintenanceAttachment
-> MaintenanceRequest
-> HousingUnit
-> municipality_id

MaintenanceIntervention
-> HousingUnit e MaintenanceRequest
-> municipality_id

MaintenanceCost
-> HousingUnit e MaintenanceRequest
-> municipality_id
```

Intervenções e custos com múltiplas relações municipais só são
acessíveis quando as relações são coerentes entre si.

#### Vistorias

```text
PropertyInspection
-> HousingUnit
-> municipality_id

PropertyInspectionItem
-> PropertyInspection
-> HousingUnit
-> municipality_id

PropertyInspectionAttachment
-> PropertyInspection
-> HousingUnit
-> municipality_id

PropertyInspectionReport
-> PropertyInspection
-> HousingUnit
-> municipality_id
```

#### Visitas

```text
VisitAvailability
-> HousingUnit ou Contest
-> Program
-> municipality_id

VisitSlot
-> VisitAvailability, HousingUnit e Contest
-> municipality_id

HousingVisit
-> VisitSlot, HousingUnit, Contest e Application
-> municipality_id
```

As referências existentes devem apontar para o mesmo Município. Uma
relação ausente, incompleta ou contraditória resulta em recusa.

### 4. Catálogos de manutenção e vistoria

#### Categorias de manutenção

`maintenance_categories` adota tenancy híbrida explícita:

```text
is_system = true
municipality_id = null
-> categoria global de sistema

is_system = false
municipality_id preenchido
-> categoria municipal

is_system = false
municipality_id = null
-> registo não classificado e inacessível
```

O backfill pode marcar como sistema apenas os códigos conhecidos:

```text
plumbing
electricity
structure
equipment
```

#### Templates de vistoria

`inspection_checklist_templates` adota o mesmo modelo híbrido.

O único template que pode ser marcado automaticamente como sistema é:

```text
housing-standard-demo
```

Os items herdam o scope do respetivo template.

#### Fornecedores

`maintenance_suppliers` é exclusivamente municipal e recebe
`municipality_id`.

Um fornecedor com `municipality_id = null` fica inacessível até
regularização. Não existe backfill inferido.

### 5. Agenda

A rota `backoffice.agenda.index` utiliza:

```text
permission: agenda.view
Policy: App\Policies\AgendaPolicy
ability: viewBackoffice
```

A Agenda é um agregador e não concede acesso aos conteúdos dos seus
providers.

Cada provider deve filtrar independentemente por:

```text
permission específica do domínio
&& scope municipal
&& visibilidade do recurso
```

Um provider não autorizado não pode revelar:

- títulos;
- contagens;
- datas;
- existência de registos;
- links;
- metadados operacionais.

### 6. Operadores da plataforma

O scope global depende exclusivamente de assignment estrutural explícito
validado por `PlatformOperatorScopeService`.

É proibido inferir acesso global através de:

```text
user.municipality_id === null
```

Utilizadores sem Município e sem assignment global válido ficam
fail-closed.

### 7. Downloads privados

As seguintes rotas são dados privados críticos:

```text
backoffice.inspections.attachments.download
backoffice.inspections.reports.download
backoffice.maintenance.attachments.download
```

Todas exigem:

- permission específica de download;
- Policy e ability `downloadBackoffice`;
- scope municipal;
- MFA;
- auditoria obrigatória;
- storage privado;
- nome de ficheiro sanitizado;
- ausência de exposição de `storage_path`;
- headers seguros.

### 8. Preservação dos fluxos candidate e tenant

A migração das 96 rotas de backoffice não remove os fluxos legítimos de
candidatos e inquilinos.

As abilities candidate existentes podem permanecer separadas, desde que:

- não concedam acesso ao backoffice;
- estejam limitadas ao próprio candidato, contrato ou agregado;
- respeitem flags de visibilidade;
- não reutilizem abilities backoffice;
- não permitam mutações fora dos estados válidos.

### 9. Auditoria

Todas as mutações das rotas 47G exigem auditoria.

Os três downloads críticos também exigem auditoria, mesmo sendo
operações de leitura.

A auditoria deve incluir, quando aplicável:

```text
actor_user_id
municipality_id
route_name
permission
ability
model_type
model_id
ação ou transição
estado anterior
estado posterior
timestamp
metadados técnicos não sensíveis
```

Dados pessoais, conteúdo documental e paths internos não devem ser
copiados desnecessariamente para logs.

### 10. Form Requests

Os Form Requests identificados com `authorize(): true` devem passar a
autorizar a mesma ability utilizada pela rota e pelo controller.

Não pode existir divergência entre:

```text
route permission
Policy ability
Form Request authorize()
Service
```

### 11. Query scoping

O `MunicipalRecordScopeService` deve ser expandido com scopes e métodos
`owns...()` para os modelos abrangidos pela 47G.

Listagens, dashboards, Agenda, pesquisas e relatórios devem aplicar o
scope antes de:

- executar contagens;
- paginar;
- agregar;
- carregar relações;
- produzir exports;
- devolver dados ao frontend.

### 12. Testes obrigatórios

A implementação deve incluir:

```text
tests/Feature/Security/MaintenanceInspectionsVisitsPermissionRoutesTest.php
tests/Feature/Security/MaintenanceInspectionsVisitsMunicipalBoundaryTest.php
tests/Feature/Security/MaintenanceInspectionVisitWorkflowIntegrityTest.php
```

Os testes devem cobrir:

- ausência de permission;
- permission sem scope municipal;
- recurso de outro Município;
- utilizador sem Município;
- operador global sem assignment;
- operador global com assignment;
- relações municipais incoerentes;
- mutações em estados inválidos;
- downloads privados;
- Agenda sem fuga de metadados;
- preservação dos fluxos candidate e tenant.

## Consequências

### Positivas

- autorização granular por operação;
- isolamento municipal verificável;
- redução de acessos implícitos por role;
- maior capacidade de auditoria;
- base técnica para módulos comerciais futuros;
- menor risco de IDOR e fuga entre Municípios.

### Custos

- criação de permissions e abilities adicionais;
- expansão do serviço de scope municipal;
- migration dos três catálogos;
- atualização de Form Requests;
- atualização dos perfis municipais;
- aumento necessário da cobertura automatizada.

## Restrições de implementação

- preservar as 96 rotas do manifesto;
- não absorver as rotas previstas para a Sprint 47H;
- não reutilizar features de candidaturas;
- não introduzir direct grants;
- não introduzir bypass por role;
- não inferir operador global por `municipality_id` nulo;
- não alterar silenciosamente o universo do manifesto;
- preservar as alterações já integradas da Sprint 49.

## Classificação de entrega

O encerramento da Sprint 47G só pode receber:

```text
REPOSITORY_PASS_DEPLOYMENT_GATED
```

A aprovação do repositório não equivale a deploy em produção.
