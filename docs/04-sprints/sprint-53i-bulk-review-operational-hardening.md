# Sprint 53I - Observabilidade, performance, resiliencia e fecho operacional

## 1. Resumo executivo

A Sprint 53I fecha operacionalmente o Programa 53 sem alterar as regras
funcionais entregues nas Sprints 53A a 53H. O trabalho esta dividido em quatro
macroblocos: auditoria e budgets, performance e escala, resiliencia e
observabilidade, e documentacao de operacao. Este documento e atualizado apenas
com resultados medidos pelos comandos e testes da propria sprint.

## 2. Commit-base

`67d08f1dba67b955dccbc2089613d9d299f3934c`

## 3. Branch

`sprint-53i-bulk-review-operational-hardening`

## 4. Commits

Planeados, no maximo, os quatro commits coesos definidos no master prompt. Os
SHAs sao registados depois de cada commit existir.

## 5. Auditoria inicial

- Working tree inicial limpa e commit-base confirmado.
- Laravel 13.12.0, PHP 8.4.21, MySQL 9.6.0, Node 24.11.0 e Composer 2.9.8.
- Ambiente local com MySQL, cache `file`, queue efetiva `sync`, storage `local`
  e logs `stack`/`single`.
- Nao foram encontradas dependencias diretas de Horizon, Pulse, OpenTelemetry,
  Prometheus, StatsD, Sentry ou Telescope.
- O projeto ja possui health check generico, auditoria, jobs unicos, locks
  transacionais, snapshots NDJSON em streaming e pacotes com checksums.
- O timeout de 1800 segundos do job de exportacao excede o `retry_after` de 90
  segundos da queue database por defeito. Este desvio e um gate operacional.
- A publicacao coletiva e atomica, mas a projecao de resultados e notificacoes
  executa trabalho por candidatura dentro da transacao.
- Um export deixado em `processing` pela morte do worker nao possui retoma
  explicita; a classificacao de falhas ainda depende parcialmente de mensagens.

## 6. Arquitetura de observabilidade

Prevista uma camada interna composta por contexto operacional de baixa
cardinalidade, recorder de metricas desacoplado, structured logging com redacao,
classificacao tipada de falhas e health check read-only. Sem dependencia externa.

## 7. Harness de benchmark

Implementado o comando `program53:benchmark`, com base SQLite e storage isolados,
seed deterministico, queue persistente `database`, relatórios JSON/Markdown e
limpeza segura. O comando recusa produção, paths fora de `storage/qa`, volumes
fora do limite e zero workers.

## 8. Datasets

Os dados sao exclusivamente sinteticos. A distribuicao cobre candidaturas
completas/incompletas, documentos validos/ausentes/rejeitados/substituidos,
aperfeicoamentos respondidos/nao respondidos, revalidacao e resultados
publicados. As percentagens sao parametros tecnicos, nao regras municipais.

## 9. Cenario 1.000

PASS: 1.000 candidaturas, 3.840 linhas, 3,327 s, 38.273.024 bytes de
peak memory, 66 queries e pacote de 739.586 bytes.

## 10. Cenario 10.000

PASS: 10.000 candidaturas, 38.487 linhas, 32,449 s, 38.273.024 bytes de
peak memory, 259 queries e pacote de 7.071.311 bytes.

## 11. Cenario 50.000

PASS: 50.000 candidaturas, 192.615 linhas, 179,258 s, 38.273.024 bytes
de peak memory, 1.059 queries e pacote de 35.211.923 bytes. Sem OOM.

## 12. Hardware e ambiente

Baseline local observado: Apple M3, 8 CPUs, 8 GiB RAM e cerca de 4,4 GiB livres
no filesystem no inicio da sprint. Os resultados so sao comparaveis no mesmo
ambiente. O espaco reduzido obriga a limpeza de cada cenario.

## 13. Memoria

Budget técnico: menor valor entre CI e 512 MiB. Os três cenários foram executados
com 256 MiB e mantiveram peak constante de 38,3 MiB, confirmando streaming.

## 14. Tempos

Não existe SLA legal ou contratual. No cenário 50.000: dataset 1,207 s, revisão
0,176 s, selagem 0,111 s, publicação 0,440 s, snapshot 3,434 s e package com
quatro formatos 173,601 s.

## 15. Throughput

Observado entre 278,928 e 308,177 candidaturas/s e entre 1.088,005 e 1.197,403
linhas exportadas/s. É baseline local, não hard gate portátil.

## 16. Queries

Foram contabilizadas 66/259/1.059 queries. O crescimento é proporcional aos
chunks de 50 e não ao número de linhas dos writers, que leem NDJSON.

## 17. Indices

O `EXPLAIN QUERY PLAN` confirmou os índices de scope, fila de analista,
publicação/resultados e queue. O ajuste ocorreu apenas no schema sintético; não
foi necessária migration de produção no Bloco B.

## 18. Multiplos analistas

O gate MySQL usa oito processos/conexoes independentes para claim, decisao,
readiness, selagem e publicacao no mesmo concurso.

## 19. Concorrencia

As garantias existentes de `lockForUpdate`, unique constraints e hashes sao
preservadas. Os testes devem provar convergencia para um lote, snapshot,
publicacao, `published_at`, resultado e conjunto de notificacoes.

## 20. Locks

Locks de base de dados continuam a proteger estado oficial. Cache locks sao
apenas coordenacao operacional e nunca fonte de verdade.

## 21. Deadlocks

Deadlocks sao classificados como retryable, registados sem PII e testados com
retry limitado. A ordem de lock deve ser deterministica.

## 22. Queue hardening

O inventario cobre queue, tries, timeout, backoff, retryUntil, afterCommit,
unicidade e failed handlers. O `retry_after` deve ser superior ao maior timeout.

## 23. Retries

Falhas transitorias podem repetir; erros de scope, autorizacao, schema e fonte
stale sao terminais ou exigem nova operacao. Nenhum retry pode contornar RBAC.

## 24. Fault injection

Sera interna, sem rota HTTP, ativa apenas em testes/benchmark controlado e com
implementacao Noop em producao.

## 25. Notification recovery

Entregas ja concluidas permanecem imutaveis. Falhas parciais retomam apenas
itens pendentes/falhados e mantem historico de tentativas.

## 26. Export recovery

Snapshot NDJSON so pode ser reutilizado se completo, checksummed e associado ao
mesmo fingerprint. Staging incompleto e eliminado; export concluido nao e
regenerado e export expirado nao e retomado.

## 27. Storage

Storage privado e paths relativos continuam obrigatorios. Pacotes `.partial`
nunca sao descarregaveis e a publicacao final mantem move atomico e hash final.

## 28. Scheduler

As tarefas de aperfeicoamentos e expiracao usam `withoutOverlapping` e
`onOneServer`; a duplicacao do scheduler sera testada como idempotente.

## 29. Retencao

A politica tecnica atual de sete dias nao e alterada. Corridas com download,
falhas de delete e orphan cleanup serao cobertas.

## 30. DST

Os gates incluem transicoes de primavera e outono em `Europe/Lisbon`, UTC e
serializacao ISO 8601. Nao sao introduzidos prazos legais.

## 31. Multi-Municipio

Carga sintetica usa dois Municipios. Scope, locks, rate limits, exports e
metricas devem manter isolamento; operador sem assignment falha fechado.

## 32. Seguranca

Permissions, entitlements, Policies, MFA, rate limiting e scopes de 53H sao
revalidados sob retry e carga. Candidate permanece fora do backoffice e auditor
permanece read-only.

## 33. RGPD

Metricas e logs excluem nomes, emails, numeros de candidatura, paths, payloads,
documentos e identificadores de alta cardinalidade. Benchmarks usam dados
ficticios.

## 34. Metricas

O catalogo inicial encontra-se em
`docs/operations/program-53-observability-design.md`; labels sao bounded por
dominio, fase, estado, resultado e codigo de falha.

## 35. Logs

Structured logs incluem operacao, correlation ID, Municipio, concurso, lote,
export, fase, duracao e resultado quando aplicavel, sempre redigidos.

## 36. Health command

Previsto `program53:operational-check`, exclusivamente read-only, com formatos
table/json/markdown e exit codes configuraveis por warning/critical.

## 37. Migrations

O Bloco A nao cria migrations. O plano inclui fresh, rollback seguro, reapply e
validacao explicita das migrations fail-closed 53A-53H em bases temporarias.

## 38. Schemas

Os schemas JSON/XSD v1 e os codigos canonicos permanecem inalterados; sera criado
catalogo derivado de enums e testes de paridade CSV/JSON/XML/XLSX.

## 39. Seeder

O demo do Programa 53 sera consolidado com dois Municipios e `--verify-only`,
sem inserir a escala 50.000 no seeder regular.

## 40. Documentacao operacional

Planeados BPMN 2.0, matriz de estados, catalogo de codigos, manuais de analista e
prazos, runbooks de queue/observabilidade e politica de retencao.

## 41. Testes

No Bloco A nao foram ainda executados testes de implementacao. Os gates dirigidos
e integrais sao registados apenas depois de observados.

## 42. Gates

Pendentes dos Blocos B-D: integridade, Composer, PHPStan, Pint, PHPUnit integral,
UX, Vite, rotas, auditorias, health, benchmarks e MySQL.

## 43. Rotas

Nao sao previstas rotas HTTP novas. Benchmark e health sao comandos Artisan.

## 44. Estado Git

Estado inicial confirmado limpo na branch correta. O estado final sera registado
depois do push e comparacao do SHA remoto.

## 45. Riscos residuais

- Espaco livre local reduzido para artefactos 50.000.
- Queue local efetiva `sync` antes da configuracao isolada do harness.
- Publicacao coletiva atual executa trabalho linear numa transacao longa.
- Nao existe APM externo; observabilidade inicial depende de logs/adapters.

## 46. Deployment gates

Backup, migration real, cache partilhado, workers, scheduler, storage privado,
MFA, entitlements, monitorizacao, acessibilidade manual, smoke e rollback no
ambiente alvo continuam externos ao repositorio.

## 47. Exclusoes

Sem deploy, regras legais, PDF, assinatura qualificada, arquivo externo, API
publica, SFTP, APM comercial, novo RBAC ou alteracoes a elegibilidade/scoring.

## 48. Decisao final

`IN_PROGRESS` durante o Bloco 53I-A. A classificacao final maxima sem deploy e
`REPOSITORY_PASS_DEPLOYMENT_GATED`.
