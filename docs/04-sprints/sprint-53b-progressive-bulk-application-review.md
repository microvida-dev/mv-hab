# Sprint 53B — Análise progressiva em bloco

## Objetivo

Disponibilizar uma mesa de análise por concurso que permita:

- acompanhar processos e documentos de forma agregada;
- atribuir vários processos ao mesmo analista;
- aplicar decisões documentais homogéneas em bloco;
- marcar candidaturas prontas para fecho;
- reabrir a análise antes da publicação coletiva;
- manter todas as decisões como rascunho técnico;
- impedir notificações individuais durante a análise progressiva.

## Decisões arquiteturais

### Estado canónico

- `AdministrativeProcess::assigned_to` continua a ser a atribuição canónica do analista.
- `ApplicationReview` com `review_type=documental` representa a análise documental progressiva.
- `ApplicationReviewStatus::ReadyForClosure` é um estado reversível, não publicado e sem resultado administrativo final.
- `DocumentSubmission` e `DocumentReview` continuam a ser a origem das decisões documentais.
- `DocumentChecklistService` continua a resolver requisitos obrigatórios e documentos em falta.

### Fronteira de publicação

A Sprint 53B não publica resultados e não cria notificações. A publicação coletiva será introduzida pelas Sprints 53C e 53D.

`ready_for_closure` significa apenas que a candidatura foi tecnicamente preparada para integrar um futuro lote selado. O campo `result` permanece nulo nesta fase.

### Concorrência e confirmação

Toda a operação em bloco exige uma pré-visualização. O token HMAC da pré-visualização inclui:

- concurso e ator;
- ação e fundamento;
- processos e documentos;
- estados e timestamps;
- analista selecionado;
- versão otimista da análise.

Na confirmação, processos, documentos selecionados, documentos que influenciam a prontidão e analista são novamente carregados com `lockForUpdate()`. O token é recalculado dentro da transação. Qualquer alteração entre pré-visualização e confirmação invalida a operação.

As mutações do lote são executadas numa única transação com repetição controlada em caso de deadlock.

## Operações suportadas

- atribuir analista elegível;
- colocar documentos submetidos em análise;
- validar documentos submetidos ou em análise;
- rejeitar documentos submetidos ou em análise, com fundamento comum;
- marcar processos prontos para fecho;
- reabrir análises com fundamento obrigatório.

Transições documentais incompatíveis são apresentadas como bloqueios na pré-visualização e não produzem alterações parciais.

Os limites por operação são:

- 200 processos;
- 500 documentos.

## Mesa de trabalho

A nova área apresenta:

- seleção de concurso;
- pesquisa por processo, candidatura, candidato ou email;
- filtros por estado do processo, análise, analista e prontidão;
- métricas agregadas;
- documentos agrupados por processo;
- seleção múltipla;
- pré-visualização obrigatória;
- confirmação explícita.

A paginação é executada no servidor e o carregamento usa eager loading e contagens agregadas.

## Segurança

- rotas permission-first;
- `active.backoffice`;
- MFA;
- logging de backoffice;
- entitlement `applications.review`;
- Policies por processo e documento;
- isolamento por `MunicipalRecordScopeService`;
- consulta documental exige `documents.view`;
- atribuição exige `administrative_processes.assign`;
- candidatos e auditores não podem executar decisões;
- o analista atribuído deve ter acesso a processos e documentos;
- nenhuma seleção de outro Município é aceite;
- identificadores inválidos ou manipulados não são descartados silenciosamente: a validação rejeita o pedido.

## Auditoria

As transições reutilizam os serviços existentes:

- `AdministrativeProcessService`;
- `DocumentReviewService`;
- `AuditLogger`.

A criação do rascunho progressivo, prontidão, reabertura manual e reabertura automática por nova atividade documental geram eventos próprios.

## Persistência

A migration incremental adiciona a `application_reviews`:

- `ready_for_closure_at`;
- `ready_for_closure_by`;
- `last_activity_at`;
- `lock_version`.

Também adiciona índices compostos para concurso, analista, estado, candidatura e documentos. A migration é reversível e preserva dados históricos.

## Rotas

- `backoffice.application-review-workspace.index`
- `backoffice.application-review-workspace.show`
- `backoffice.application-review-workspace.preview`
- `backoffice.application-review-workspace.apply`

## Critérios de aceitação

1. A pré-visualização não altera dados.
2. Uma seleção desatualizada é rejeitada.
3. Operações em bloco respeitam Policies e Município.
4. Validar documentos não notifica o candidato.
5. Uma candidatura só fica pronta quando todos os requisitos obrigatórios estão validados.
6. O estado de prontidão pode ser reaberto mediante fundamento.
7. Uma nova atividade documental reabre automaticamente uma análise pronta.
8. Transições documentais inválidas são bloqueadas antes da mutação.
9. Alterações documentais concorrentes invalidam a confirmação de prontidão.
10. Nenhuma análise é concluída ou publicada nesta sprint.
