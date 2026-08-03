# Programa 53 - Plano de performance e escala

## Natureza dos limites

Todos os limites deste documento sao **technical engineering guardrails**. Nao
sao SLA, prazo legal, compromisso contratual nem regra municipal.

## Ambiente observado

| Elemento | Baseline local |
|---|---|
| PHP | 8.4.21, `memory_limit=256M` |
| Laravel | 13.12.0 |
| Base de dados | MySQL 9.6.0 |
| Queue efetiva inicial | `sync` |
| Cache | `file` |
| Filesystem | `local` privado |
| CPU/RAM | Apple M3, 8 CPUs, 8 GiB |
| Disco livre inicial | aproximadamente 4,4 GiB |

Resultados temporais so podem ser comparados no mesmo commit, ambiente, seed e
configuracao. O hard gate de memoria usa o limite mais restritivo entre CI e 512
MiB; localmente prevalece 256 MiB.

## Gates invariantes

1. Zero corrupcao ou perda de decisoes.
2. Uma unica selagem/publicacao/notificacao logica por fonte.
3. Nenhum pacote parcial descarregavel.
4. Isolamento municipal integral.
5. Sem OOM a 50.000.
6. Writers alimentados por NDJSON, sem reconsulta operacional.
7. Queries proporcionais a chunks, nao a relacoes por linha.
8. Checksums e fingerprints deterministas.
9. Limpeza da base e storage isolados apos cada cenario.
10. Queue assincrona real nos cenarios que a exercitam.

## Matriz fixa de cenarios

| Cenario | Municipios | Concursos | Analistas | Candidaturas | Documentos | Aperfeicoamentos | Lotes/exports | Queue | Falha | Resultado |
|---|---:|---:|---:|---:|---:|---:|---:|---|---|---|
| smoke-1k | 2 | 2 | 4 | 1.000 | 2.500 | 150 | 2/2 | database | nenhuma | fluxo e quatro formatos validos |
| scale-10k | 2 | 4 | 8 | 10.000 | 25.000 | 1.500 | 4/4 | database | deadlock retryable | chunking, retoma e isolamento |
| hard-50k | 2 | 4 | 8 | 50.000 | 125.000 | 7.500 | 4/4 | database | kill/storage/retry | sem OOM ou artefacto parcial |

Distribuicao sintetica deterministica inicial: 55% completas, 45% incompletas;
65% documentos validos, 15% ausentes, 10% rejeitados e 10% substituidos; 15%
com aperfeicoamento, dos quais 70% respondem; 20% de revalidacoes; 60% de
resultados publicados. Estes valores sao configuracao de carga.

## Fases medidas

1. migrations da base isolada;
2. criacao bulk do dataset;
3. leitura da workspace e readiness;
4. claim/decisao concorrentes;
5. selagem e snapshot;
6. publicacao/projecao;
7. outbox e notificacoes;
8. snapshot NDJSON;
9. writers CSV/JSON/XML/XLSX;
10. ZIP, manifest e checksums;
11. expiracao e limpeza.

Por fase recolher: duracao, queries, linhas, chunks, throughput, peak memory,
retries, deadlocks, lock waits, queue wait, tamanho e checksums.

## Protecao do harness

- Recusar `production` e conexoes cujo nome/base nao seja temporario.
- Criar SQLite/MySQL temporario por execucao; nunca reutilizar a base funcional.
- Redirecionar `local` storage para raiz temporaria exclusiva.
- Forcar queue `database`; recusar `sync` quando `queue-workers > 0`.
- Usar seed explicito, valores reservados e zero PII.
- Nao imprimir DSN, credenciais ou paths absolutos.
- `--cleanup` elimina base, jobs e storage, preservando apenas relatorio pedido.
- `--assert` devolve exit code nao zero perante gate invariavel falhado.

## Queries a observar

- processos e candidaturas do concurso por `contest_id` e `id`;
- reviews documentais por processo/tipo/estado/id;
- documentos por candidatura/registo e reviews por submissao;
- batches por concurso/ciclo/source fingerprint;
- items por batch/id;
- publication/result por batch/publication/application;
- correction requests/responses por processo/estado/prazo;
- report exports por idempotency, status, expiry e profile;
- jobs/failed jobs por queue/reserved/available.

Qualquer indice novo requer `EXPLAIN` real antes/depois. Nao sao aceites indices
especulativos.

## Outputs

O JSON e Markdown incluem timestamp UTC, commit, runtimes, drivers, hardware
disponivel, seed, dataset, contagens, duracoes, throughput, queries, memoria,
retries, deadlocks, lock waits, tamanhos, checksums, warnings e resultado.

## Criterio de comparacao

Sem baseline calibrada nao ha gate de milissegundos. Regressao temporal so e
declarada entre execucoes com ambiente e dataset equivalentes. Correcao,
streaming e integridade prevalecem sempre sobre velocidade.
