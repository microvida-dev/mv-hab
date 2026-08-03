# Programa 53 - Relatório de fecho

## Resumo executivo

O Programa 53 entrega um fluxo municipal permission-first para calendário de
candidaturas, análise progressiva, snapshots em lote, publicação sincronizada,
aperfeiçoamento, revalidação diferencial, exportação temporal e hardening
operacional. Não altera elegibilidade, scoring, listas, contratos ou prazos
legais. A classificação máxima sem deploy é
`REPOSITORY_PASS_DEPLOYMENT_GATED`.

## Linha de entrega verificada no Git

| Sprint | Branch | Commit final observado | Objetivo | Estado |
|---|---|---|---|---|
| 53A | `sprint-53a-contest-application-processing-phases` | `bb36cd77` | fases processuais por concurso | concluída |
| 53B | `sprint-53b-progressive-bulk-application-review` | `d8272e67` | mesa de análise progressiva | concluída |
| 53C | `sprint-53c-review-batches-immutable-snapshots` | `0b101559` | lotes/snapshots imutáveis | concluída |
| 53D | `sprint-53d-synchronized-publication-notifications` | `d2448c5e` (merge `dcd640b2`) | publicação e notificações sincronizadas | concluída |
| 53E | `sprint-53e-candidate-correction-cycle` | `d49c28b7` | ciclo formal de aperfeiçoamento | concluída |
| 53F | `sprint-53f-differential-revalidation-second-closure` | `f8872fef` | revalidação diferencial e segundo fecho | concluída |
| 53G | `sprint-53g-temporal-application-result-exports` | `ef91154c` | exportação temporal reproduzível | concluída |
| 53H | `sprint-53h-analyst-bulk-review-export-permissions` | `67d08f1d` | perfil, permissions e segregação | concluída |
| 53I | `sprint-53i-bulk-review-operational-hardening` | commit final desta branch | performance, resiliência e operação | concluída no repositório |

## 53A - Fases processuais

- **Domínio:** `ContestDeadline`, `ContestApplicationPhase` e prazos existentes.
- **Services:** `ContestApplicationTimelineService` e
  `ContestApplicationPhaseService`.
- **Persistência:** sem migration nova; `contest_deadlines` continua fonte e
  `opens_at`/`closes_at` mantêm compatibilidade pública.
- **Segurança:** gestão pelas permissões/policies existentes; alterações
  auditadas no concurso.
- **Testes:** serviços de timeline/fase e compatibilidade de concurso.
- **Limite:** ausência de fases posteriores é aviso para legacy, não backfill
  inventado.

## 53B - Análise progressiva

- **Modelos/services:** `ApplicationReview`,
  `ApplicationReviewWorkspaceService`, `BulkApplicationReviewService`,
  `ProgressiveApplicationReviewService` e readiness documental.
- **Migration:** `2026_07_30_000047_add_progressive_bulk_review_fields.php`.
- **Segurança:** preview HMAC, lock pessimista, lock version, permission-first e
  ausência de comunicação individual antes da publicação.
- **Testes:** operações em bloco, transições, concorrência e rotas.
- **Limite:** `ready_for_closure` é técnico/reversível, nunca decisão final.

## 53C - Lotes e snapshots

- **Modelos/services:** `ApplicationReviewBatch`, item imutável,
  `ApplicationReviewBatchService`, `ReviewBatchSnapshotBuilder` e hasher
  canónico.
- **Migration:** `2026_07_31_000048_create_application_review_batches_tables.php`.
- **Segurança/integridade:** seleção integral do concurso, `seal_key`, source
  fingerprint, SHA-256, unique constraints e `lockForUpdate()`.
- **Testes:** preview read-only, retry idempotente, rollback e imutabilidade.
- **Hardening 53I:** o alvo de documentos repetíveis é resolvido pelo requisito
  (`household_member`, `income_record`, etc.), evitando chaves temporais
  ambíguas quando também existe `application_id`.

## 53D - Publicação sincronizada

- **Modelos/services:** `ApplicationReviewPublication`, resultado individual,
  `ApplicationReviewPublicationService`, notificações oficiais, comunicações e
  entregas.
- **Migration:** `2026_07_31_000049_create_application_review_publications_tables.php`.
- **Segurança:** um `published_at`, payload privado minimizado, ownership do
  candidato e email after-commit.
- **Testes:** publicação atómica, idempotência, email e acesso privado.
- **Limite:** falha do transporte não desfaz o ato oficial; é recuperada pelo
  outbox/entrega.

## 53E - Aperfeiçoamento

- **Modelos/services:** `CorrectionRequest`, items/responses,
  `CorrectionSubmissionReceipt`, deadline extension, candidate workspace e
  projectors a partir do resultado publicado.
- **Migrations:** `000050`, `000051` e `000052` de alinhamento, workspace e
  submissão formal.
- **Segurança:** pedido só nasce de resultado canónico; ownership, scope
  municipal, documentos versionados e recibo imutável.
- **Testes:** projeção, workspace candidate, substituição documental, prazos,
  operações municipais e casos legacy fail-closed.
- **Limite:** estado principal da candidatura não é reescrito pelo ciclo.

## 53F - Revalidação diferencial

- **Modelos/services:** snapshot de revalidação,
  `CorrectionDifferentialResolver`, `CorrectionRevalidationService`,
  `CorrectionResolutionService` e projector final.
- **Migration:** `2026_08_01_000053_add_correction_revalidation_controls.php`.
- **Segurança:** fonte temporal é o recibo; versões posteriores não entram;
  segundo lote/publicação reutiliza infraestrutura imutável.
- **Testes observados no relatório 53F:** suite integral de 1525 testes/22937
  asserções, PHPStan 0, ciclos SQLite/MySQL PASS.
- **Limite:** sem terceiro aperfeiçoamento automático; decisão manual permanece
  humana.

## 53G - Exportação temporal

- **Modelos/services:** extensão de `ReportExport`, source resolver, snapshot
  NDJSON, comparator, catálogo de campos, writers, schema validator, package e
  lifecycle assíncrono.
- **Migration:** `2026_08_01_000054_extend_report_exports_for_temporal_application_results.php`.
- **Segurança:** scope municipal, entitlement, MFA, Policy, storage privado,
  downloads auditados, schemas/hashes e package atómico.
- **Testes observados no relatório 53G:** 1573 testes/23238 asserções, PHPStan
  0, SQLite/MySQL up/down/up PASS.
- **Limite:** binários documentais fail-closed sem estado antivírus persistido.

## 53H - Perfil e segregação

- **Componentes:** template `analista-candidaturas-exportacao`, role registry,
  role management, access audit, seis rate limiters e manifesto de 45 rotas.
- **Migration:** `2026_08_01_000055_add_template_metadata_to_roles_table.php`.
- **Segurança:** 36 permissions exatas, nenhum wildcard, permissions e
  entitlements independentes, MFA orientado por risco, role lifecycle e scope
  municipal.
- **Testes:** matriz de acesso, workflow, rate limiting, segregação,
  concorrência de templates e seeder demonstrativo.
- **Limite:** template não ativa feature, não atribui utilizador e não concede
  `reports.export_sensitive`.

## 53I - Hardening operacional

- **Observabilidade:** contexto tipado/redigido, recorder desacoplado, métricas,
  classificação de falhas e health read-only.
- **Resiliência:** fault injection só em testes, rollback/retry, recuperação de
  snapshots/packages, queue worker kill/recovery, retenção e scheduler
  idempotente.
- **Performance:** 1.000, 10.000 e 50.000 candidaturas com memória constante de
  38.273.024 bytes; detalhes no relatório de performance.
- **Seeder:** dois Municípios, três candidaturas, lotes inicial/revalidação,
  publicações, nove notificações oficiais, aperfeiçoamento com recibo,
  candidato sem resposta e dois exports temporais seguros.
- **Persistência:** sem migration nova na 53I; hardening compatível com schemas
  53A-53H.
- **Limite:** APM/alerting, workers, scheduler, cache e storage são validados no
  deploy alvo.

## Matriz de resultado funcional

| Resultado | Fonte técnica | Prova/invariante |
|---|---|---|
| prazos independentes | 53A | timeline validada, sem sobreposição |
| análise progressiva | 53B | preview + lock version, sem comunicação |
| fecho em lote | 53C | snapshot/hash imutável e seleção integral |
| publicação simultânea | 53D | uma transação e um `published_at` |
| aperfeiçoamento | 53E | finding publicado -> pedido -> recibo |
| revalidação de alterações | 53F | diff a partir do recibo e segundo lote |
| export reproduzível | 53G | NDJSON, schemas, manifesto e SHA-256 |
| acesso permission-first | 53H | permission + Policy + scope + entitlement + MFA |
| escala/recuperação | 53I | 50k sem OOM, chaos/queue/health e runbooks |

## Artefactos operacionais

- BPMN e descrição: `docs/operations/program-53-process.*`.
- Matriz: `program-53-state-matrix.md`.
- Catálogo: `program-53-export-code-catalog.md`.
- Manuais/runbooks: analista, prazos, queue, retenção e observabilidade.
- Recuperação: `program-53-failure-recovery-matrix.md`.
- Performance: `docs/quality/program-53-performance-report.md`.

## Gates externos

Sem deploy real permanecem obrigatórios: backup, migration real, cache
partilhado, worker `reports`, workers de notificações, scheduler, storage
privado, permissões filesystem, MFA, entitlements, monitorização, alertas,
acessibilidade manual, smoke pós-deploy e rollback operacional.

## Decisão

`REPOSITORY_PASS_DEPLOYMENT_GATED`.
