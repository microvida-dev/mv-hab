# Programa 53 - Relatorio de performance

## Escopo e metodo

Resultados do harness sintético isolado da Sprint 53I, executado em 2 de agosto
de 2026 sobre o commit de trabalho posterior a `6ea0da92`. Cada cenário criou
uma base SQLite e storage exclusivos, processou revisão, selagem, publicação,
resultados, notificações, snapshot NDJSON e pacote CSV/JSON/XML/XLSX, e eliminou
os dados temporários com `--cleanup`.

Os valores são **technical engineering guardrails**, não SLA. A queue do harness
é persistente em SQLite e não usa `sync`; workers Laravel database e concorrência
MySQL são gates separados do Bloco 53I-C/final.

## Ambiente

- Apple M3, 8 CPUs, 8 GiB RAM.
- PHP 8.4.21 com `memory_limit=256M` nestas execuções.
- Laravel 13.12.0.
- SQLite 3.53.1 isolado; storage local temporário privado.
- Quatro formatos: CSV, JSON, XML e XLSX.
- JSON Schema e XSD validados durante a geração.

## Resultados

| Candidaturas | Linhas totais | Duração | Peak memory | Queries | Candidaturas/s | Linhas export/s | ZIP | Resultado |
|---:|---:|---:|---:|---:|---:|---:|---:|---|
| 1.000 | 3.840 | 3,327 s | 38.273.024 B | 66 | 300,577 | 1.161,768 | 739.586 B | PASS |
| 10.000 | 38.487 | 32,449 s | 38.273.024 B | 259 | 308,177 | 1.197,403 | 7.071.311 B | PASS |
| 50.000 | 192.615 | 179,258 s | 38.273.024 B | 1.059 | 278,928 | 1.088,005 | 35.211.923 B | PASS |

Linhas totais somam aplicações, documentos, findings e changes. O cenário
50.000 contém 50.000 aplicações, 125.000 documentos, 7.615 findings e 10.000
changes.

## Fases do cenário 50.000

| Fase | Segundos |
|---|---:|
| schema | 0,002 |
| dataset | 1,207 |
| revisão | 0,176 |
| selagem | 0,111 |
| publicação | 0,440 |
| queue wait | < 0,001 |
| snapshot NDJSON | 3,434 |
| writers, schemas e package | 173,601 |
| integridade | 0,283 |
| total | 179,258 |

A validação/serialização dos quatro formatos representa o custo dominante. A
memória constante confirma leitura e escrita em streaming.

## Query plans

Os quatro planos críticos do harness usam índices:

- workspace: `benchmark_applications_scope_idx`;
- fila de analista: `benchmark_applications_work_idx`;
- resultados por publicação: `benchmark_results_publication_idx`;
- queue pronta: `benchmark_queue_ready_idx` (covering).

As queries crescem por chunks de 50 e por concurso, não por relação exportada.
O crescimento 66 -> 259 -> 1.059 é compatível com essa regra.

## Integridade

Nos três cenários:

- uma aplicação, um resultado e uma notificação;
- um lote e uma publicação por concurso;
- zero linha fora do Município do concurso;
- queue persistente concluída;
- contagens do snapshot coerentes;
- pacote e manifest com SHA-256;
- nenhum OOM;
- storage/base temporários eliminados.

Hashes dos pacotes observados:

- 1.000: `6f97a506c6dc4942e0ae8a6e2e251331c2af124d03e791d0f9de8ed8ab40d6ac`;
- 10.000: `5bd879a8011cb1a68de94f3031f40fe18faa7f6c45dc1a339ae20cf3e8253012`;
- 50.000: `8c9f8d0ae7670526a0e5d31b4ab0d4c238acc39490e21c0abf6d6b5a33106817`.

## Limites desta medição

- SQLite mede o harness e os writers; não substitui o gate MySQL/MariaDB.
- A queue persistente do harness não substitui testes de `queue:work`, kill e
  retry do Bloco C.
- Tempos não são comparáveis entre hardware diferente.
- O benchmark não contém PII nem documentos binários.
