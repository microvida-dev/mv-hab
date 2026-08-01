# Sprint 53F - Revalidacao diferencial, segunda analise e fecho

## Identificacao

- Branch: `sprint-53f-differential-revalidation-second-closure`
- Commit-base: `d49c28b7ce504dde29e695db5de4117208769da2`
- Branch de origem: `sprint-53e-candidate-correction-cycle`
- Estado deste documento: auditoria inicial do Bloco 53F-A

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

## Estado dos blocos

- 53F-A: auditoria concluida; commit pendente.
- 53F-B: pendente.
- 53F-C: pendente.
- 53F-D: pendente.

## Classificacao provisoria

Nao atribuida antes da implementacao e dos gates finais.
