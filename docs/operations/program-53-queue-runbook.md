# Programa 53 - Runbook de filas

## Filas e jobs

| Fila | Job | Tries | Timeout | Backoff | Janela |
|---|---|---:|---:|---|---|
| `reports` | `GenerateApplicationResultExport` | 3 | 1800 s | 60/300/900 s | fixa no job |
| `reports` | `ExpireApplicationResultExport` | 3 | 120 s | 30/120/600 s | 2 h por defeito |
| configurada para notificações | `DeliverProceduralEmail` | 5 | 120 s | configuração MV-HAB | fixa no job |

Exports usam unicidade até ao processamento; expiração usa unicidade por
export. Email processual é despachado `afterCommit()` e tem chave de entrega.

## Configuração obrigatória

- `QUEUE_CONNECTION=database` ou `redis`; nunca `sync` em produção.
- `retry_after` deve ser estritamente superior a 1800 s. O valor recomendado
  pelo repositório é 2100 s para database/redis.
- Um worker dedicado deve consumir `reports`.
- Cache partilhado deve suportar atomic locks.
- Failed jobs e heartbeat devem ser monitorizados.

Exemplo operacional, ajustado ao supervisor da infraestrutura:

```bash
php artisan queue:work database --queue=reports --sleep=1 --tries=3 --timeout=1800 --max-time=3600
php artisan queue:work database --queue=default,notifications --sleep=1 --tries=5 --timeout=120 --max-time=3600
```

## Deploy e graceful shutdown

1. Bloquear novos deploys concorrentes.
2. Confirmar health, backlog e jobs falhados.
3. Ativar maintenance apenas se o procedimento de deploy o exigir.
4. Executar migrations antes de iniciar código que delas dependa.
5. `php artisan queue:restart` para pedir saída após o job atual.
6. Esperar graceful shutdown; não matar writers durante o move final salvo
   incidente controlado.
7. Iniciar workers com a nova release.
8. Validar heartbeat, backlog, export de smoke e entrega de teste autorizada.

## Retry e classificação

- `database_deadlock`, `database_unavailable` e `storage_unavailable` são
  retryable.
- `package_corrupted` reconstrói staging de forma limitada.
- `stale_source` exige nova operação.
- `source_not_found`, `authorization_revoked`, `schema_invalid` e
  `document_unavailable` são terminais.
- Nunca usar `queue:retry all` sem classificar cada failed job e revalidar
  autorização/contexto.

Para retry individual:

```bash
php artisan queue:failed
php artisan queue:retry <uuid>
```

Registar o UUID técnico e código seguro de falha; não copiar payloads para
incidentes ou tickets.

## Worker terminado

O teste de resiliência prova que um job reservado num driver database, morto
por `SIGKILL`, regressa após `retry_after` e é concluído por outro worker sem
duplicar efeito final. Operação em produção:

1. confirmar processo do worker e motivo da morte;
2. verificar que o job continua em `jobs` ou passou a `failed_jobs`;
3. restaurar a dependência (DB/storage/cache);
4. iniciar worker substituto;
5. observar uma única conclusão e limpar apenas staging inválido;
6. reconciliar métricas e auditoria.

## Recuperação de exportação

- Snapshot NDJSON completo só é reutilizado após contagem, SHA-256,
  fingerprint e schema válidos.
- Snapshot incompleto/divergente é eliminado e reconstruído.
- Package staging é sempre reconstruível; `.partial` não é download.
- `completed` não é regenerado; `expired` não é retomado.

## Verificação pós-incidente

```bash
php artisan program53:operational-check --format=json --fail-on-critical
php artisan queue:failed
php artisan schedule:list
```

Confirmar: zero pacote parcial exposto, hashes finais válidos, uma única
publicação/entrega, backlog a descer, worker heartbeat presente e logs sem PII.
