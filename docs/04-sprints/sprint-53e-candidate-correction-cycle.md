# Sprint 53E — Ciclo de aperfeiçoamento pelo candidato

## Bloco A — Projeção do resultado publicado

Base: `dcd640b23b0061f8d55eeae706abb15dae16402f`.

Este bloco liga o pedido de aperfeiçoamento ao resultado individual publicado
pela Sprint 53D. `ApplicationReviewPublicationResult` e
`ApplicationReviewBatchItem` permanecem imutáveis. O pedido é uma projeção
idempotente do snapshot comunicado.

## Schema

`correction_requests` passa a guardar:

- origem única em `application_review_publication_result_id`;
- `source_snapshot_hash`;
- datas canónicas de notificação, abertura, submissão, expiração e resolução;
- índice de estado/prazo.

A tabela `correction_request_sequences` reserva números humanos por Município e
ano através de lock transacional. Não existe backfill inferido para pedidos
legacy.

## Estados

Estados canónicos:

- `notified`;
- `open`;
- `partially_completed`;
- `submitted`;
- `expired`;
- `cancelled`;
- `resolved`.

A leitura de estados legacy usa um cast explícito. Estados ambíguos, como
`draft` e `rejected`, falham fechado e exigem regularização operacional.

O estado principal de `applications` não é alterado pelo ciclo de correção.

## Relação com 53C e 53D

Novos snapshots de lote incluem `findings` documentais estruturados produzidos
a partir da checklist do momento da selagem. Apenas documentos obrigatórios em
falta, rejeitados ou expirados originam itens de aperfeiçoamento. Documentos
validados não são novamente pedidos.

Durante a publicação coletiva, cada resultado `correction_required` com
`next_action=await_correction_request` projeta exatamente um pedido. Falhas de
integridade, ausência da fase de aperfeiçoamento ou ausência de achados seguros
fazem rollback da publicação.

O prazo é resolvido exclusivamente por `ContestApplicationPhaseService` e pelo
prazo `ContestDeadlineType::Corrections`.

## Compatibilidade legacy

Pedidos sem `application_review_publication_result_id` são considerados legacy
e não ganham visibilidade do candidato por simples inferência.

Durante a transição incremental, mantém-se compatibilidade apenas para pedidos
legacy explicitamente emitidos pelo serviço municipal existente. A visibilidade
exige cumulativamente:

- `candidate_visible=true`;
- `issued_at`, `notified_at` e `opened_at` preenchidos;
- candidatura, candidato e processo administrativo coerentes entre si;
- pedido não cancelado;
- instante de emissão e notificação não futuro.

Pedidos legacy em rascunho, órfãos, incoerentes ou sem metadados de emissão
continuam fail-closed. Não existe backfill baseado em email, role, Município
nulo ou estado atual mutável da candidatura. Novos pedidos provenientes da
publicação coletiva continuam obrigatoriamente ligados ao resultado 53D.

## Fora do Bloco A

Ficam reservados aos blocos seguintes:

- workspace e upload versionado;
- submissão formal e recibo;
- prorrogações e expiração operacional;
- métricas agregadas;
- revalidação diferencial, segundo lote e segunda publicação.
