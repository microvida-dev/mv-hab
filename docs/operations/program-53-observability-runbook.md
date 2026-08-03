# Programa 53 - Runbook de observabilidade

## Objetivo

Detetar degradação sem expor PII e orientar diagnóstico/recuperação. A camada
interna usa `Program53OperationalContext`, `Program53MetricsRecorder`, logs
estruturados com allowlist e códigos tipados de falha. Não depende de APM
comercial.

## Health read-only

```bash
php artisan program53:operational-check --format=table
php artisan program53:operational-check --format=json --fail-on-critical
php artisan program53:operational-check --format=markdown --output=storage/qa/program53-health.md
```

O comando inspeciona 24 áreas/findings, não repara estados, não cria auditoria
e remove probes transitórios. Em local foram observados 21 `info`, 3 `warning`
e 0 `critical`; os warnings correspondem a queue `sync`, relação de timeout não
validável nesse driver e heartbeat externo ausente.

## Contexto permitido

`operation_id`, `request_id`, `correlation_id`, `municipality_id`, `contest_id`,
`batch_id`, `publication_id`, `correction_request_id`, `export_id`, `job_id`,
`attempt` e `stage`.

Nunca incluir nome, email, NIF, número de candidatura, filename/path, payload,
documento, token, credencial, fundamento livre ou conteúdo de exception.

## Métricas implementadas

| Domínio | Métricas principais |
|---|---|
| lotes | `batch_seal_duration`, `batch_items` |
| publicação | `batch_publish_duration` |
| entregas | `delivery_succeeded`, `delivery_failed` |
| aperfeiçoamento | `corrections_submitted`, `correction_submission_duration`, `revalidation_duration` |
| export | `export_retries`, `snapshot_duration`, `rows_by_dataset`, `package_duration`, `export_duration`, `peak_memory`, `export_failures` |
| retenção | `expiration_duration`, `expiration_failures` |

Labels permitidas: `component`, `operation`, `stage`, `status`, `failure_code`,
`format`, `dataset`, `result`, `reused`. IDs técnicos ficam no contexto de log,
nunca em labels agregadas.

## Severidade

- **info:** componente disponível/estado esperado.
- **warning:** degradação local ou risco ainda sem impacto crítico observado.
- **critical:** integridade, isolamento, lock, schema, storage final, hash,
  worker/backlog ou configuração de produção insegura.

Qualquer `critical` bloqueia deploy e exige diagnóstico antes de retry.

## Alertas mínimos no ambiente alvo

1. Queue `reports` sem heartbeat com backlog.
2. `failed_jobs` antigo ou crescimento continuado.
3. `retry_after <= timeout` do export.
4. Atomic locks indisponíveis.
5. Drift da matriz de acesso.
6. JSON Schema/XSD inválido.
7. Export `processing` stale, pacote/hash divergente ou `.partial` órfão.
8. Falha de storage/move/delete.
9. Scheduler sem heartbeat ou execução duplicada persistente.
10. Taxa elevada de `export_failures`/`delivery_failed` por código bounded.

Os thresholds finais dependem da infraestrutura e volume reais; não são
inventados neste repositório.

## Triagem

1. Capturar timestamp UTC, environment, código de finding/falha e operation ID.
2. Verificar scope municipal sem consultar conteúdo pessoal.
3. Executar health JSON e `queue:failed`.
4. Confirmar DB, cache locks, storage, workers e scheduler.
5. Classificar retryable/terminal/new operation pelo catálogo.
6. Recuperar segundo a matriz de falhas.
7. Confirmar hash/contagem/estado final e ausência de duplicados.
8. Registar causa e ação sem payload sensível.

## Integração futura

Prometheus/OpenTelemetry/StatsD pode implementar o contrato existente desde que
preserve allowlists e baixa cardinalidade. Dashboards, alert manager, retenção
de logs e heartbeat são deployment gates externos.
