# Sprint 53G - Exportacao temporal, dossier municipal e artefactos reproduziveis

## Identificacao

- Branch: `sprint-53g-temporal-application-result-exports`
- Commit-base: `f8872fef447c84cc8e934b332008b22963ae6bc0`
- Branch de origem: `sprint-53f-differential-revalidation-second-closure`
- Estado deste documento: auditoria inicial e contrato de implementacao

## Resumo executivo

A Sprint 53G estende o reporting existente para produzir exportacoes temporais
do dominio de candidaturas e resultados. `ReportDefinition`, `ReportRun` e
`ReportExport` permanecem a infraestrutura unica de catalogo, execucao,
autorizacao, storage, download e auditoria. Os exports legacy conservam o
comportamento atual; o novo pipeline temporal nao usa os fallbacks historicos
de XLSX para CSV ou de PDF para HTML.

Todos os formatos temporais serao produzidos a partir de uma unica captura
canonica privada, ordenada e imutavel. Os lotes selados e publicacoes das
Sprints 53C-53F constituem as fontes historicas autoritativas. O estado
operacional atual sera capturado numa transacao de leitura consistente. Na
ausencia de baseline historico reconstruivel, os modos delta falham fechado.

## Auditoria inicial

### Reporting existente

Foram auditados:

- `ReportDefinition`, `ReportRun`, `ReportExport` e respetivas relations;
- `ReportExportService`, `ReportRunService`, `ReportPermissionService`,
  `ReportAccessLogger` e `ReportDownloadService`;
- `CsvReportExporter`, `HtmlReportExporter`, `ReportFormat`,
  `ReportExportStatus`, `ExportScope` e `ReportAccessType`;
- controllers, Form Requests, Policies, rotas, factories, seeders e views de
  reporting;
- migration base `2026_06_15_020000_create_reporting_tables.php`;
- storage privado `local`, cujo root e `storage/app/private`.

Conclusoes:

1. `ReportExport` pode continuar a ser o registo unico da exportacao.
2. `ReportRun` continua a ligar a exportacao ao catalogo e ao ator, mas nao
   persiste um dataset escalavel; e necessaria uma representacao intermedia.
3. O export legacy executa todo o dataset em memoria.
4. O export legacy degrada XLSX para CSV e PDF para HTML. Este contrato sera
   preservado apenas para callers legacy.
5. O export legacy conclui sincronicamente no request HTTP.
6. `ReportExport` ja suporta public ID, ator, estado, disk, path privado,
   tamanho, conclusao, expiracao e download auditado.
7. A expiracao atual e verificada no download, mas nao existe limpeza fisica
   agendada dos exports de reporting.
8. A retencao legacy observada e de sete dias. A exportacao temporal reutiliza
   esta duracao atraves de configuracao explicita.
9. `ReportAccessLogger` e `AuditLogger` sao reutilizaveis, desde que a metadata
   seja minimizada e nao contenha filtros pessoais, paths ou conteudo.
10. O scope atual de reports e indireto pelo utilizador do `ReportRun`. Para o
    dominio temporal serao persistidos `municipality_id` e `contest_id`, e o
    scope sera aplicado diretamente antes de contagens, paginacao e snapshot.

### Programa 53

Foram auditados:

- `ApplicationReviewBatch` e `ApplicationReviewBatchItem`;
- `ApplicationReviewPublication` e
  `ApplicationReviewPublicationResult`;
- ciclos, outcomes, estados, hashes, fingerprints e timestamps;
- `CorrectionRequest`, `CorrectionResponse` e
  `CorrectionSubmissionReceipt`;
- builders e servicos de selagem, publicacao e revalidacao diferencial;
- `AdministrativeProcess`, `ContestApplicationPhaseService`, elegibilidade,
  scoring e decisoes administrativas.

Conclusoes:

- lotes selados sao imutaveis e possuem `source_fingerprint`,
  `snapshot_hash`, ciclo, sequencia e `sealed_at`;
- itens de lote possuem snapshot tipado, documentos e achados, com hash
  individual;
- publicacoes sao imutaveis, oficiais e possuem um unico `published_at`;
- resultados publicados distinguem outcome documental de decisao
  administrativa;
- `complete_pending_decision` nao equivale a admissao;
- `correction_rejected` nao equivale a exclusao;
- o recibo formal de aperfeicoamento e uma fronteira temporal imutavel;
- os snapshots 53C/53F sao a fonte de verdade de `sealed_batch`;
- as publicacoes 53D/53F sao a fonte de verdade de `phase_snapshot`,
  `delta_since_datetime` e `final_result`.

### Documentos privados

Foram auditados `DocumentSubmission`, `DocumentVersion`, `DocumentType`,
`RequiredDocument`, `DocumentAccessService`, o dossier documental existente,
discos, checksums e MIME types.

Conclusoes:

- ficheiros e versoes vivem em storage privado;
- existem checksum, MIME, tamanho, versao e ligacao ao requisito;
- o download normal e novamente autorizado e auditado;
- paths e nomes originais existem no modelo e nao podem entrar nos datasets;
- nao foi encontrado um estado persistido de antivirus ou quarentena;
- o dossier temporal so incluira binarios atraves de whitelist de MIME,
  existencia, checksum, versao temporal, Municipio e estado documental;
- qualquer indicio de quarentena/malware que venha a existir sera bloqueante;
- a ausencia de prova suficiente torna o ficheiro uma exclusao registada no
  indice, nunca uma inclusao silenciosa.

### Seguranca e isolamento municipal

As permissions existentes sao suficientes:

- `reports.view` para consulta;
- `reports.export` para pedido e download;
- `reports.export_sensitive` para campos pessoais autorizados e documentos;
- `reports.audit` para auditoria;
- `applications.export` para o dominio de candidaturas;
- entitlement `applications.export`.

As rotas temporais usarao cumulativamente `auth`, conta ativa, MFA, logging,
entitlement, permission exata, Policy e scope municipal fail-closed. Candidato
permanece fora do backoffice. Auditor pode consultar metadata autorizada, mas
nao gerar, cancelar ou descarregar exports. Operador global necessita de scope
estrutural explicito; a ausencia de Municipio ou assignment falha fechado.

### Dependencias e XLSX

Nao existia uma biblioteca XLSX direta no projeto. Foi auditado OpenSpout
`4.32.0`:

- PHP suportado: `~8.3.0 || ~8.4.0 || ~8.5.0`;
- licenca MIT;
- escrita e leitura XLSX orientadas a streaming;
- extensoes exigidas `dom`, `filter`, `libxml`, `xmlreader` e `zip` presentes
  no ambiente observado;
- sem macros, formulas ou links externos necessarios ao caso de uso.

Decisao: adicionar `openspout/openspout:^4.32` no Bloco C. Nao sera construido
XLSX manualmente e nenhum ficheiro CSV/HTML sera renomeado para XLSX.

`opis/json-schema` ja esta presente transitivamente no lockfile por Laravel.
O schema JSON sera autocontido e a validacao sera executada localmente. XML
usara `XMLWriter`/`DOMDocument` com XSD local, DTD e rede desativados.

## Arquitetura escolhida

```text
ReportExport lifecycle
        ^
TemporalApplicationResultExportService
        |
ApplicationResultExportSourceResolver
        |
ApplicationResultExportSnapshotBuilder
        |
NDJSON privado e canonico
        |
CSV | JSON | XML | XLSX
        |
validacao de schemas e paridade
        |
manifesto + checksums
        |
ZIP privado + conclusao atomica
```

Nao sera criada uma nova tabela ou um segundo agregado de exportacao. Uma
migration incremental estendera `report_exports` com colunas tipadas para
scope, modo, snapshot, lifecycle e hashes. Metadata variavel permanecera em
JSON. Dados exportados e manifestos integrais nao serao guardados na base de
dados.

## Matriz de modos

| Modo | Fonte autoritativa | Oficial | Regra |
| --- | --- | --- | --- |
| `current_state` | estado atual capturado numa leitura consistente | nao | todos os writers leem o mesmo NDJSON |
| `sealed_batch` | lote e itens imutaveis | nao | exige lote `sealed` e hashes validos |
| `phase_snapshot` | ultima publicacao da fase em ou antes de `as_of` | sim | Municipio, concurso, fase e timestamp obrigatorios |
| `delta_between_batches` | dois lotes imutaveis | nao | mesmo Municipio/concurso e ordem temporal valida |
| `delta_since_datetime` | publicacao baseline e publicacao alvo | depende da fonte | falha fechado sem baseline historico |
| `final_result` | ultimo resultado oficial publicado por candidatura | sim | rascunhos e estados mutaveis sao excluidos |

## Matriz de formatos

| Formato | Implementacao | Validacao |
| --- | --- | --- |
| CSV | writer streaming, UTF-8, BOM/delimitador configuraveis | parser e contagens |
| JSON | writer streaming com schema versionado | JSON Schema local |
| XML | `XMLWriter` streaming | XSD local, sem DTD/rede |
| XLSX | OpenSpout streaming | leitor OpenSpout e paridade |
| ZIP | `ZipArchive`, entradas ordenadas e paths seguros | integridade, manifesto e SHA-256 |

O formato fisico final da exportacao temporal e ZIP. Os formatos selecionados
aparecem na metadata, UI e manifesto. Nao existe degradacao silenciosa.

## Catalogo inicial de campos

O catalogo central `ApplicationResultExportFieldCatalog` definira, por campo:

- codigo e label;
- tipo;
- origem;
- sensibilidade;
- nulabilidade;
- modos e datasets disponiveis;
- versao de schema.

Datasets:

- `applications`: estados independentes da submissao, revisao documental,
  elegibilidade, scoring e decisao administrativa;
- `documents`: requisito, tipo, alvo, instancia, periodo, estado, versao,
  datas, checksum e carry-forward;
- `findings`: achados estruturados e decisoes, sem texto livre por defeito;
- `changes`: alteracoes por chave estavel, com redacao de valores sensiveis.

## Classificacao de sensibilidade

| Classe | Exemplos | Export normal | Export sensivel |
| --- | --- | --- | --- |
| operacional | codigos, estados, contagens, datas, hashes | incluido | incluido |
| pseudonimo processual | candidatura e processo | incluido | incluido |
| pessoal | nome, email, telefone | omitido | apenas whitelist |
| altamente sensivel | NIF, IBAN, rendimento, saude, OCR | proibido | proibido nesta versao |
| interno | notas, grounds, nomes originais, paths | proibido | proibido |
| documental binario | versao privada validada | nao | opcao separada e confirmada |

## Consistencia temporal e fingerprint

`current_state` sera capturado em `REPEATABLE READ` no MySQL/MariaDB e numa
transacao de leitura unica nos drivers de teste. A transacao abrange apenas a
resolucao e escrita do NDJSON privado; os exporters nao voltam a consultar as
tabelas operacionais.

As fontes historicas sao verificadas contra os hashes persistidos. A ordem
canonica usa IDs/referencias estaveis, nunca a ordem natural da base. Datas sao
ISO 8601 UTC. O source fingerprint deriva de:

- versao do schema;
- modo e configuracao canonica;
- Municipio e concurso;
- referencias/hashes das fontes;
- checksums dos NDJSON ordenados.

Nao entram no fingerprint `now()`, paths, URLs, worker metadata ou objetos
serializados.

## Retencao, staging e expirar

- staging: `temporal-exports/staging/<public-id>/`, privado;
- final: `temporal-exports/packages/<ano>/<mes>/<public-id>/...zip`, privado;
- TTL: sete dias, configurado e alinhado com reporting legacy;
- move final atomico dentro do mesmo disk;
- falha elimina staging e nao deixa pacote descarregavel;
- sucesso elimina NDJSON e payloads temporarios depois do ZIP validado;
- expiracao elimina pacote e qualquer staging/auxiliar remanescente;
- a base preserva apenas metadata tecnica e hashes.

## Plano de migration

Estender `report_exports` de forma nullable e compativel com registos legacy:

- `municipality_id`, `contest_id`;
- `export_mode`, `profile`, `snapshot_at`;
- `source_metadata`, `source_fingerprint`;
- `manifest_sha256`, `package_sha256`;
- `processing_stage`, `progress`;
- `started_at`, `failed_at`, `failure_code`.

Serao adicionados indices para scope municipal, concurso/modo e fila. Nao
existira backfill municipal inferido. O rollback sera fail-closed perante
exports temporais persistidos para impedir perda silenciosa de metadata.

## Plano de jobs

Um job orquestrador idempotente recebe apenas o ID do `ReportExport`:

1. bloqueia o registo;
2. captura a fonte e NDJSON uma unica vez;
3. gera os formatos selecionados;
4. valida schemas e paridade;
5. gera manifesto, checksums e ZIP;
6. move atomico;
7. conclui o `ReportRun` e `ReportExport`;
8. limpa staging.

Retries usam lock, estado e ficheiros temporarios limpos. Um export concluido
ou expirado nao e regenerado. O job usa tentativas, timeout e backoff
explicitos e e enviado `afterCommit()`.

## Plano de testes

- unitarios: modos, fonte, catalogo, canonicalizacao, fingerprint, deltas,
  redacao e temporalidade T1/T2/T3;
- formatos: CSV injection, JSON Schema, XSD, XLSX real e paridade;
- pacote: manifesto, checksums, ZIP, paths, documentos e exclusoes;
- feature: preview, pedido assincrono, lifecycle, download, expiracao e falha;
- seguranca: permission/entitlement/scope independentes, Municipios A/B,
  candidato, auditor, MFA, conta/role e IDOR;
- concorrencia MySQL: workers, finalizacao, expiracao/download e retry;
- regressao: reporting legacy, Sprints 53B-53F, documentos e auditoria.

## Ficheiros previstos

- migration incremental de `report_exports`;
- enums e DTOs em `app/Enums` e `app/Data/Reports`;
- services em `app/Services/Reporting/Temporal`;
- exporters em `app/Services/Reporting/Exporters/Temporal`;
- job e comando de expiracao;
- controller e Form Requests dedicados;
- extensoes de model, Policy, scope e download;
- rotas e views backoffice;
- schemas em `schema/`;
- seeder de definicao de relatorio;
- testes unitarios, feature, seguranca, migration e concorrencia.

## Riscos e decisoes

- `delta_since_datetime` so e suportado quando existe uma publicacao baseline
  em ou antes de `since`; sem ela, falha fechado.
- snapshots legacy podem possuir schema 1 ou 2. O normalizador aceita apenas
  contratos conhecidos e nao inventa campos ausentes.
- documentos sem prova suficiente de integridade/MIME/versao sao declarados
  no indice como excluidos.
- o ZIP pode variar byte a byte entre plataformas; a verificacao autoritativa
  usa checksums canonicos dos conteudos e manifesto.
- os exports legacy mantem os fallbacks atuais nesta sprint; a UI temporal
  mostra apenas formatos reais.
- nao serao alterados templates globais de roles; a Sprint 53H permanece a
  fronteira para essa mudanca.

## Estado dos blocos

- 53G-A: auditoria concluida; commit em preparacao.
- 53G-B: pendente.
- 53G-C: pendente.
- 53G-D: pendente.

## Classificacao provisoria

`IMPLEMENTATION_IN_PROGRESS`
