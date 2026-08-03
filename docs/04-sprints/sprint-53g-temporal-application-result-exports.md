# Sprint 53G - Exportacao temporal, dossier municipal e artefactos reproduziveis

## 1. Resumo executivo

A Sprint 53G implementou exportacoes temporais e reproduziveis para o dominio
de candidaturas e resultados, sem criar um segundo subsistema de reporting.
`ReportDefinition`, `ReportRun` e `ReportExport` continuam a suportar catalogo,
execucao, autorizacao, storage, download, auditoria e retencao.

Cada exportacao resolve uma fonte temporal, captura um snapshot canonico
privado em NDJSON e gera CSV, JSON, XML e XLSX reais a partir dessa unica
representacao. O resultado e um ZIP municipal com schemas versionados,
manifesto, checksums e hashes persistidos. O pedido e assincrono, scoped por
Municipio e concurso, idempotente e concluido apenas depois das validacoes e
da publicacao atomica do pacote.

A inclusao de binarios documentais permanece bloqueada de forma fail-closed:
o repositorio nao possui um estado persistido e confiavel de antivirus ou
quarentena. Quando o dossier e pedido, e produzido `document-index.csv` com
identificadores opacos e o motivo tecnico de exclusao; nenhum binario e
incluido silenciosamente.

Classificacao final: `REPOSITORY_PASS_DEPLOYMENT_GATED`.

## 2. Commit-base

- Commit-base obrigatorio: `f8872fef447c84cc8e934b332008b22963ae6bc0`.
- Branch de origem: `sprint-53f-differential-revalidation-second-closure`.
- Nao foi efetuado merge de `main` ou de outra branch durante a sprint.

## 3. Branch

- Branch de trabalho: `sprint-53g-temporal-application-result-exports`.
- O trabalho foi executado exclusivamente nesta branch.

## 4. Commits

Sequencia coesa da sprint:

1. `dac7b820 docs(sprint-53g): audit temporal export architecture`
2. `f2b27d23 feat(exports): add canonical temporal application result sources`
3. `3bdfd288 feat(exports): generate versioned municipal export packages`
4. `feat(exports): add secure temporal export lifecycle and backoffice`
   - commit que inclui lifecycle, jobs, backoffice, seguranca, testes e este
     relatorio final.

## 5. Auditoria inicial

Foram auditados:

- models, enums, migrations, controllers, requests, policies, services,
  exporters, rotas, views, seeders e testes de reporting;
- `ReportDefinition`, `ReportRun`, `ReportExport`, `ReportExportService`,
  `ReportPermissionService`, `ReportAccessLogger` e
  `ReportDownloadService`;
- lotes, itens, publicacoes e resultados imutaveis das Sprints 53C-53F;
- recibos de aperfeicoamento, processos administrativos, elegibilidade,
  scoring e decisoes administrativas;
- submissions, versoes, requisitos e storage privado de documentos;
- permissions, entitlement, MFA, conta ativa, Policy e scope municipal;
- dependencias Composer adequadas a XLSX e validacao de schemas.

Conclusoes principais:

- `ReportExport` podia continuar a ser o registo unico de exportacao;
- era necessaria metadata tipada para scope, fonte, lifecycle e hashes;
- o exporter legacy carregava datasets em memoria e tinha fallbacks de
  formato que nao podiam ser usados no novo dominio temporal;
- os lotes selados e publicacoes existentes forneciam fontes historicas
  autoritativas;
- era necessaria uma representacao canonica privada e temporaria;
- o storage `local` ja era privado, em `storage/app/private`;
- nao existia estado documental persistido de antivirus/quarentena.

## 6. Limitacoes encontradas

- O reporting legacy permanece sincrono e com fallbacks para os seus callers
  historicos; a sprint isolou o novo perfil em vez de alterar esse contrato.
- `delta_since_datetime` nao pode inventar historico a partir de
  `updated_at`; sem publicacao baseline autoritativa falha fechado.
- O modo `current_state` e operacional e nao oficial.
- Binarios documentais nao podem ser incluidos com seguranca enquanto faltar
  um estado verificavel de antivirus/quarentena.
- O pacote ZIP pode variar byte a byte entre plataformas; a verificacao
  autoritativa usa conteudos canonicos, manifesto e checksums SHA-256.
- Nao foram realizados testes de carga de 10.000/50.000 registos, reservados
  para a Sprint 53I.

## 7. Arquitetura final

```text
ReportExport lifecycle existente
        ^
TemporalApplicationResultExportService
        |
ApplicationResultExportSourceResolver
        |
ApplicationResultExportSnapshotBuilder
        |
CanonicalNdjsonStore (privado e temporario)
        |
CSV | JSON | XML | XLSX
        |
schemas + paridade + manifesto + checksums
        |
ZIP privado + move atomico + hash final
```

O controller e fino. Requests normalizam e validam input. O service compoe o
pedido, aplica autorizacao, cria `ReportRun`/`ReportExport`, envia o job depois
do commit e gere transicoes. Writers nunca reconsultam dados operacionais.

## 8. Compatibilidade legacy

- `ReportExportService::export()` e os callers existentes foram preservados.
- `ReportFormat` passou a conhecer XML e ZIP, mas disponibiliza
  `legacyCases()`, `legacyValues()` e `legacyOptions()` para o fluxo antigo.
- Requests e controllers genericos rejeitam a definicao temporal e formatos
  nao suportados pelo pipeline legacy.
- A listagem generica omite exports do perfil
  `temporal_application_results`.
- A definicao temporal encaminha o utilizador para o workflow dedicado.
- Nao foram removidas rotas, permissions, policies ou funcionalidades
  historicas.

## 9. Migration

Foi criada a migration incremental e reversivel:

`database/migrations/2026_08_01_000054_extend_report_exports_for_temporal_application_results.php`

Campos adicionados a `report_exports`:

- scope: `municipality_id`, `contest_id`;
- perfil/fonte: `export_profile`, `export_mode`, `snapshot_at`,
  `source_metadata`, `source_fingerprint`;
- integridade: `manifest_sha256`, `package_sha256`;
- lifecycle: `processing_stage`, `progress`, `started_at`, `failed_at`,
  `failure_code`;
- idempotencia e opcoes: `idempotency_key`, `formats`, `datasets`,
  `sensitive_fields_included`, `document_files_requested` e
  `document_files_included`.

Foram adicionadas FKs restritivas, indice municipal/perfil/data, indice de
concurso, indice de fingerprint e unicidade de `idempotency_key`. Nao existe
backfill municipal inferido. O rollback recusa executar quando existem
exports temporais, evitando perda silenciosa de metadata. O tratamento de
foreign keys no `down()` cobre SQLite e MySQL/MariaDB.

## 10. Enums e DTOs

Enums adicionados:

- `ApplicationResultExportMode`;
- `ApplicationResultExportFormat`;
- `ApplicationResultExportDataset`;
- `ApplicationResultExportSensitivity`;
- `ApplicationResultChangeType`;
- `ApplicationResultExportStage`.

DTOs readonly adicionados:

- `ApplicationResultExportSourceData`;
- `ApplicationResultExportSnapshotData`;
- `ApplicationResultExportFieldData`;
- `ApplicationResultExportFileData`;
- `ApplicationResultExportPackageData`;
- `ApplicationResultExportPackageOptionsData`;
- `ApplicationResultDocumentDossierData`;
- `ApplicationResultExportPreviewData`.

Os DTOs separam resolucao temporal, representacao canonica, configuracao do
pacote e apresentacao, reduzindo arrays implicitos nas fronteiras principais.

## 11. Catalogo de campos

`ApplicationResultExportFieldCatalog` centraliza codigo, label, tipo, origem,
sensibilidade, nulabilidade, modos, datasets e versao de schema.

Inventario observado:

| Dataset | Campos |
| --- | ---: |
| `applications` | 34 |
| `documents` | 16 |
| `findings` | 10 |
| `changes` | 11 |
| **Total** | **71** |

Os headers sao derivados do catalogo, nao de arrays independentes em cada
writer. O dataset `changes` so esta disponivel nos modos delta.

## 12. Classificacao de sensibilidade

Inventario observado no catalogo:

- `operational`: 56 campos;
- `process_reference`: 14 campos;
- `personal`: 1 campo (`candidate_name`);
- `highly_sensitive`: 0 campos exportaveis;
- `internal`: 0 campos exportaveis.

O export normal inclui apenas dados operacionais e referencias processuais.
O nome do candidato exige export sensivel, permission exata e confirmacao.
NIF, IBAN, rendimentos detalhados, saude, OCR, notas internas, nomes originais
e paths nao fazem parte do catalogo exportavel.

## 13. Modos temporais

| Modo | Fonte e comportamento |
| --- | --- |
| `current_state` | Leitura consistente do estado operacional; `official=false`. |
| `sealed_batch` | Usa exclusivamente lote selado e respetivos snapshots/hashes imutaveis. |
| `phase_snapshot` | Resolve deterministicamente a publicacao da fase em ou antes de `as_of`. |
| `delta_between_batches` | Compara dois lotes compativeis do mesmo Municipio/concurso por chaves estaveis. |
| `delta_since_datetime` | Compara publicacao baseline e alvo; falha fechado sem baseline reconstruivel. |
| `final_result` | Usa apenas o ultimo resultado oficial publicado por candidatura. |

O comparator distingue `added`, `removed`, `changed` e `unchanged`; este
ultimo e omitido por defeito. Estados documental, elegibilidade, scoring e
administrativo permanecem campos independentes.

## 14. Estrategia de consistencia

- `current_state` captura os dados numa transacao de leitura unica; em
  MySQL/MariaDB usa isolamento `REPEATABLE READ`.
- Fontes historicas sao validadas por Municipio, concurso, estado,
  temporalidade e hashes persistidos.
- A ordenacao canonica usa referencias/IDs estaveis, nunca ordem natural da
  base de dados.
- Todos os writers consomem o mesmo snapshot e nao executam queries por
  formato.
- Datas canonicas sao ISO 8601 em UTC.

## 15. Fonte canonica

`ApplicationResultExportSourceResolver` resolve e valida a fonte de cada modo.
`ApplicationResultExportSnapshotBuilder` materializa, por chunks, os datasets
autorizados. `CanonicalNdjsonStore` escreve e le:

- `applications.ndjson`;
- `documents.ndjson`;
- `findings.ndjson`;
- `changes.ndjson`, quando aplicavel.

Os ficheiros sao privados, UTF-8, ordenados, com JSON canonico e eliminados
depois de sucesso, falha ou expiracao. Nao contem objetos serializados, URLs
temporarias ou paths internos.

## 16. Source fingerprint

O fingerprint SHA-256 deriva de:

- versao do schema;
- modo e parametros canonicos;
- Municipio e concurso;
- referencias e hashes da fonte;
- checksums dos datasets NDJSON ordenados.

Nao inclui `now()` recalculado, paths, URLs, metadata do worker ou ordem
acidental da base. O fingerprint e persistido em `report_exports` e incluido
no manifesto.

## 17. CSV

- CSV real em UTF-8;
- BOM configuravel;
- delimitadores suportados: virgula, ponto e virgula e tab;
- headers estaveis derivados do catalogo;
- identificadores processuais serializados como texto;
- datas em ISO 8601;
- escrita incremental a partir do NDJSON;
- neutralizacao de formula injection para valores iniciados por `=`, `+`,
  `-` ou `@`.

A neutralizacao e apenas tabular e nao altera o valor canonico JSON/XML.

## 18. JSON

- JSON real, UTF-8, com `schema_version`, metadata de export e datasets;
- escrita incremental sem construir todos os arrays em memoria;
- chaves e ordem estaveis;
- sem `JSON_FORCE_OBJECT`, HTML ou mensagens de exception;
- validacao local contra JSON Schema versionado.

## 19. XML

- XML real produzido com `XMLWriter`;
- escrita incremental e UTF-8;
- namespace, versao e ordem estaveis;
- DTD e entidades externas nao sao usadas;
- validacao local contra XSD, sem fetch de recursos externos.

## 20. XLSX

- XLSX real produzido com OpenSpout `4.32.0`;
- biblioteca compativel com PHP 8.4 e licenciada sob MIT;
- folhas estaveis `Applications`, `Documents`, `Findings`, `Changes` e
  `Manifest`, de acordo com os datasets e modo selecionados;
- escrita streaming, filtros, primeira linha fixa e identificadores como
  texto;
- sem macros, formulas ou links externos;
- formula injection neutralizada;
- leitura de verificacao feita por OpenSpout nos testes.

Nao existe renomeacao de CSV/HTML para `.xlsx` no pipeline temporal.

## 21. Schemas

Foram adicionados:

- `schema/mvhab-application-results-v1.schema.json`;
- `schema/mvhab-application-results-v1.xsd`.

Ambos sao autocontidos, versionados e incluidos no pacote. JSON e XML sao
validados localmente antes da conclusao. A validacao JSON usa
`opis/json-schema` `2.6.0` (Apache-2.0), ja disponivel no lockfile.

## 22. Manifesto

`ApplicationResultExportManifestBuilder` produz `manifest.json` com:

- versoes do manifesto/schema;
- public ID, timestamps, Municipio, concurso, modo e carater oficial;
- tipo/referencias da fonte e fingerprint;
- fontes base/alvo para deltas;
- formatos, datasets, configuracao CSV e opcoes sensiveis;
- contagens por dataset;
- retencao, expiracao e avisos;
- path relativo, media type, tamanho, SHA-256, row count e schema de cada
  artefacto.

O manifesto nao inclui ator nominal, email, NIF, token, path interno ou
conteudo documental.

## 23. Checksums

- Cada artefacto recebe SHA-256.
- O manifesto e calculado depois dos payloads e tem hash proprio.
- `checksums.sha256` e escrito por ordem lexicografica e nao inclui o seu
  proprio hash.
- `manifest_sha256` e `package_sha256` sao persistidos.
- Depois do move final, o hash do ficheiro publicado e recalculado e comparado
  antes da conclusao do lifecycle.

## 24. Dossier documental

A opcao `include_document_files` e separada, desativada por defeito e exige:

- export sensivel;
- `reports.export_sensitive`;
- confirmacao sensivel;
- confirmacao documental;
- Policy e scope municipal;
- dataset `documents`.

O fluxo suporta `changed_documents_only` nos modos delta. Contudo, a auditoria
nao encontrou um estado persistido e fiavel de antivirus/quarentena. Por isso:

- nenhum binario e incluido;
- `document-index.csv` e gerado com identificador HMAC opaco;
- nomes originais, MIME nao verificado e paths ficam ausentes;
- cada item recebe `included=false` e
  `exclusion_reason=security_state_unavailable`;
- o manifesto e a UI declaram o aviso e
  `document_files_included=false`.

Este comportamento e deliberadamente fail-closed e constitui o gate de
deployment para dossier binario.

## 25. Lifecycle de ReportExport

`ReportExport` foi estendido, nao substituido. O status principal existente e
combinado com `ApplicationResultExportStage`:

```text
pending/queued
  -> processing/snapshotting
  -> processing/rendering
  -> processing/packaging
  -> completed/completed
  -> failed/failed
  -> expired/expired
```

O pedido cria `ReportRun` e `ReportExport` na mesma transacao. Progressos,
snapshot, hashes, ficheiro final e falhas seguras sao persistidos sob
`lockForUpdate()`. A conclusao so ocorre depois de schemas, ZIP e hash final
serem validados. O codigo de falha e tecnico e minimizado; nao e persistida
stack trace.

## 26. Jobs

`GenerateApplicationResultExport`:

- transporta apenas `reportExportId`;
- fila `reports`;
- `ShouldBeUniqueUntilProcessing`;
- 3 tentativas, timeout de 1800 segundos e `failOnTimeout`;
- backoff de 60, 300 e 900 segundos;
- enviado com `afterCommit()`.

`ExpireApplicationResultExport`:

- transporta apenas `reportExportId`;
- fila `reports`;
- `ShouldBeUnique`;
- 3 tentativas e timeout de 120 segundos.

Nao existe job por candidatura nem query independente por formato.

## 27. Storage

- Disk: `local`, privado.
- Staging: `report-exports/temporal/<public-id>/staging`.
- Final: `reports/<YYYY>/<MM>/<public-id>/<ficheiro>.zip`.
- Paths sao relativos e validados contra traversal, absolutos e segmentos
  inseguros.
- Entradas ZIP sao ordenadas, unicas, sem symlinks e com timestamp fixado ao
  snapshot quando suportado.
- O pacote e criado como `.partial`, renomeado atomicamente e depois movido
  para o destino final no mesmo disk.
- Nao sao criadas URLs publicas.

## 28. Retencao

A exportacao temporal usa a retencao observada no reporting existente: sete
dias. `expires_at` e persistido no pedido e incluido no manifesto. A base
preserva metadata tecnica e hashes depois da eliminacao do artefacto.

## 29. Expiracao

O comando read-only de selecao/agendamento
`reports:expire-temporal-exports`:

- seleciona apenas exports temporais concluidos e vencidos;
- processa IDs em `chunkById(100)`;
- envia jobs de expiracao;
- corre de hora a hora, `withoutOverlapping()` e `onOneServer()`.

O job volta a bloquear o registo, elimina pacote, staging e auxiliares, limpa
path/nome/tamanho, marca o estado como expirado e audita. Uma janela de cinco
minutos apos download evita a corrida expirar/download. Um export expirado nao
pode ser descarregado ou retomado.

## 30. Rotas

Foram adicionadas seis rotas backoffice dedicadas:

- `backoffice.reports.temporal-exports.index`;
- `backoffice.reports.temporal-exports.create`;
- `backoffice.reports.temporal-exports.preview`;
- `backoffice.reports.temporal-exports.store`;
- `backoffice.reports.temporal-exports.show`;
- `backoffice.reports.temporal-exports.download`.

Todas combinam autenticacao herdada do grupo, conta ativa, MFA, logging,
entitlement `applications.export`, permission exata aplicavel, Policy e scope
municipal. Nenhuma rota nova usa role fixa.

## 31. Permissions

Foram reutilizadas, sem criar novas permissions:

- `reports.view` para metadata/listagem;
- `reports.export` para pedido e download;
- `applications.export` para o dominio funcional;
- `reports.export_sensitive` para campos pessoais/dossier;
- `reports.audit` para consulta do auditor;
- entitlement `applications.export`.

`ReportPermissionService` valida explicitamente o catalogo de candidaturas.
Os templates globais de roles nao foram alterados.

## 32. Policies

`ReportExportPolicy` passou a suportar:

- `createTemporal`, negando candidate, auditor e utilizador sem Municipio;
- consulta scoped de metadata pelo operador autorizado;
- consulta read-only pelo auditor com `reports.view` e `reports.audit`;
- download novamente autorizado e negado ao auditor;
- permission sensivel no download quando o pacote foi pedido com campos
  pessoais ou documentos.

O controller aplica `Gate::authorize()` e o download reutiliza a Policy antes
de tocar no ficheiro.

## 33. Scope municipal

`MunicipalRecordScopeService` aplica `municipality_id` diretamente aos exports
temporais antes da paginacao. Concurso e lotes sao resolvidos no mesmo
Municipio do ator. Sao recusados:

- utilizador municipal sem Municipio;
- concurso fora do scope;
- lotes de Municipio ou concurso divergente;
- public ID de export de outro Municipio;
- operador global sem assignment estrutural explicito.

O fallback indireto por `ReportRun.user` e mantido apenas para exports legacy.

## 34. Auditoria

Eventos implementados:

- `application_result_export_previewed`;
- `application_result_export_requested`;
- `sensitive_application_result_export_requested`;
- `document_dossier_export_requested`;
- `application_result_export_started`;
- `application_result_export_snapshot_created`;
- `application_result_export_completed`;
- `application_result_export_failed`;
- `application_result_export_downloaded`;
- `application_result_export_expired`.

A metadata contem apenas IDs/referencias processuais, Municipio, concurso,
modo, formatos, datasets, contagens, flags, hashes, tamanho, estado e
timestamps. Nao contem payload exportado, PII, nome original, OCR, path,
exception, URL assinada ou token.

## 35. RGPD

- Privacidade por defeito e pseudonimizacao processual.
- Nome do candidato e o unico campo pessoal em whitelist e exige fluxo
  sensivel completo.
- NIF, IBAN, rendimento, saude/incapacidade, OCR, morada, email, telefone,
  notas e documentos nao sao exportados.
- Preview nao le nem copia binarios.
- Historico nao mostra disk, path, exception ou stack trace.
- Download revalida permission, Policy, Municipio, estado, expiracao e
  existencia do ficheiro.
- Candidate permanece fora do backoffice e auditor nao gera nem descarrega.

## 36. Performance

- Snapshot unico por exportacao.
- Queries scoped antes de contagens e chunking.
- `chunkById(250)` nos datasets operacionais.
- NDJSON privado evita colecoes integrais e permite writers streaming.
- CSV, JSON, XML e XLSX nao reconsultam a base.
- XLSX usa OpenSpout em streaming.
- Listagens usam limites e paginacao de 30 exports.
- Selecao no formulario limita concursos a 100 e lotes a 200.
- Expiracao usa `chunkById(100)`.
- Nao existem queries nas views.

Os testes de 10.000 e 50.000 registos continuam reservados para a Sprint 53I.

## 37. Concorrencia e idempotencia

- `idempotency_key` SHA-256 inclui ator, Municipio, concurso, token do cliente,
  modo, fontes, formatos, datasets e opcoes.
- Constraint unica e `lockForUpdate()` eliminam pedidos duplicados.
- O job e unico ate iniciar processamento.
- Dois workers sobre o mesmo export convergem para um unico estado final.
- Apenas um snapshot, manifesto, pacote e hash final sao publicados.
- Retry limpa staging anterior e nao regenera exports concluidos/expirados.
- Falha parcial elimina `.partial`, staging e qualquer final nao validado.
- Download e expiracao voltam a bloquear/revalidar o registo.

O teste MySQL com dois processos PHP independentes confirmou um unico pacote,
hash correto, staging vazio e um unico evento de inicio, snapshot e conclusao.

## 38. Testes executados

Resultados observados:

- PHPUnit integral: **1573 testes, 23238 assercoes - PASS**.
- UX: **135 testes, 664 assercoes - PASS**.
- Suite dirigida reporting/temporal: **53 testes, 293 assercoes - PASS**.
- Pacote, schemas, XLSX, ZIP, checksums e paridade:
  **12 testes, 84 assercoes - PASS**.
- Regressao reporting, Sprints 53B-53F, seguranca, Agenda e Dashboard:
  **162 testes, 3141 assercoes - PASS**.
- Concorrencia MySQL: **1 teste, 17 assercoes - PASS**.
- Lifecycle temporal: **4 testes, 17 assercoes - PASS**.
- Auditoria de rotas dirigida: **2 testes, 546 assercoes - PASS**.

Cobertura adicionada inclui seis modos, T1/T2/T3, baseline ausente,
canonicalizacao, fingerprint, comparator, redaction, formula injection,
schemas, XLSX real, ZIP, manifesto, checksums, preview, pedido assincrono,
lifecycle, idempotencia, falha, expiracao, download, IDOR, Municipio A/B,
candidate, auditor, MFA, conta/role e concorrencia real.

## 39. Gates finais

Resultados observados antes do commit final:

- `composer validate --strict`: PASS.
- `composer audit --locked`: PASS, sem advisories.
- `composer check-platform-reqs`: PASS.
- PHPStan integral: **0 erros**.
- Pint integral: PASS.
- Pint dirigido/incremental: PASS em **76 ficheiros PHP** alterados desde o
  commit-base.
- `php artisan optimize:clear`: PASS.
- SQLite migration up/down/up: PASS.
- MySQL migration up/down/up: PASS em base temporaria eliminada no fim.
- `npm run build`: PASS com Vite `8.0.16`; CSS `86.25 kB`, JS `45.25 kB`.
- Integridade dos testes: **0 violacoes, 0 avisos** em 13 ficheiros de teste
  alterados desde o commit-base.
- `git diff --check`: PASS.

Nenhum resultado acima constitui evidencia de deploy ou validacao em
producao.

## 40. Auditoria de rotas

Resultados observados:

- rotas sem vendor: **1199** (baseline 1193; +6 rotas temporais legitimas);
- nomes duplicados: **0**;
- rotas totais auditadas: **1202**;
- rotas com permission middleware: **937**;
- rotas backoffice com role fixa: **0**;
- rotas candidate com role fixa herdada: **216**;
- novas rotas temporais com guards completos: **6**;
- rotas removidas acidentalmente: nenhuma detetada pela regressao/baseline.

## 41. Ficheiros alterados

O conjunto integral da sprint afeta **81 ficheiros**, agrupados em:

- DTOs de exportacao em `app/Data/Reports/`;
- enums em `app/Enums/`;
- controller e requests dedicados em
  `app/Http/Controllers/Backoffice/Reporting/` e
  `app/Http/Requests/Reporting/`;
- jobs em `app/Jobs/`;
- extensoes a `ReportExport`, `ReportExportPolicy`, scope, permissions,
  download e compatibilidade legacy;
- fonte, snapshot, comparator, catalogo, writers, schemas, dossier, manifesto,
  package builder e lifecycle em `app/Services/Reporting/Temporal/`;
- migration incremental de `report_exports`;
- definicao idempotente em `database/seeders/ReportDefinitionSeeder.php`;
- tres views em `resources/views/backoffice/reports/temporal-exports/` e links
  de integracao nas views legacy;
- `schema/mvhab-application-results-v1.schema.json` e
  `schema/mvhab-application-results-v1.xsd`;
- scheduler e seis rotas;
- testes unitarios, feature, seguranca, lifecycle e concorrencia;
- `composer.json`/`composer.lock` para OpenSpout;
- este relatorio.

Nao foram alteradas regras de elegibilidade, scoring, decisoes
administrativas, publicacoes ou documentos.

## 42. Estado Git

- Base e branch foram confirmadas antes da implementacao.
- Os tres primeiros macroblocos possuem commits proprios.
- O Bloco D e este relatorio sao fechados no quarto commit obrigatorio.
- A publicacao deve usar push normal com upstream, nunca force push.
- A igualdade entre SHA local e remoto deve ser confirmada apos o push.
- Nao foi efetuado merge em `main`.

## 43. Riscos residuais

1. **Gate documental:** binarios continuam excluidos ate existir estado
   persistido, auditavel e fiavel de antivirus/quarentena.
2. **Deployment:** migration, worker da fila `reports`, scheduler e storage
   privado precisam de ser aplicados/configurados no ambiente alvo.
3. **Carga:** o desenho usa chunking/streaming, mas cenarios de 10.000/50.000
   e carga concorrente transversal pertencem a Sprint 53I.
4. **ZIP multiplataforma:** o hash do pacote e valido no artefacto produzido;
   reproducao semantica entre plataformas depende dos checksums canonicos,
   nao apenas de igualdade byte a byte do ZIP.
5. **Retencao:** sete dias replica a politica tecnica observada; qualquer
   alteracao juridica futura deve ser feita numa configuracao/politica formal.

## 44. Exclusoes

Ficaram fora da Sprint 53G:

- alteracao global de roles, novo perfil municipal, rate limiting global e
  matriz final de permissions (Sprint 53H);
- carga 10.000/50.000, chaos testing e observabilidade transversal
  (Sprint 53I);
- PDF, assinatura digital qualificada e selo temporal externo;
- arquivo municipal externo, email automatico, API publica e SFTP;
- alteracao retroativa de snapshots;
- elegibilidade/exclusao/aperfeicoamento automaticos;
- exportacao de campos fora da whitelist;
- inclusao de binarios sem prova de seguranca.

## 45. Preparacao da Sprint 53H

A Sprint 53G deixa preparado para a Sprint 53H:

- perfil temporal isolado em `ReportExport`;
- permissions existentes identificadas e aplicadas de forma exata;
- seis rotas sem roles fixas e com guards completos;
- Policy com auditor read-only e candidate excluido;
- scope municipal direto e fail-closed;
- eventos de auditoria e lifecycle tipados;
- report definition idempotente;
- testes que protegem entitlement, permission, MFA, conta ativa, scope e
  ausencia de role fixa.

A Sprint 53H pode evoluir templates de roles, perfil municipal, rate limiting
e matriz final de permissions sem reabrir os contratos temporais, formatos ou
package builder.

## Decisao final

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O codigo, testes, schemas, migrations e gates de repositorio passam. O deploy
nao foi executado nem e declarado. A geracao estruturada CSV/JSON/XML/XLSX e
ZIP municipal esta pronta no repositorio; a inclusao de binarios documentais
permanece bloqueada ate existir prova tecnica persistida de
antivirus/quarentena.
