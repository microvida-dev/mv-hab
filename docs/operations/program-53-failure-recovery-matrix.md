# Programa 53 - Matriz de falhas e recuperacao

## Taxonomia implementada

| Codigo | Disposicao | Tratamento |
|---|---|---|
| `storage_unavailable` | retryable | repetir com backoff e staging validado |
| `database_deadlock` | retryable | rollback e retry transacional limitado |
| `database_unavailable` | retryable | repetir dentro da janela fixa |
| `package_corrupted` | retryable limitado | eliminar package staging e reconstruir; termina apos tries |
| `stale_source` | requires new operation | preservar resultado anterior e exigir nova operacao |
| `source_not_found` | terminal | sem retry automatico |
| `authorization_revoked` | terminal | nova autorizacao obrigatoria |
| `schema_invalid` | terminal | corrigir codigo/schema antes de nova operacao |
| `document_unavailable` | terminal | resolver disponibilidade documental |
| `unexpected_failure` | terminal | investigar sem guardar mensagem livre |

## Checkpoints e invariantes

| Dominio | Checkpoint | Fronteira | Estado apos falha | Retoma |
|---|---|---|---|---|
| Lote | `before_batch_lock` | antes do lock | sem mutacao | repetir |
| Lote | `after_batch_items_before_seal` | dentro da transacao | rollback integral | repetir preview/seal |
| Lote | `after_seal_commit_before_dispatch` | depois do commit | lote selado imutavel | operacao idempotente |
| Publicacao | `before_notification_chunk` | dentro da transacao | rollback integral | repetir |
| Publicacao | `mid_notification_chunk` | meio do outbox | rollback integral | repetir |
| Publicacao | `during_projection` | projecao administrativa | rollback integral | repetir |
| Publicacao | `after_outbox_persist` | antes do commit | rollback integral | repetir |
| Publicacao | `after_publication_commit` | depois do commit | uma publicacao | retry devolve original |
| Entrega | `after_delivery_before_ack` | entrega persistida | entrega terminal preservada | retry faz noop |
| Correcao | `after_receipt_lock` | depois do lock | sem recibo parcial | repetir |
| Correcao | `after_snapshot_before_commit` | snapshot na transacao | rollback integral | repetir |
| Correcao | `during_revalidation` | diferencial calculado | sem decisao nova | repetir ou nova fonte |
| Correcao | `after_resolution_before_projection` | lote de revalidacao selado | lote imutavel | projecao idempotente |
| Export | `after_source_resolution` | fonte resolvida | sem snapshot final | repetir |
| Export | `mid_ndjson_snapshot` | NDJSON parcial | staging privado invalido | eliminar/reconstruir |
| Export | `after_snapshot_checksum` | NDJSON completo | checkpoint preservado | validar e reutilizar |
| Export | `after_csv/json/xml/xlsx` | writer concluido | package staging removido | reutilizar snapshot |
| Export | `after_manifest` | manifesto escrito | sem ZIP final | reconstruir package |
| Export | `after_partial_zip` | ZIP criado | nunca descarregavel | validar/reconstruir |
| Export | `before_atomic_move` | antes da publicacao | sem final | repetir package/move |
| Export | `after_atomic_move_before_completion` | final movido, DB pendente | final eliminado no catch | repetir com mesmo snapshot |
| Retencao | `before_expiration_lock` | antes do lock | estado original | repetir |
| Retencao | `after_database_expired_before_file_delete` | download ja bloqueado | `expired`, ficheiro privado pendente | limpar apenas artefactos |
| Retencao | `during_staging_cleanup` | durante cleanup | `expired`, sem URL valida | repetir cleanup |

## Retoma de exportacoes

1. Resolver novamente a fonte e calcular o hash do payload da fonte.
2. Reutilizar NDJSON apenas quando paths, contagens, checksums, fingerprint e
   todos os registos sao validos.
3. Eliminar integralmente o source staging quando existe divergencia.
4. Reconstruir sempre package staging incompleto.
5. Marcar `completed` apenas depois de schemas, manifesto, ZIP, checksums, move
   atomico e hash final.
6. Nao regenerar `completed`; nao retomar `expired`.

## Retencao

Ordem implementada:

```text
lock
-> revalidar estado/download recente
-> marcar expired e impedir download
-> eliminar package e staging
-> limpar metadata de ficheiro
-> auditar
```

Se a remocao falhar depois de `expired`, o scheduler seleciona novamente apenas
o cleanup pendente. Duas execucoes do scheduler convergem para uma transicao e
um evento de auditoria.

## Queues

- export: `tries=3`, `timeout=1800`, backoff `60/300/900`, retry deadline fixa;
- expiracao: `tries=3`, `timeout=120`, backoff `30/120/600`, deadline fixa;
- email processual: deadline de retry fixada no construtor e preservada na
  serializacao;
- default `retry_after` database/redis: 2100 segundos, superior ao export;
- falha terminal chama `fail`; falha retryable volta a lancar para a queue.

## Evidencia dirigida

- rollback do lote apos persistencia parcial;
- retry de publicacao depois do commit sem notificacoes duplicadas;
- retry de entrega persistida sem segunda tentativa externa;
- snapshot valido reutilizado e snapshot corrompido reconstruido;
- cleanup de export expirado retomado;
- scheduler duplicado idempotente;
- cache atomic lock indisponivel classificado critical;
- worker database morto por `SIGKILL` e retomado por segundo worker;
- DST primavera/outono com instantes UTC inequivocos.
