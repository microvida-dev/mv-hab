# Programa 53 - Matriz de falhas e recuperacao

| Dominio/checkpoint | Falha | Codigo | Retry | Estado seguro | Recuperacao | Evidencia |
|---|---|---|---|---|---|---|
| batch.before_lock | DB indisponivel | database_unavailable | sim | sem lote | retry com backoff | metrica/log |
| batch.after_snapshot | worker termina | worker_interrupted | sim | snapshot sem publicacao | reconstruir/validar fonte | hash/audit |
| batch.before_commit | deadlock | database_deadlock | sim | rollback integral | retry transacional | retry count |
| publication.after_header | worker termina | worker_interrupted | sim | rollback integral | repetir token | uma publicacao |
| publication.after_result | excecao | database_transaction_failed | sim | rollback integral | repetir publicacao | zero parcial |
| publication.before_commit | deadlock | database_deadlock | sim | rollback integral | retry maximo limitado | deadlock metric |
| notification.after_delivery | provider falha | delivery_failed | sim | entrega falhada persistida | retry so da entrega | attempts |
| notification.delivered | job duplicado | duplicate_delivery | nao | entregue imutavel | noop idempotente | uma entrega |
| correction.before_submit | conflito de versao | stale_source | nao | draft preservado | nova leitura/submissao | conflito auditado |
| correction.after_receipt | worker termina | worker_interrupted | sim | recibo imutavel | retomar projecao | receipt hash |
| correction.revalidation | documento substituido | stale_source | nao | snapshot anterior preservado | nova analise | version IDs |
| export.before_snapshot | DB indisponivel | database_unavailable | sim | pending/failed controlado | retry | failure code |
| export.during_snapshot | worker termina | worker_interrupted | sim | NDJSON parcial privado | eliminar ou validar e retomar | checksum |
| export.after_snapshot | worker termina | worker_interrupted | sim | NDJSON completo privado | reutilizar se fingerprint/hash validos | reuse metric |
| export.during_writer | storage falha | storage_unavailable | sim | sem pacote final | limpar writer e repetir | staging scan |
| export.after_package | pacote corrompido | package_corrupted | nao | pacote nao publicado | eliminar e nova operacao | package hash |
| export.before_publish | move falha | storage_unavailable | sim | `.partial` privado | repetir move/build | no final path |
| export.completed | job duplicado | already_completed | nao | artefacto imutavel | noop | um hash |
| export.expired | retry tardio | already_expired | nao | ficheiro ausente | noop | status expired |
| retention.before_delete | download recente | download_in_progress | sim | completed preservado | adiar >= 5 min | downloaded_at |
| retention.after_delete | DB falha | database_unavailable | sim | ficheiro pode estar ausente | reconciliar e marcar expired | health finding |
| retention.after_status | delete falha | storage_unavailable | sim | orphan privado | orphan cleanup | warning |
| cache.lock | cache indisponivel | cache_unavailable | sim | DB continua fonte | fallback DB/abortar coordenacao | health warning |
| scheduler | execucao duplicada | duplicate_schedule | nao | transicoes idempotentes | noop por locks/jobs unicos | uma transicao |
| authorization | permissao revogada | authorization_revoked | nao | sem efeito | nova autorizacao requerida | access audit |
| schema | payload invalido | schema_invalid | nao | sem pacote final | corrigir codigo/schema | validator |

## Taxonomia

- Retryable: `database_unavailable`, `database_deadlock`,
  `storage_unavailable`, `cache_unavailable`, `worker_interrupted`,
  `delivery_failed`, `download_in_progress`.
- Terminal: `stale_source`, `authorization_revoked`, `schema_invalid`,
  `package_corrupted`, `already_completed`, `already_expired`,
  `duplicate_delivery`, `duplicate_schedule`.

## Regras invariantes

1. Fault injection nunca esta acessivel por HTTP nem ativa em producao.
2. Cada checkpoint ocorre antes/depois de uma fronteira verificavel.
3. O retry repete IDs minimos e volta a autorizar/scope quando aplicavel.
4. Estados oficiais, hashes finais e entregas concluidas sao imutaveis.
5. Staging parcial e sempre privado e nunca referenciado por download.
6. Falhas sao auditadas sem mensagens livres ou PII.
7. O health check deteta estados orfaos sem os alterar.
