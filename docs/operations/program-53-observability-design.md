# Programa 53 - Desenho de observabilidade operacional

## Decisao arquitetural

O repositorio nao possui APM ou backend de metricas externo. A Sprint 53I cria
contratos internos e usa structured logging como adapter inicial. A integracao
futura com OpenTelemetry, Prometheus, StatsD ou outro backend nao altera os
servicos de dominio.

## Contexto operacional

Cada operacao pode transportar apenas identificadores tecnicos minimizados:

- `correlation_id` opaco;
- `operation` de catalogo fechado;
- `municipality_id`, `contest_id`, `batch_id`, `publication_id`, `export_id`;
- `phase`, `queue`, `attempt`, `failure_code`;
- timestamps UTC e duracao.

O contexto nunca inclui nome, email, NIF, numero de processo/candidatura, texto
livre, documento, payload, path absoluto, token ou credencial.

## Contratos internos

- `Program53OperationalContext`: contexto tipado e serializacao redigida.
- `Program53MetricsRecorder`: incrementos, gauges e duracoes.
- `StructuredLogProgram53MetricsRecorder`: adapter inicial para canal de log.
- `Program53FailureClassifier`: codigo tipado e retryability.
- `Program53FaultInjector`: checkpoints internos; Noop fora de testes.
- `Program53OperationalHealthService`: findings read-only e severidade.

## Catalogo de metricas

### Revisao

- `program53.review.claimed_total`
- `program53.review.decisions_total`
- `program53.review.conflicts_total`
- `program53.review.readiness_duration_ms`

### Lotes/publicacao

- `program53.batch.sealed_total`
- `program53.batch.items_total`
- `program53.batch.seal_duration_ms`
- `program53.publication.completed_total`
- `program53.publication.results_total`
- `program53.publication.duration_ms`
- `program53.publication.idempotent_reuse_total`

### Notificacoes

- `program53.notification.queued_total`
- `program53.notification.delivered_total`
- `program53.notification.failed_total`
- `program53.notification.retry_total`

### Aperfeicoamentos

- `program53.correction.open_total`
- `program53.correction.submitted_total`
- `program53.correction.expired_total`
- `program53.correction.revalidated_total`
- `program53.correction.duration_ms`

### Exportacoes

- `program53.export.requested_total`
- `program53.export.snapshot_rows_total`
- `program53.export.snapshot_reused_total`
- `program53.export.completed_total`
- `program53.export.failed_total`
- `program53.export.expired_total`
- `program53.export.duration_ms`
- `program53.export.bytes_total`
- `program53.export.queue_wait_ms`

### Seguranca

- `program53.access.denied_total`
- `program53.scope.denied_total`
- `program53.mfa.denied_total`
- `program53.rate_limit.denied_total`

## Labels permitidas

`operation`, `phase`, `status`, `outcome`, `failure_code`, `retryable`, `queue`,
`mode`, `format`, `dataset` e `environment`, todos provenientes de enum/catalogo
fechado. IDs nao sao labels de metricas; podem existir apenas no contexto
estruturado de logs operacionais com acesso restrito.

## Structured logging

Eventos usam JSON logico com `event`, `context`, `measurements` e `result`.
Excecoes sao representadas por classe e failure code, nunca mensagem arbitraria
quando possa conter dados. O redactor remove recursivamente chaves sensiveis e
limita strings e arrays.

## Health operacional

`program53:operational-check` e read-only e verifica:

- migrations/tabelas/indices essenciais;
- queue assincrona e coerencia `retry_after > timeout`;
- failed jobs e backlog por filas do programa;
- scheduler e locks suportados;
- storage privado, staging orfao e espaco disponivel;
- exports presos, falhados, expirados ou com ficheiro/hash incoerente;
- publicacoes com contagens divergentes;
- notificacoes pendentes/falhadas;
- pedidos de aperfeicoamento expirados nao processados;
- manifesto de acesso e drift;
- configuracao de logs e metricas.

Severidades: `info`, `warning`, `critical`. O comando suporta table, JSON e
Markdown, output opcional e `--fail-on-warning`/`--fail-on-critical`.

## Alertas recomendados

- Critical: perda de integridade/hash, scope divergente, queue sync fora de
  local/teste, export final ausente, publicacao parcial, storage indisponivel.
- Warning: backlog, failed jobs, export stale, notification retry, pouco disco,
  cache nao partilhado, scheduler nao verificavel.
- Info: contagens, versoes, tempo desde ultima execucao e configuracao ativa.

## RGPD e cardinalidade

Logs e metricas sao dados operacionais minimizados. Nao transportar PII nem
conteudo de candidaturas. Retencao dos logs pertence a politica operacional do
ambiente e deve ser aprovada separadamente; o repositorio nao inventa esse prazo.
