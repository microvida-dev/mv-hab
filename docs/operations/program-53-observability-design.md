# Programa 53 - Desenho de observabilidade operacional

## Decisao arquitetural

O repositorio nao possui Horizon, Pulse ativo, OpenTelemetry, Prometheus,
StatsD, Sentry ou outro APM obrigatorio. A Sprint 53I introduz contratos
internos e um adapter inicial de structured logging. A integracao futura com
um backend de metricas nao exige alteracoes nos servicos de dominio.

## Componentes implementados

- `Program53OperationalContext`: contexto tipado e minimizado.
- `Program53MetricsRecorder`: contrato sem dependencia de fornecedor.
- `StructuredLogProgram53MetricsRecorder`: emite `program53.metric`.
- `Program53ContextRedactor`: allowlist de contexto e labels.
- `Program53FailureClassifier`: codigo e disposicao tipados.
- `Program53FaultInjector`: fronteira interna de checkpoints.
- `NoopProgram53FaultInjector`: binding unico normal da aplicacao.
- `ControlledProgram53FaultInjector`: apenas construivel em `testing`.
- `Program53OperationalHealthService`: diagnostico sem escrita persistente na
  base de dados.
- `program53:operational-check`: table, JSON ou Markdown, com gates por
  severidade.

Nao existe endpoint HTTP nem feature flag para fault injection.

## Contexto operacional

O contexto pode transportar apenas:

```text
operation_id
request_id
correlation_id
municipality_id
contest_id
batch_id
publication_id
correction_request_id
export_id
job_id
attempt
stage
```

Os IDs tecnicos existem no log operacional restrito, nunca como labels de
metricas agregadas. Nao sao aceites nome, email, NIF, numero de candidatura,
texto livre, payload, documento, filename, path, token ou credencial.

## Metricas emitidas no codigo

| Dominio | Metricas |
|---|---|
| Lotes | `batch_seal_duration`, `batch_items` |
| Publicacao | `batch_publish_duration` |
| Notificacoes | `delivery_succeeded`, `delivery_failed` |
| Aperfeicoamentos | `corrections_submitted`, `correction_submission_duration`, `revalidation_duration` |
| Exportacao | `export_retries`, `snapshot_duration`, `rows_by_dataset`, `package_duration`, `export_duration`, `peak_memory`, `export_failures` |
| Retencao | `expiration_duration`, `expiration_failures` |

Labels permitidas: `component`, `operation`, `stage`, `status`,
`failure_code`, `format`, `dataset`, `result` e `reused`. Qualquer outra label
e removida antes da escrita.

Contagens globais de readiness, backlog, outbox, aperfeicoamentos e seguranca
podem ser acrescentadas por um adapter futuro. Nao se declaram implementadas
enquanto nao existir instrumentacao real nos respetivos pontos.

## Structured logging e RGPD

Cada metrica gera um registo `program53.metric` com nome de catalogo, valor,
contexto tecnico e labels redigidas. Strings sao limitadas e mapas usam
allowlist. Mensagens livres de exceptions e payloads funcionais nao sao
persistidos. A retencao do log depende da politica aprovada no ambiente alvo.

## Health operacional

O comando executa 24 verificacoes e cobre:

- ligacao, tabelas, migration e indices nucleares;
- drift do manifesto de acesso;
- JSON Schema e XSD;
- queue assincrona, relacao `retry_after > timeout`, backlog e failed jobs;
- atomic locks e rate limiters;
- escrita, leitura, move e remocao controlados no storage privado;
- espaco livre;
- exports stale, staging orfao e ficheiros parciais;
- existencia e SHA-256 do pacote concluido;
- validade de `source_fingerprint` e `manifest_sha256`;
- artefactos de exports expirados;
- comandos do scheduler, timezone e retencao de sete dias.

O probe de cache/storage e transitorio e integralmente removido. O comando nao
altera a base de dados, nao cria auditoria, nao repara estados e nao repete jobs.

Execucao local observada em 2 de agosto de 2026:

```text
24 findings
21 info
3 warning
0 critical
```

Os avisos locais sao `queue=sync`, timeout nao validavel nesse driver e
heartbeat externo do scheduler ausente. O teste dirigido prova que package hash
invalido e atomic lock indisponivel produzem severidade `critical`.

## Evidencia de queues e recuperacao

Um teste com SQLite temporaria e queue `database` inicia um `queue:work`, mata o
processo com `SIGKILL` durante o job, aguarda `retry_after` e inicia outro worker.
Resultado observado: duas tentativas, uma conclusao, zero jobs pendentes e zero
`failed_jobs`. A base e o storage temporarios sao eliminados no final.

## Integracao futura

Um adapter Prometheus/OpenTelemetry/StatsD deve preservar a allowlist, evitar
IDs como labels e mapear apenas as metricas instrumentadas. Worker heartbeat,
dashboards, alertas externos e retencao dos logs continuam deployment gates.
