# Sprint 53F - Revalidacao diferencial, segunda analise e fecho

## Identificacao

- Branch: `sprint-53f-differential-revalidation-second-closure`
- Commit-base: `d49c28b7ce504dde29e695db5de4117208769da2`
- Branch de origem: `sprint-53e-candidate-correction-cycle`
- Estado deste documento: relatorio final de implementacao e validacao

## Resumo executivo

A Sprint 53F fecha o ciclo iniciado pela publicacao coletiva da revisao
documental e continuado pela resposta formal do candidato. A segunda analise
sera diferencial: usa o recibo imutavel da submissao como fronteira temporal,
transporta explicitamente os elementos anteriormente validados e revê apenas
alteracoes, substituicoes, novos documentos, justificacoes ou dependencias
afetadas.

O motor existente de `ApplicationReviewBatch`,
`ApplicationReviewBatchItem`, `ApplicationReviewPublication` e
`ApplicationReviewPublicationResult` continua a ser a unica infraestrutura de
selagem e publicacao. Nao sera criado um agregado paralelo de publicacao.

## Auditoria inicial

### Dominio de lotes e publicacao

- `ApplicationReviewBatchCycle` ja contem `initial_review` e `revalidation`.
  Nao e necessario um novo valor para identificar a segunda analise.
- `ApplicationReviewBatch` e `ApplicationReviewBatchItem` sao imutaveis depois
  de criados. O lote atual nasce selado e nao possui estado de rascunho.
- A constraint `review_batches_contest_cycle_unique` limita atualmente cada
  concurso a um lote por ciclo.
- `seal_key`, `source_fingerprint` e os hashes SHA-256 sao protecoes de
  idempotencia e integridade existentes.
- `ApplicationReviewPublication` possui unicidade por lote e representa a
  publicacao coletiva oficial.
- `ApplicationReviewPublicationResult` possui um unico resultado por item de
  lote e referencias duraveis a notificacao, comunicacao e entregas.
- `ApplicationReviewPublicationService` bloqueia lote, concurso, itens,
  processo, candidatura e destinatario, valida hashes e publica tudo numa
  transacao.
- O email processual e enfileirado pelo pipeline existente depois do commit.
- `PublishedCorrectionRequestProjector` cria o primeiro pedido apenas quando o
  resultado publicado e `correction_required`. A segunda publicacao nao pode
  reutilizar esse resultado, pois originaria indevidamente um terceiro pedido.

### Dominio de aperfeicoamentos

- `CorrectionRequest` esta ligado de forma unica ao
  `ApplicationReviewPublicationResult` original e ao respetivo hash.
- Pedidos novos sem esta origem sao recusados pelo scope municipal. Pedidos
  legacy permanecem isolados e nao recebem origem por inferencia.
- `CorrectionRequestItem` identifica o requisito, instancia, alvo e documento
  de origem.
- `CorrectionResponse` ja possui `response_kind`, versao documental submetida,
  `review_result`, `reviewed_by`, `reviewed_at` e `review_notes`.
- `CorrectionSubmissionReceipt` e unico por pedido, imutavel e guarda o
  snapshot/hash da submissao formal.
- O snapshot do recibo identifica exatamente cada resposta e versao documental
  que entrou no ato formal.
- `CandidateCorrectionWorkspaceService` preserva versoes anteriores e liga a
  nova versao a `replaces_document_version_id`.
- `CorrectionResponseService` resolve hoje pedidos diretamente quando todas as
  respostas sao aceites. Esse comportamento fica reservado ao fluxo legacy;
  pedidos canonicos da 53E passam obrigatoriamente pelo ciclo 53F.

### Processo administrativo

O `AdministrativeWorkflowTransitionService` estabelece a sequencia oficial:

```text
correction_submitted
    -> correction_under_review
    -> eligibility_review
```

`eligibility_review` e a fase onde uma decisao administrativa posterior pode
conduzir a `admitted_for_scoring` ou `not_admitted`.

Conclusoes:

- aceitar todos os elementos documentais nao torna a candidatura elegivel;
- rejeitar um elemento nao exclui automaticamente a candidatura;
- os resultados favoravel e desfavoravel da revalidacao regressam a
  `eligibility_review` para decisao administrativa humana;
- `requires_manual_decision` bloqueia a selagem/publicacao final;
- nao existe regra oficial que autorize um terceiro aperfeicoamento automatico.

### Seguranca e operacao

- Permissions existentes suficientes:
  - `administrative_processes.view` para fila e detalhe;
  - `administrative_processes.update` para iniciar a revalidacao;
  - `administrative_processes.decide` para decidir itens;
  - `administrative_processes.publish` para publicar;
  - `documents.view` para consulta documental protegida.
- Nao serao criadas permissions nesta sprint.
- `CorrectionRequestPolicy`, `CorrectionResponsePolicy`,
  `ApplicationReviewBatchPolicy` e `ApplicationReviewPublicationPolicy` ja
  excluem candidatos de backoffice e mutacoes por auditores.
- `MunicipalRecordScopeService::correctionRequests()` deriva o Municipio da
  publicacao original e falha fechado sem origem ou Municipio.
- As rotas novas manterao `active.backoffice`, MFA, logging, entitlement
  `applications.review`, permission exata e Policy. Nao usarao middleware de
  role.
- `CorrectionProgressMetricsService` e
  `CorrectionRequestTimelineProvider` sao os pontos existentes para dashboard,
  Timeline e Agenda, sem queries em views.

## Matriz de estados

| Estado do pedido | Segunda analise | Acao permitida |
| --- | --- | --- |
| `notified` | bloqueada | aguardar abertura/resposta |
| `open` | bloqueada | candidato prepara respostas |
| `partially_completed` | bloqueada | candidato conclui respostas |
| `submitted` sem inicio | pronta | iniciar revalidacao |
| `submitted` em analise | ativa | decidir apenas itens reavaliaveis |
| `submitted` pronta | pronta para fecho | rever preview e selar |
| `submitted` selada | imutavel | publicar |
| `resolved` | concluida | apenas consulta |
| `expired`, `cancelled` | bloqueada | nenhuma revalidacao |

O processo transita de `correction_submitted` para
`correction_under_review` ao iniciar. Apenas a projecao da publicacao transita
para `eligibility_review` e marca o pedido como `resolved`.

## Matriz de classificacao diferencial

| Classificacao | Origem | Editavel | Tratamento |
| --- | --- | --- | --- |
| `unchanged_valid` | snapshot publicado original, nao afetado | nao | carry-forward explicito |
| `changed_document` | documento existente com versao alterada | sim | decisao municipal |
| `new_document` | nova submissao incluida no recibo | sim | decisao municipal |
| `replaced_document` | versao que referencia a anterior | sim | decisao municipal |
| `candidate_justification` | resposta textual formal | sim | decisao fundamentada |
| `dependency_affected` | requisito relacionado sem validade demonstravel | sim | decisao ou encaminhamento manual |

Versoes posteriores ao recibo ficam fora do payload. Se a versao atual do
documento divergir da versao do recibo, a fonte fica `stale` e o fecho falha.

## Matriz de decisoes por item

| Decisao | Efeito no item | Efeito no fecho |
| --- | --- | --- |
| `accepted` | resposta aceite | permite fecho |
| `rejected` | resposta nao conforme | permite resultado agregado desfavoravel |
| `not_applicable` | requisito dispensado com fundamento | permite fecho |
| `requires_manual_decision` | necessita decisao competente | bloqueia selagem/publicacao |

Itens `unchanged_valid` nao recebem decisao manual e nao podem ser alterados.
Ator, data, estado, Municipio, hash e resultado agregado sao sempre definidos
no servidor.

## Matriz de resultado agregado

| Condicao | Resultado agregado | Outcome do lote | Processo apos projecao |
| --- | --- | --- | --- |
| todos aceites/nao aplicaveis | `accepted` | `complete_pending_decision` | `eligibility_review` |
| existe pelo menos um rejeitado | `rejected` | `correction_rejected` | `eligibility_review` |
| existe decisao manual pendente | `requires_manual_decision` | nenhum lote final | permanece `correction_under_review` |
| item sem decisao ou fonte stale | incompleto | nenhum lote final | permanece `correction_under_review` |

`correction_rejected` e um resultado documental desfavoravel, nao uma decisao
de exclusao e nao cria novo pedido automaticamente.

## Ciclo existente reutilizado

1. O resolver carrega o resultado original publicado, o respetivo item
   imutavel, o pedido e o recibo.
2. A abertura muda apenas o processo para `correction_under_review` e regista o
   marco da revalidacao.
3. As decisoes transitórias usam os campos de revisao existentes em
   `CorrectionResponse` e um fingerprint da fonte.
4. O fecho cria um `ApplicationReviewBatch` final do ciclo `revalidation`, com
   um `ApplicationReviewBatchItem` para a candidatura e snapshot 53F completo.
5. O publicador existente cria uma unica publicacao, resultado, notificacao,
   comunicacao e entregas.
6. O projector 53F liga o resultado publicado ao pedido, marca a projecao,
   resolve o pedido e usa o servico oficial para regressar a
   `eligibility_review`.

O lote final e individual por pedido, mas a infraestrutura e o contrato de
publicacao permanecem os mesmos. Os lotes coletivos historicos das 53C/53D nao
sao alterados.

## Migrations previstas

Uma migration incremental e reversivel ira:

- ligar opcionalmente `application_review_batches` a
  `correction_requests`;
- substituir a unicidade global concurso/ciclo por:
  - unicidade dos lotes coletivos historicos;
  - unicidade por pedido de aperfeicoamento;
- adicionar ao pedido os marcos de inicio, resultado, resultado publicado e
  projecao;
- adicionar a `correction_responses` a classificacao diferencial e o
  fingerprint da decisao;
- criar indices de fila para estado/inicio/resultado/data;
- manter foreign keys restritivas e sem backfill inferido.

Lotes historicos recebem apenas uma chave coletiva deterministica baseada nos
campos autoritativos existentes. Nenhum lote historico e ligado retroativamente
a um pedido. O rollback recusa dados incompatíveis em vez de os eliminar.

## Scope municipal

Fonte autoritativa:

```text
CorrectionRequest
  -> ApplicationReviewPublicationResult original
  -> municipality_id
```

Cada operacao tambem valida coerencia entre pedido, recibo, candidatura,
processo, concurso, lote final, documentos e utilizador. A query da fila e
limitada por Municipio antes da paginacao. Ausencia de qualquer relacao
autoritativa falha fechado.

## Riscos e decisoes

- **Unicidade historica:** a constraint original por concurso/ciclo e
  incompatível com um lote de revalidacao por pedido. A migration preservara a
  unicidade coletiva por uma chave explicita e acrescentara unicidade por
  pedido.
- **Fluxo legacy:** respostas legacy continuam a usar a revisao direta para
  nao quebrar o fluxo historico. Pedidos com publicacao e recibo nao podem ser
  resolvidos por esse atalho.
- **Dependencias:** so serao marcadas quando derivadas de chaves de requisito e
  alvo presentes nos snapshots. Na ausencia de prova, o resolver falha fechado
  ou produz `dependency_affected`; nao inventa dependencias regulamentares.
- **Resultado desfavoravel:** nao existe estado final desfavoravel do pedido
  alem de `resolved`; o detalhe fica em `revalidation_result=rejected`. O
  processo regressa a decisao manual em `eligibility_review`.
- **Publicacao:** o novo outcome `correction_rejected` evita reutilizar
  `correction_required` e, consequentemente, evita um terceiro pedido.
- **Privacidade:** snapshots internos guardam IDs, hashes e decisoes; dashboard,
  Agenda, Timeline, logs e notificacoes nao recebem texto integral,
  filenames, OCR ou dados pessoais sensiveis.

## Ficheiros previstos

- migration incremental 53F;
- enums de classificacao e resultado agregado;
- extensao de `ApplicationReviewBatchOutcome` e
  `CorrectionResponseReviewResult`;
- relacoes/casts em `ApplicationReviewBatch`, `CorrectionRequest` e
  `CorrectionResponse`;
- DTOs em `app/Data/Administrative`;
- `CorrectionDifferentialResolver`;
- `CorrectionRevalidationSnapshotBuilder`;
- `CorrectionRevalidationService`;
- `CorrectionResolutionService`;
- `PublishedCorrectionRevalidationProjector`;
- controller e Form Requests dedicados;
- Policies e `MunicipalRecordScopeService`;
- rotas e manifesto de acesso;
- views da fila e detalhe do pedido;
- metricas, Timeline/Agenda e seeder integrado;
- testes unitarios, feature, seguranca, migration e concorrencia.

## Plano de testes

- Unitarios: classificacao, carry-forward, dependencias, fronteira temporal,
  canonicalizacao, fingerprint, snapshot/hash e agregado.
- Feature: fila, inicio, decisoes, carry-forward bloqueado, preview, selagem,
  publicacao, projecao, resolucao e notificacao unica.
- Seguranca: permission/scope independentes, Municipio cruzado, sem Municipio,
  candidato, auditor, MFA, conta/role inativa e IDs externos.
- Concorrencia: decisao, selagem, publicacao e projecao em processos MySQL
  independentes.
- Regressao: Sprints 53B-53E, documentos, processo, dashboard, Timeline, Agenda,
  auditoria de rotas e seeder integrado.
- Gates: Composer, PHPStan, Pint, PHPUnit integral, UX canonica, Vite,
  migrations SQLite/MySQL, route diff e `git diff --check`.

## Implementacao do Bloco 53F-B

O motor diferencial e a persistencia minima foram entregues no commit
`cc3e2ee9`:

- enums tipados para classificacao diferencial e resultado agregado;
- novo outcome documental `correction_rejected`, sem semantica automatica de
  exclusao;
- `CorrectionDifferentialResolver` com o recibo formal como fronteira
  temporal, carry-forward explicito e deteccao de fontes stale;
- `CorrectionRevalidationSnapshotBuilder` com schema versionado,
  canonicalizacao, fingerprint e hash SHA-256 deterministico;
- DTOs tipados do resultado e dos itens diferenciais;
- migration incremental `2026_08_01_000053`, com ligacao unica do lote ao
  pedido, chave coletiva para lotes historicos e campos de controlo da
  revalidacao/projecao;
- backfill limitado a lotes coletivos autoritativos; nenhum pedido historico
  foi inferido;
- rollback fail-closed quando existirem dados 53F que nao possam regressar ao
  contrato anterior sem perda.

A migration passou num ciclo isolado SQLite `up/down/up`. A validacao
MySQL/MariaDB temporaria e a concorrencia real ficam registadas apenas nos
gates finais.

## Implementacao do Bloco 53F-C

O workspace municipal foi integrado no detalhe canonico do pedido e numa fila
operacional dedicada. Foram implementados:

- `CorrectionRevalidationService` para fila paginada, inicio idempotente,
  decisoes por item, optimistic token, progresso e resultado agregado;
- `CorrectionResolutionService` para preview, validacao integral, locks,
  snapshot final e selagem idempotente;
- controller fino e Form Requests dedicados para fila, inicio, decisao,
  preview e selagem;
- abilities em `CorrectionRequestPolicy` e `CorrectionResponsePolicy`, sem
  bypass de administrador e com auditor/candidato excluidos das mutacoes;
- scope canonico de pedidos e respostas derivado da publicacao original, com
  acesso global apenas por assignment estrutural explicito; o fallback de
  processo/candidatura fica restrito a respostas legacy;
- cinco rotas permission-first com `auth`, conta ativa, MFA, logging,
  entitlement `applications.review`, permission exata e sem role fixa;
- fila com filtros por concurso, submissao, SLA, tecnico, estado, resultado,
  processo e candidatura, aplicando o scope antes da paginacao;
- detalhe com resultado original, recibo, carry-forward bloqueado, comparacao
  por links documentais protegidos, decisoes, progresso e estado de selagem;
- confirmacao explicita no preview antes de criar o lote imutavel;
- separacao entre selagem e publicacao: o pedido permanece `submitted` e o
  processo permanece `correction_under_review` ate ao Bloco D;
- bloqueio do atalho de revisao legacy para pedidos canonicos.

Evidencia dirigida observada antes do commit do Bloco C:

- regressao inicial 53E/legacy: 22 testes, 158 assercoes;
- testes funcionais e de seguranca 53F-C: 12 testes, 95 assercoes;
- conjunto dirigido consolidado A-C/53E/legacy: 34 testes, 249 assercoes;
- PHPStan dirigido: zero erros;
- Pint dirigido: PASS;
- `git diff --check`: PASS.

## Rotas 53F

| Rota | Metodo | Permission |
| --- | --- | --- |
| `backoffice.correction-revalidations.index` | GET | `administrative_processes.view` |
| `backoffice.correction-revalidations.start` | POST | `administrative_processes.update` |
| `backoffice.correction-revalidations.decide` | POST | `administrative_processes.decide` |
| `backoffice.correction-revalidations.preview` | POST | `administrative_processes.update` |
| `backoffice.correction-revalidations.seal` | POST | `administrative_processes.update` |

Nao foram criadas permissions novas. A publicacao continua a usar
`administrative_processes.publish` e as rotas oficiais preexistentes.

## Estado dos blocos

- 53F-A: concluido no commit `ba8f139f`.
- 53F-B: concluido no commit `cc3e2ee9`.
- 53F-C: concluido no commit `313fe2fe`.
- 53F-D: implementado e validado de forma dirigida; commit em preparacao.

## Implementacao do Bloco 53F-D

### Publicacao e projecao

O motor oficial de `ApplicationReviewPublication` continua a ser a unica via
de publicacao e notificacao. O ciclo `revalidation` passa a encaminhar o
resultado publicado para `PublishedCorrectionRevalidationProjector`, dentro da
mesma transacao atomica.

O projector:

- volta a carregar e bloquear lote, publicacao, item, resultado, pedido,
  processo e candidatura;
- aplica o scope municipal antes da projecao;
- verifica ciclo, outcome, relacoes, snapshot, fingerprint, hashes e recibo;
- aceita apenas `complete_pending_decision` ou `correction_rejected`;
- projeta `accepted` ou `rejected` no pedido sem alterar a candidatura;
- transita o processo por servico oficial de `correction_under_review` para
  `eligibility_review`;
- marca o pedido `resolved` apenas depois da publicacao bem-sucedida;
- recupera idempotentemente uma publicacao completa ja existente;
- falha fechado perante projecao parcial ou incoerente;
- emite `CorrectionRevalidationProjected` apenas depois do commit.

O outcome rejeitado significa apenas que um elemento documental nao foi
aceite. Nao exclui a candidatura, nao decide elegibilidade e nao abre um
terceiro aperfeicoamento.

### Notificacao e auditoria

A notificacao formal, comunicacao e entregas continuam a ser criadas pelo
pipeline atomico de publicacao existente. Nao foi criado um segundo motor de
notificacoes nem uma notificacao por item.

Foram consolidados os eventos de auditoria:

- `correction_revalidation_started`;
- `correction_item_reviewed`;
- `correction_revalidation_previewed`;
- `correction_revalidation_sealed`;
- `correction_revalidation_published`;
- `correction_revalidation_rejected`, quando aplicavel;
- `correction_request_resolved`;
- `correction_revalidation_projected`.

Os metadados operacionais usam IDs, resultados e hashes. Nao incluem ficheiros,
paths, OCR, texto da justificacao, NIF, morada, rendimentos ou dados bancarios.

### Dashboard, Timeline e Agenda

`CorrectionProgressMetricsService` passou a produzir agregados municipais para
pedidos submetidos, parcialmente revistos, prontos para fecho, selados,
publicados, resolvidos e rejeitados, incluindo duracao media da segunda
analise. As queries aplicam o scope antes das agregacoes e nao carregam
conteudo documental.

Nao foi encontrada uma regra autoritativa de SLA especifica para a segunda
analise. A apresentacao falha fechado com `revalidation_sla_configured=false`
e valor vencido indisponivel, sem inventar um prazo.

`CorrectionRequestTimelineProvider` produz um unico evento atual por pedido,
com marcos de submissao, inicio, prontidao, selagem, publicacao e resolucao ou
rejeicao. A Agenda reutiliza estes eventos pelo agregador existente. Nao e
gerado um evento global por documento ou por decisao individual.

### Seeder integrado e compatibilidade

`IntegratedWorkflowTestSeeder` passa pelo circuito canonico completo: resultado
inicial publicado, pedido, resposta, recibo formal, inicio, decisao, selagem,
publicacao final, projecao e resolucao. O cenario usa apenas dados ficticios
`example.test` e valida internamente o estado final.

As views de lote, preview, publicacao, pedido e resultado do candidato foram
ajustadas para distinguir `correction_rejected` de sucesso. O candidato recebe
uma mensagem explicita de que o resultado documental nao constitui exclusao
automatica.

### Evidencia dirigida do Bloco D

- regressao 53B-53F, resolver diferencial e fluxo integrado: 58 testes,
  580 assercoes, PASS; em SQLite o teste concorrente valida apenas o contrato
  de ambiente e a execucao real ocorre no gate MySQL/MariaDB;
- Dashboard e Agenda: 81 testes, 372 assercoes;
- processos, publicacao, documentos e rotas relacionadas: 34 testes,
  181 assercoes;
- concorrencia real MySQL com processos independentes: 1 teste,
  26 assercoes, PASS;
- migration SQLite `up/down/up`: PASS;
- migration MySQL `up/down/up`: PASS;
- backfill legacy, foreign key e indices unicos MySQL: PASS;
- rotas `--except-vendor`: 1188 antes, 1193 depois, cinco adicoes legitimas,
  zero remocoes e zero nomes duplicados;
- novas rotas backoffice com role fixa: zero;
- Pint incremental: PASS;
- PHPStan dirigido: zero erros;
- `git diff --check`: PASS.

## Relatorio final

### Resumo executivo

A Sprint 53F fecha o ciclo canonico de aperfeicoamento sem criar um sistema
paralelo. O recibo formal da 53E define a fronteira temporal; o motor compara
as fontes, transporta resultados validos, revê apenas alteracoes e produz um
snapshot deterministico. A selagem, publicacao, notificacao e projecao usam os
agregados oficiais existentes.

O fecho documental nunca decide elegibilidade. Tanto a aceitacao como a
rejeicao regressam o processo a `eligibility_review`, onde permanece obrigatoria
uma decisao administrativa humana. Um resultado manual pendente bloqueia a
selagem e nenhuma regra cria um terceiro aperfeicoamento automaticamente.

### Git e commits

- Commit-base: `d49c28b7ce504dde29e695db5de4117208769da2`.
- Branch: `sprint-53f-differential-revalidation-second-closure`.
- `ba8f139f` - auditoria de dominio do Bloco A.
- `cc3e2ee9` - motor diferencial, snapshots e migration do Bloco B.
- `313fe2fe` - workspace municipal, decisoes e fecho do Bloco C.
- `HEAD` - publicacao, projecao, operacao, testes e relatorio do Bloco D,
  com o subject `feat(corrections): publish and project final revalidation results`.
- Nao houve merge em `main` nem force push.

### Decisoes regulamentares

- Documento aceite nao equivale a candidatura elegivel.
- Documento rejeitado nao equivale a exclusao da candidatura.
- `requires_manual_decision` nao constitui resultado publicavel final.
- Nao foi encontrada autorizacao para abrir automaticamente um terceiro ciclo.
- O estado comprovado seguinte e `eligibility_review`, por transicao oficial.
- Nao existe SLA autoritativo especifico para segunda analise; a plataforma
  apresenta configuracao incompleta em vez de inventar um prazo.

### Arquitetura final

O fluxo implementado e:

```text
CorrectionSubmissionReceipt
    -> CorrectionDifferentialResolver
    -> CorrectionRevalidationService
    -> CorrectionResolutionService
    -> ApplicationReviewBatch(revalidation)
    -> ApplicationReviewPublicationService
    -> PublishedCorrectionRevalidationProjector
    -> CorrectionRequest(resolved)
    -> AdministrativeProcess(eligibility_review)
```

DTOs tipados separam o resultado diferencial e os seus itens. O snapshot usa
`schema_version=1`, ordenacao canonica, SHA-256, IDs estaveis, hashes da origem
e do recibo, carry-forward explicito e decisoes finais. Nao inclui objetos
serializados, URLs temporarios, binarios ou `now()` no conteudo sujeito a hash.

### Migration e persistencia

Foi criada uma unica migration incremental e reversivel:

`database/migrations/2026_08_01_000053_add_correction_revalidation_controls.php`

Alteracoes:

- ligacao unica entre lote final e pedido de aperfeicoamento;
- `collective_scope_key` para preservar a unicidade dos lotes coletivos;
- backfill deterministico apenas para lotes historicos autoritativos;
- marcos de inicio, resultado, publicacao e projecao no pedido;
- classificacao diferencial e fingerprint da decisao na resposta;
- foreign keys restritivas e indices de fila;
- rollback fail-closed quando dados 53F tornariam o contrato anterior
  impossivel sem perda.

O ciclo `up/down/up` passou em SQLite e MySQL temporarios. No MySQL foram ainda
confirmados o backfill legacy, a foreign key, os indices unicos e o bloqueio de
um lote coletivo duplicado. Os lotes sao imutaveis e nao usam SoftDeletes; os
pedidos mantem SoftDeletes sem libertar a unicidade do lote final.

### Enums e modelos

Foram criados `CorrectionRevalidationItemType` e
`CorrectionRevalidationAggregateResult`. Foram estendidos
`CorrectionResponseReviewResult` com `requires_manual_decision` e
`ApplicationReviewBatchOutcome` com `correction_rejected`.

`ApplicationReviewBatch`, `CorrectionRequest` e `CorrectionResponse` receberam
apenas relacoes, casts, propriedades e protecoes necessarias ao novo ciclo. Os
lotes selados continuam imutaveis e os caminhos legacy permanecem separados.

### Servicos

- `CorrectionDifferentialResolver`: origem, recibo, comparacao, carry-forward,
  dependencias, stale sources e fingerprint.
- `CorrectionRevalidationSnapshotBuilder`: snapshot canonico e hash estavel.
- `CorrectionRevalidationService`: fila, abertura, decisoes, tokens otimistas,
  progresso e agregado.
- `CorrectionResolutionService`: preview, locks, validacao, selagem e
  idempotencia.
- `PublishedCorrectionRevalidationProjector`: integridade, projecao,
  transicao, auditoria e evento after-commit.
- `ApplicationReviewPublicationService`: integracao atomica com publicacao,
  notificacao e recuperacao idempotente.
- `CorrectionProgressMetricsService` e
  `CorrectionRequestTimelineProvider`: operacao agregada sem PII.

### Controllers, Form Requests e rotas

`CorrectionRevalidationController` permanece fino. Foram criados Form Requests
especificos para fila, abertura, decisao, preview e selagem; todos autorizam
atraves de Policy/permission e validam apenas campos controlaveis pelo cliente.

As cinco rotas novas exigem `auth`, `active.backoffice`, `mfa.backoffice`,
`log.backoffice`, entitlement `applications.review`, permission exata e Policy.
Nao usam `role:*`.

Auditoria da colecao `--except-vendor`:

- antes: 1188;
- depois: 1193;
- adicionadas: 5;
- removidas: 0;
- nomes duplicados: 0;
- novas rotas backoffice com role fixa: 0.

O comando global `access:audit-routes` observou 1196 rotas totais, 931 com
permission middleware, 216 rotas candidate com role fixa e zero rotas
backoffice com role fixa ou guards em falta. O teste de caracterizacao foi
atualizado de 926 para 931 com base neste resultado real.

### Permissions, Policies e scope municipal

Nao foram criadas permissions. Foram reutilizadas:

- `administrative_processes.view`;
- `administrative_processes.update`;
- `administrative_processes.decide`;
- `administrative_processes.publish`;
- `documents.view`.

As abilities de `CorrectionRequestPolicy` e `CorrectionResponsePolicy`
recusam candidato e mutacoes do auditor. O scope deriva o Municipio do resultado
original publicado e falha fechado perante origem ausente, Municipio cruzado ou
assignment global inexistente. O fallback menos forte fica limitado a respostas
legacy e nao contamina o fluxo canonico.

### Concorrencia e idempotencia

Foram usados `DB::transaction()`, `lockForUpdate()` e unique constraints. Um
teste com dois processos PHP e ligacoes MySQL independentes executou em
concorrencia:

- duas aberturas;
- duas decisoes sobre o mesmo item;
- duas selagens;
- duas publicacoes;
- duas projecoes;
- duas tentativas de notificacao.

Resultado observado: um inicio auditado, uma decisao auditada, um lote, um
snapshot/hash final, uma publicacao, um resultado, uma notificacao, uma projecao
e um pedido resolvido. O teste passou com 26 assercoes.

### Backoffice e candidato

O backoffice possui fila paginada e filtros municipais, detalhe da segunda
analise, carry-forward bloqueado, comparacao por downloads protegidos, decisoes
por item, progresso, preview confirmado, selagem e ligacao para a publicacao
oficial.

O candidato consulta o resultado final publicado. Quando existe rejeicao, a UI
explica que se trata de um resultado documental e nao de exclusao automatica.
Nomes de ficheiros e notas internas nao sao projetados para o dashboard,
Timeline, Agenda ou resultado publico do candidato.

### Dashboard, Timeline e Agenda

As metricas municipais cobrem submetidos, parcialmente revistos, prontos,
selados, publicados, resolvidos, rejeitados e duracao media. O scope e aplicado
antes das agregacoes. O SLA fica explicitamente nao configurado.

A Timeline produz um evento por pedido e representa os marcos relevantes sem
ruido por item. A Agenda reutiliza o mesmo provider e apenas recebe eventos
operacionais autorizados.

### Notificacoes e auditoria

A publicacao oficial continua a criar exatamente uma notificacao,
`CommunicationLog` e entregas. O evento `CorrectionRevalidationProjected`
implementa `ShouldDispatchAfterCommit` e transporta apenas IDs, outcome e data.

As recusas nao persistem efeitos de dominio. Uma falha de projecao testada
reverte publicacao, comunicacao, notificacao e auditoria dentro da mesma
transacao.

### RGPD e seguranca

- storage e preview documental continuam privados e autorizados;
- nao existem URLs publicas ou paths internos novos;
- logs e eventos nao recebem OCR, documentos, texto integral, NIF, morada,
  rendimentos, dados bancarios, cookies, tokens ou MFA;
- dashboard, Timeline e Agenda recebem payload minimizado;
- candidato permanece fora do backoffice;
- auditor permanece read-only;
- o isolamento municipal e fail-closed.

### Performance

- scope antes da paginacao;
- fila baseada em agregados leves;
- `withCount()` para progresso sem resolver cada diferencial na lista;
- eager loading limitado ao detalhe autorizado;
- nenhuma query em Blade;
- nenhum documento binario ou OCR carregado por dashboard;
- agregacoes SQL especificas por driver para duracao media;
- limites preservados nos widgets e na Timeline.

### Testes e gates observados

- PHPUnit integral: 1525 testes, 22937 assercoes, PASS.
- UX canonica por `--filter UX`: 135 testes, 664 assercoes, PASS.
- Regressao 53B-53F/integrada: 58 testes, 580 assercoes, PASS.
- Dashboard/Agenda dirigidos: 81 testes, 372 assercoes, PASS.
- Processo/documentos/rotas dirigidos: 34 testes, 181 assercoes, PASS.
- Publicacao/integracao 53F: 7 testes, 118 assercoes, PASS no ciclo dirigido
  anterior ao fecho.
- Concorrencia real MySQL: 1 teste, 26 assercoes, PASS.
- PHPStan canonico: 0 erros.
- Pint integral: PASS.
- Pint incremental: PASS.
- Integridade de testes: 0 violacoes criticas, 0 avisos.
- Composer validate `--strict`: PASS.
- Composer audit `--locked`: sem advisories.
- Composer platform requirements: PASS.
- `php artisan optimize:clear`: PASS.
- Vite `npm run build`: PASS.
- SQLite migration `up/down/up`: PASS.
- MySQL migration `up/down/up`: PASS.
- `php artisan route:list --json --except-vendor`: PASS.
- `php artisan access:audit-routes`: PASS.
- `git diff --check`: PASS.

### Ficheiros alterados

Foram alterados 53 ficheiros, agrupados em:

- 2 DTOs, 4 enums e 1 evento;
- 3 models e 2 Policies;
- 3 controllers e 4 Form Requests;
- 13 services de dominio, scope, publicacao, metricas e Timeline;
- 1 migration e 1 seeder integrado;
- 7 views Blade e 1 componente de dashboard;
- `routes/web.php`;
- 9 ficheiros de testes/fixtures, incluindo concorrencia real;
- este relatorio e o teste de caracterizacao de rotas.

A lista exata e reproduzivel por:

```bash
git diff --name-only d49c28b7ce504dde29e695db5de4117208769da2..HEAD
```

### Riscos residuais e exclusoes

- O SLA da segunda analise necessita de fonte regulamentar/configuracao
  autoritativa antes de apresentar atrasos.
- O deploy real, backup, migracao em staging/producao e monitorizacao de queues
  nao fazem parte deste trabalho de repositorio.
- Exportacao temporal pertence a 53G.
- Gestao global de perfis pertence a 53H.
- Carga global, chaos/retry testing e observabilidade alargada pertencem a 53I.
- Nao foi implementada elegibilidade automatica, exclusao automatica, terceiro
  aperfeicoamento ou qualquer alteracao retroativa de snapshots.

### Preparacao da Sprint 53G

Os artefactos finais possuem `schema_version`, hashes, fingerprints, IDs
estaveis, referencia ao resultado original, referencia ao recibo, decisoes,
atores, timestamps UTC, carry-forward e ordem canonica. Podem ser exportados
temporalmente sem depender de views ou logs transitórios.

### Estado Git e classificacao

A arvore final foi verificada como limpa antes do push. A branch foi publicada
sem force push e sem alteracao de `main`; a igualdade entre HEAD local e remoto
foi confirmada no fecho.

Classificacao final:

```text
REPOSITORY_PASS
```
