# Sprint 53C — Lotes de revisão e snapshots imutáveis

## Objetivo

Criar a fronteira formal entre a análise progressiva e a futura publicação coletiva.

A sprint permite fechar simultaneamente todos os processos de um concurso num ciclo de:

- validação inicial;
- revalidação após aperfeiçoamento.

Nenhum lote desta sprint publica resultados ou envia notificações.

## Modelo de domínio

Foram introduzidos:

- `ApplicationReviewBatch` — cabeçalho do lote selado;
- `ApplicationReviewBatchItem` — snapshot imutável por candidatura;
- `ApplicationReviewBatchCycle` — validação inicial ou revalidação;
- `ApplicationReviewBatchOutcome` — resultado técnico congelado;
- `ApplicationReviewBatchStatus` — estado do lote.

Cada concurso pode possuir, no máximo, um lote por ciclo. O lote abrange obrigatoriamente todos os processos do concurso, evitando fechos parciais incompatíveis com a futura publicação sincronizada.

## Resultados técnicos

O lote pode congelar os seguintes resultados:

- `complete_pending_decision`;
- `correction_required`;
- `withdrawn`;
- `not_assessed`.

`complete_pending_decision` não significa admissão, elegibilidade, classificação ou atribuição. Significa apenas que a revisão documental terminou sem bloqueios documentais.

## Regras de fecho

- documentos `submitted` ou `under_review` bloqueiam o lote;
- documentos em falta, rejeitados ou expirados originam `correction_required`;
- uma candidatura conforme exige `ApplicationReview::ReadyForClosure`;
- desistências e processos cancelados/arquivados ficam preservados no snapshot;
- a revalidação só pode ser selada após existir o lote inicial;
- a seleção parcial de processos é recusada.

## Concorrência e idempotência

A pré-visualização produz um token HMAC que incorpora:

- ator;
- concurso;
- ciclo;
- fundamento;
- processos;
- estado integral persistido das candidaturas, análises e documentos;
- hashes dos snapshots.

No selamento, os registos são recarregados com `lockForUpdate()`, os hashes são recalculados e o token é comparado dentro da transação.

A operação usa:

- lock do concurso para serializar a sequência;
- `seal_key` único para retries do mesmo pedido;
- `source_fingerprint` único para impedir duplicação do mesmo estado;
- repetição transacional controlada para deadlocks.

## Imutabilidade

Os itens não podem ser atualizados nem eliminados através do Eloquent.

O cabeçalho não permite alterar:

- Município;
- concurso;
- ciclo;
- sequência;
- fundamento;
- contagens;
- hashes;
- ator/data de selamento.

Uma evolução posterior pode alterar apenas metadata de ciclo de vida expressamente permitida, como a marcação de um lote substituído.

## Conteúdo do snapshot

Cada item preserva:

- IDs técnicos e referências públicas;
- estado do processo e candidatura;
- resultado técnico do ciclo;
- estado final da análise;
- prontidão e blockers;
- documentos e respetivo contexto funcional;
- checksums e versões;
- última decisão documental;
- hash SHA-256 individual.

Não são guardados ficheiros, conteúdo documental, nomes de ficheiro ou dados pessoais do candidato no snapshot técnico.

## Segurança

As cinco rotas são permission-first e exigem:

- `active.backoffice`;
- MFA;
- logging;
- entitlement `applications.review`;
- permissions `administrative_processes.view` ou `administrative_processes.update`;
- Policy;
- scope municipal fail-closed.

Candidatos e auditores não podem selar lotes.

## Auditoria

São registados:

- lote selado;
- conclusão de cada análise no lote;
- ciclo, sequência, concurso, outcomes e hashes.

## Fora de âmbito

A Sprint 53C não:

- publica o lote;
- altera o estado público da candidatura;
- envia email, SMS ou notificação interna;
- abre pedidos de aperfeiçoamento;
- gera exportações;
- substitui snapshots anteriores.

Estas responsabilidades pertencem às sprints 53D a 53G.
