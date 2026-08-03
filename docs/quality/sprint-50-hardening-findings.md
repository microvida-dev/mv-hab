# Sprint 50 — Findings de hardening regulamentar e de preferências

## 1. Âmbito e método

Esta auditoria precede qualquer alteração funcional das Sprints 50A.1 e
50E.1. A análise foi efetuada sobre:

- branch `sprint-50a1-regulatory-hardening`;
- commit-base `0e1761a6dd4cdb737e5a1f0f8d95aa9e92b688e7`;
- schema MySQL/MariaDB local, incluindo índices reais;
- código e testes do commit final publicado da Sprint 50E;
- fontes existentes em `docs/00-fontes`;
- relatórios históricos das Sprints 50A e 50E.

O baseline integral ficou verde antes desta auditoria. A contagem
determinística de `route:list --json --except-vendor` é **1 167 rotas**. O
ficheiro de evidência local é `/tmp/routes-before-hardening.json`, com SHA-256
`8ffba3746d3dc77fb68ca7a5ef8815e30acfa30449f8816002c83c7c9444d87b`.

Os estados usados abaixo são os definidos no master prompt:
`CONFIRMED`, `NOT_REPRODUCED`, `ALREADY_SAFE`, `REQUIRES_CHANGE` e
`BLOCKED_BY_MISSING_SOURCE`.

## 2. Matriz de findings

| Finding | Evidência | Ficheiro/linha | Estado | Risco | Correção |
| --- | --- | --- | --- | --- | --- |
| 1. Limite do 6.º escalão do IRS não está demonstrado no Quadro I | Os dois consumidores calculam apenas `38 632 + 10 000 + 5 000 por pessoa adicional`. Não existem `tax_year`, limite do 6.º escalão nem fonte fiscal tipada. | `app/Services/Eligibility/EligibilityDataProvider.php:398`; `app/Services/Eligibility/EligibilityDataProvider.php:407`; `app/Services/Applications/HousingCompatibilityService.php:594`; `app/Models/AffordableRentRegulatoryProfile.php:18`; `database/migrations/2026_07_27_000041_create_affordable_rent_regulatory_layer.php:11` | `REQUIRES_CHANGE` | O limite efetivo pode exceder o teto fiscal aplicável e os dois motores podem divergir em futuras alterações. | Introduzir parâmetros fiscais tipados e versionados, calculador único baseado em `DecimalMoney` e estado `configuration_incomplete` quando o valor oficial faltar. |
| 2. Completude da tabela PAA de rendas não tem prova documental suficiente | O provider confia em `rent_limits_configured`. O seeder marca PAA como completo, mas a única fonte local apenas remete para portarias e não contém tabela nacional, cobertura, versão verificável ou manifesto. Os valores demo de Alcanena são `320,00–470,00 EUR`. | `app/Services/Regulatory/RentLimits/PaaRentLimitProvider.php:20`; `database/seeders/AffordableRentRegulatoryProfileSeeder.php:25`; `database/seeders/DemoAlcanenaAffordableRentSeeder.php:993`; `docs/00-fontes/regime-arrendamento-acessivel-alcanena.pdf`, artigo 10.º | `BLOCKED_BY_MISSING_SOURCE` | Uma tabela demo ou um booleano administrativo pode ser interpretado como prova de uma tabela oficial nacional. Publicações PAA podem ser autorizadas sem proveniência demonstrável. | Não inventar valores. Manter PAA fail-closed, criar manifesto verificável e comando read-only, e só desbloquear após entrega da fonte oficial aplicável. |
| 3. Contratos legacy não têm inventário operacional read-only | Não existe comando `regulatory:inventory-legacy-contracts`. O resolver devolve apenas `resolved` ou revisão manual e não produz classificação determinística em lote. | `app/Console/Commands`; `app/Services/Regulatory/AffordableRentLegalRegimeResolver.php:177` | `REQUIRES_CHANGE` | Não é possível medir o volume e as razões de ambiguidade antes de qualquer migração operacional. | Criar inventário estritamente read-only, por IDs técnicos e razões, seguindo contrato → cálculo → candidatura/atribuição → concurso/programa → snapshot/perfil. |
| 4. Publicação regulamentar pode não ser totalmente atómica sob concorrência | Programa/concurso, snapshot, publicação e auditoria já estão na mesma transação e a entidade de origem usa `lockForUpdate`. Contudo, o perfil e os rule sets são carregados sem lock/revalidação consistente, pelo que podem mudar entre readiness e snapshot. | `app/Services/Programs/ProgramService.php:125`; `app/Services/Contests/ContestService.php:135`; `app/Services/Regulatory/RegulatoryPublicationReadinessService.php:26`; `app/Services/Regulatory/RegulatorySnapshotService.php:35` | `REQUIRES_CHANGE` | O snapshot pode fixar uma combinação diferente daquela que foi validada, ou dois pedidos podem competir sem contrato explícito de idempotência. | Bloquear e reler perfil/rule sets dentro da transação, revalidar depois dos locks e testar rollback, concorrência e auditoria apenas após commit lógico. |
| 5. Snapshots regulamentares podem não ter unicidade estrutural suficiente | O schema real tem unique `(source_type, source_id, context)`, FKs restritivas e o model bloqueia update/delete. A base local não contém duplicados. | `database/migrations/2026_07_27_000041_create_affordable_rent_regulatory_layer.php:55`; `app/Models/RegulatorySnapshot.php:48`; índice MySQL `regulatory_snapshot_source_context_unique` | `ALREADY_SAFE` | O contrato estrutural já existe. Permanece apenas um risco de exceção em corrida no padrão check-then-create do service. | Não criar nova constraint. Reforçar tratamento idempotente de duplicate key e cobertura de concorrência sem alterar snapshots emitidos. |
| 6. SoftDeletes pode colidir com índices únicos de `housing_preferences` | Os índices únicos não incluem `deleted_at`. Em MySQL real, após soft-delete, reutilizar a mesma candidatura/ordem/unidade falhou com SQLSTATE `23000`, erro `1062`. O `replace()` atual evita-o apenas porque usa `forceDelete()` nos rascunhos ativos. | `database/migrations/2026_06_12_010000_create_allocation_tables.php:71`; `app/Models/HousingPreference.php:29`; `app/Services/Allocation/HousingPreferenceService.php:109` | `REQUIRES_CHANGE` | Outros caminhos que usem `delete()` impedem reutilização de posição/unidade; o comportamento diverge entre paths e pode bloquear edição do rascunho. | Definir ciclo de vida de rascunho compatível com MySQL/SQLite e provar guardar/remover/reutilizar/reordenar repetidamente sem perda do histórico administrativo final. |
| 7. Fallback legacy pode reativar preferências antigas | Os readers escolhem a fonte oficial apenas quando a coleção não está vazia. Uma coleção oficial deliberadamente vazia volta automaticamente a `application_preferences`. | `app/Services/Applications/HousingPreferenceSnapshotService.php:13`; `app/Services/ProcedureMinutes/ProcedureMinutePayloadBuilder.php:419`; `app/Services/Eligibility/EligibilityDataProvider.php:63` | `REQUIRES_CHANGE` | A remoção explícita de escolhas oficiais pode ressuscitar opções legacy e influenciar elegibilidade, atas, dossier ou atribuição. | Persistir estado estrutural da fonte e centralizar a resolução; depois de `official`, nunca regressar implicitamente a `legacy`. |
| 8. `firstOrCreate()` pode cristalizar snapshots demasiado cedo | A produção chama `ApplicationSnapshotService::create()` após lock, revalidação e mudança para `submitted`; previews atuais usam DTO/payload em memória. Porém, o service é público, não confirma estado submetido, não abre/impõe transação própria e usa `firstOrCreate()` por tipo. | `app/Services/Applications/ApplicationSubmissionService.php:35`; `app/Services/Applications/ApplicationSnapshotService.php:31`; `app/Services/Applications/ApplicationSnapshotService.php:192`; `app/Services/DocumentStandardization/DocumentDossierBuilder.php:33` | `REQUIRES_CHANGE` | Uma chamada fora do fluxo principal pode criar o snapshot final num rascunho e congelar dados preliminares. | Impor ciclo final no service: candidatura submetida, lock, transação, criação única/idempotente e proibição de preview persistente. |
| 9. `HousingPreferencePolicy` depende de role fixa | `view()` e `update()` usam diretamente `hasRole('candidate')`; não existe `User::isCandidate()` nem boundary estrutural reutilizada nesta policy. | `app/Policies/HousingPreferencePolicy.php:20`; `routes/web.php:328`; `app/Policies/ApplicationPolicy.php:23` | `REQUIRES_CHANGE` | Roles múltiplas ou atendimento assistido podem produzir autorização excessiva ou bloqueio indevido. | Centralizar a fronteira candidate e combinar ownership, ability, estado editável, lock e ausência de atribuição. |
| 10. Invalidação não cobre todos os writers | A invalidação é chamada manualmente apenas pelos quatro services do portal do candidato. O controller backoffice de agregados escreve diretamente sem invalidar; não existe event/listener transversal. | `app/Services/Allocation/HousingPreferenceInvalidationService.php:14`; `app/Services/Candidate/HouseholdService.php`; `app/Services/Candidate/HouseholdMemberService.php`; `app/Services/Candidate/IncomeService.php`; `app/Services/Candidate/HousingSituationService.php`; `app/Http/Controllers/HouseholdController.php:52` | `REQUIRES_CHANGE` | Uma alteração por backoffice, correção, import ou job pode deixar compatibilidades antigas marcadas como válidas. | Inventariar writers e adotar mecanismo de domínio transversal e idempotente, sem apagar preferências nem tocar em snapshots bloqueados. |
| 11. Evidência do número de rotas é inconsistente | O relatório 50A regista “1 171 linhas de saída”; o 50E regista “1 167 rotas” sem evidência JSON preservada. A contagem corretiva real por JSON é 1 167. | `docs/04-sprints/sprint-50a-paa-rsaa-legal-transition-report.md:321`; `docs/04-sprints/sprint-50e-compatible-housing-selection-report.md:456`; `/tmp/routes-before-hardening.json` | `REQUIRES_CHANGE` | Evidência textual não permite distinguir rotas de linhas formatadas nem comparar contratos HTTP. | Registar JSON antes/depois, contar o array e comparar nome, URI, métodos e middleware. |
| 12. Evidência Git final está incompleta | Os relatórios indicam branch/base, mas não registam conjuntamente HEAD local, HEAD remoto, ahead/behind e working tree final. | `docs/04-sprints/sprint-50a-paa-rsaa-legal-transition-report.md:19`; `docs/04-sprints/sprint-50e-compatible-housing-selection-report.md:20` | `REQUIRES_CHANGE` | Não é possível provar apenas pelo relatório que o artefacto testado coincide com o publicado. | Adicionar evidência Git determinística no fecho de 50A.1 e 50E.1. |

## 3. Evidência regulamentar disponível

Os ficheiros jurídicos presentes são:

| Ficheiro | SHA-256 | Conteúdo relevante |
| --- | --- | --- |
| `docs/00-fontes/regime-arrendamento-acessivel-alcanena.pdf` | `1f06229bdd265887af41a80f63611cc67a4655c1a9c579f566fd1d8207b2f7d8` | Regulamento municipal; remete os limites de renda para a Portaria n.º 176/2019, alterada pela Portaria n.º 53/2024. Não contém a tabela nacional integral. |
| `docs/00-fontes/manual-concursos-habitacao-acessivel.pdf` | `5dfa41ea4e69946d5a236bb8e7424b175e625f77072ec75299bf19410b4376f4` | Manual funcional, não manifesto oficial da tabela nacional PAA. |
| `docs/00-fontes/requisitos-plataforma.pdf` | `3c2702604dd4d58d27fca818c87d2f798fa9134dc73ea4b6320991f0ed31bd81` | Requisitos de produto; não fonte normativa dos valores. |

Não foi encontrado no repositório:

- documento oficial com todas as linhas da tabela PAA aplicável;
- cobertura por Município e tipologia;
- versão e período de vigência verificáveis da tabela carregada;
- checksum de origem ligado aos `rent_rule_sets`/`rent_rules`;
- valor oficial aplicável ao limite superior do 6.º escalão do IRS com ano
  fiscal e vigência.

## 4. Evidência de schema MySQL

Auditoria read-only observada no ambiente local:

- `housing_preferences`:
  - unique `hp_application_order_unique`
    (`application_id`, `preference_order`);
  - unique `hp_application_chu_unique`
    (`application_id`, `contest_housing_unit_id`);
  - nenhum dos índices inclui `deleted_at`;
  - reprodução transacional e revertida: reutilização após soft-delete devolveu
    SQLSTATE `23000`, driver `1062`.
- `application_snapshots`:
  - unique `application_snapshots_type_unique`
    (`application_id`, `snapshot_type`);
  - zero duplicados observados localmente.
- `regulatory_snapshots`:
  - unique `regulatory_snapshot_source_context_unique`
    (`source_type`, `source_id`, `context`);
  - zero duplicados observados localmente.

As tabelas auditadas estavam vazias no ambiente local depois do baseline de
testes. Esta observação não representa produção e não permite concluir que não
existem dados históricos noutros ambientes.

## 5. Decisão e gate obrigatório

O finding 2 satisfaz diretamente dois critérios de paragem do master prompt:

- **fonte jurídica oficial em falta**;
- **tabela PAA não comprovável**.

Por isso:

1. não serão inventados valores, linhas, checksums ou cobertura;
2. não será declarada a tabela demo como oficial;
3. não será iniciada alteração funcional da Sprint 50A.1 sem decisão sobre a
   fonte oficial;
4. a Sprint 50E.1 não pode começar antes de a 50A.1 estar implementada,
   validada e publicada.

Classificação atual: **BLOCKED**.

## 6. Decisão posterior e auditoria inicial 50E.1

### 6.1. Decisão de continuação

O bloqueio regulamentar acima foi reavaliado após decisão formal do titular
do produto. A continuação foi autorizada exclusivamente em modo
**fail-closed**, sem instalar, inferir ou tratar como oficiais valores PAA,
RSAA ou fiscais não suportados por fonte verificada.

A Sprint 50A.1 foi concluída e publicada na branch
`sprint-50a1-regulatory-hardening`, commit
`1dc512b3d45a0dfc8cbe36d30f45a76caa222a96`. Os gates regulamentares
continuam fechados quando a fonte aplicável está ausente ou incompleta.

### 6.2. Evidência 50E.1 antes das alterações

A auditoria da 50E.1 foi executada na branch
`sprint-50e1-preference-integrity-hardening`, criada diretamente a partir do
commit remoto final da 50A.1.

| Finding | Evidência | Estado | Risco | Decisão técnica |
| --- | --- | --- | --- | --- |
| SoftDeletes e índices únicos | `housing_preferences` mantém `SoftDeletes`, mas os índices únicos são `(application_id, preference_order)` e `(application_id, contest_housing_unit_id)`, sem `deleted_at`. O `replace()` elimina apenas linhas ativas desbloqueadas; linhas antigas já soft-deleted continuam a colidir. | `REQUIRES_CHANGE` | Edições repetidas de rascunho podem falhar com duplicate key em MySQL/MariaDB. | Eliminar definitivamente, sob lock e apenas em candidatura rascunho sem atribuição/snapshot final, todas as linhas desbloqueadas dessa candidatura antes da substituição. A história administrativa final permanece no snapshot e auditoria. |
| Fonte oficial versus legacy | `HousingPreferenceSnapshotService`, `EligibilityDataProvider` e `ProcedureMinutePayloadBuilder` escolhem legacy quando a coleção oficial está vazia. | `REQUIRES_CHANGE` | Uma remoção oficial deliberada pode reativar preferências antigas. | Persistir estado estrutural `uninitialized`, `legacy`, `official`, `reconciled` ou `requires_manual_review` e centralizar todos os readers num resolver fail-closed. |
| Ciclo de snapshots | Existe unique estrutural em `application_snapshots`, mas `ApplicationSnapshotService::create()` aceita rascunhos, não impõe transação própria nem lock da candidatura e usa `firstOrCreate()`. | `REQUIRES_CHANGE` para o service; `ALREADY_SAFE` para a constraint | Uma chamada fora da submissão pode cristalizar dados preliminares. | Exigir candidatura submetida/bloqueada, usar transação e `lockForUpdate`, criar apenas tipos em falta e devolver de forma idempotente o conjunto final já existente. |
| Policy | `HousingPreferencePolicy::update()` repete `hasRole('candidate')`, embora `ApplicationPolicy::update()` já centralize ownership, estado editável e permission. | `REQUIRES_CHANGE` | Roles múltiplas e futuros fluxos assistidos ficam dependentes de uma string dispersa. | Delegar a fronteira da candidatura a `ApplicationPolicy`, mantendo ownership, permission, rascunho, ausência de lock e de atribuição. O middleware candidate continua inalterado. |
| Invalidação transversal | Os quatro services do portal invalidam manualmente. `HouseholdController` no backoffice escreve diretamente e não invalida; não existe evento de domínio comum. | `REQUIRES_CHANGE` | Alterações válidas por outro writer podem deixar compatibilidade obsoleta. | Introduzir eventos de domínio síncronos e um listener único, disparados pelos writers validados. A invalidação continua idempotente, limitada a rascunhos e sem tocar em snapshots finais. |
| Atribuição estrita | `PreferenceAllocationService` valida lock, submissão, compatibilidade, invalidação e `regulatory_snapshot_id`, e não faz fallback para unidade não escolhida. Não confirma ainda a fonte estrutural nem a existência do snapshot final. | `REQUIRES_CHANGE` | Linhas formalmente válidas, mas não oficiais ou sem snapshot final, podem ser consumidas. | Exigir fonte `official`/`reconciled`, snapshot final `housing_preferences`, ordem consecutiva e snapshot regulamentar coincidente antes da alocação. |

### 6.3. Restrições da correção

- não apagar `application_preferences`;
- não efetuar backfill ambíguo;
- não alterar snapshots administrativos já emitidos;
- não introduzir um terceiro sistema de preferências;
- não usar índices parciais ou expressões específicas de PostgreSQL;
- suportar MySQL/MariaDB e SQLite;
- manter atribuição estritamente pela 1.ª, 2.ª ou 3.ª escolha, caso contrário
  reserva.

Classificação da auditoria 50E.1: **REQUIRES_CHANGE**.
