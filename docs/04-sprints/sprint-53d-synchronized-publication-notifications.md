# Sprint 53D — Publicação coletiva e notificações sincronizadas

## Objetivo

Publicar, num único commit transacional, todos os resultados privados de um lote
selado da Sprint 53C e criar os registos duráveis de comunicação e entrega.

## Garantia temporal

- um único `published_at` é aplicado à publicação e a todos os resultados;
- nenhum candidato consulta resultados antes desse instante;
- cada candidato consulta apenas resultados associados ao próprio utilizador;
- a entrega externa de email é executada depois do commit;
- falhas de fornecedor não anulam a publicação oficial.

## Fonte de verdade

A publicação deriva exclusivamente de `ApplicationReviewBatch` e dos respetivos
`ApplicationReviewBatchItem` imutáveis. O serviço recalcula os hashes individuais
e o hash coletivo antes de publicar.

O payload privado apresentado ao candidato é minimizado. Não inclui notas
internas, documentos, checksums, nomes de ficheiros ou dados pessoais copiados
do snapshot técnico.

## Modelo

- `ApplicationReviewPublication`: cabeçalho coletivo imutável;
- `ApplicationReviewPublicationResult`: resultado privado imutável por candidatura;
- `OfficialNotification`: notificação na área pessoal;
- `CommunicationLog`: registo oficial da comunicação;
- `CommunicationDelivery`: entrega durável in-app e email.

## Idempotência e concorrência

- preview HMAC obrigatório;
- lote e concurso bloqueados com `lockForUpdate()`;
- uma publicação por lote;
- chaves e hashes únicos;
- repetição devolve a publicação existente;
- email processual usa job único, retry e `afterCommit()`.

## Limites da sprint

A Sprint 53D não:

- abre pedidos de aperfeiçoamento;
- altera documentos;
- altera `applications.status`;
- publica listas públicas;
- exporta dossiers;
- transforma `complete_pending_decision` numa decisão formal.
