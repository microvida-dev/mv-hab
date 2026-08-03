# Programa 53 - Catálogo de códigos de exportação e operação

## Contrato

Os códigos são estáveis e independentes das labels portuguesas. JSON, XML, CSV
e XLSX derivam do mesmo snapshot NDJSON e usam schema `1.0`. Alterar/remover um
código exige nova versão de schema e migração explícita dos consumidores.

## Fonte temporal (`ApplicationResultExportMode`)

| Código | Significado |
|---|---|
| `current_state` | estado operacional no instante pedido |
| `sealed_batch` | snapshot de lote selado |
| `phase_snapshot` | publicação de fase |
| `delta_between_batches` | diferenças entre dois lotes |
| `delta_since_datetime` | alterações desde instante ISO 8601 |
| `final_result` | último resultado oficial disponível |

## Datasets (`ApplicationResultExportDataset`)

| Código | Conteúdo minimizado |
|---|---|
| `applications` | estado processual por candidatura |
| `documents` | metadata documental, sem binários |
| `findings` | achados estruturados publicados |
| `changes` | diferenças canónicas de exportações delta |

## Tipos de alteração (`ApplicationResultChangeType`)

`added`, `removed`, `changed`, `unchanged`.

## Estado de exportação (`ReportExportStatus`)

`pending`, `processing`, `completed`, `failed`, `cancelled`, `expired`.

## Ciclo, lote e resultado

- Ciclos: `initial_review`, `revalidation`.
- Estado do lote: `sealed`, `superseded`.
- Outcomes: `complete_pending_decision`, `correction_required`,
  `correction_rejected`, `withdrawn`, `not_assessed`.
- Publicação: `published`.

## Aperfeiçoamento

- Pedido: `notified`, `open`, `partially_completed`, `submitted`, `expired`,
  `cancelled`, `resolved`.
- Resposta: `draft`, `submitted`, `under_review`, `accepted`, `rejected`,
  `cancelled`.
- Revisão da resposta: `accepted`, `rejected`, `requires_more_information`,
  `requires_manual_decision`, `not_applicable`.
- Resultado agregado: `accepted`, `rejected`, `requires_manual_decision`.

## Falhas operacionais (`Program53FailureCode`)

| Código | Disposição |
|---|---|
| `source_not_found` | terminal |
| `stale_source` | `requires_new_operation` |
| `authorization_revoked` | terminal |
| `schema_invalid` | terminal |
| `storage_unavailable` | `retryable` |
| `database_deadlock` | `retryable` |
| `database_unavailable` | `retryable` |
| `package_corrupted` | retry limitado |
| `document_unavailable` | terminal |
| `unexpected_failure` | terminal e investigação |

Disposições canónicas: `retryable`, `terminal`, `requires_new_operation`.
Severidades do health: `info`, `warning`, `critical`.

## Códigos de candidatura e fase

- Candidatura: `draft`, `submitted`, `under_review`, `requires_correction`,
  `correction_submitted`, `eligible`, `ineligible`, `excluded`, `cancelled`,
  `withdrawn`, `expired`.
- Fase: `cancelled`, `upcoming`, `applications`, `initial_review`,
  `corrections`, `revalidation`, `between_phases`, `completed`.
- Revisão: `draft`, `in_progress`, `ready_for_closure`, `completed`,
  `cancelled`.

## Paridade e segurança

- Os writers não consultam a base: consomem o mesmo NDJSON canónico.
- A primeira linha/elemento identifica versão e dataset conforme o schema.
- Manifesto e ficheiros têm SHA-256; o ZIP final possui hash persistido.
- Fórmulas de spreadsheet são neutralizadas.
- Dossier documental produz índice; binários permanecem excluídos enquanto não
  existir estado antivírus/quarentena persistido.
- O perfil normal é não sensível. `reports.export_sensitive` é independente e
  não pertence aos templates padrão do Programa 53.
