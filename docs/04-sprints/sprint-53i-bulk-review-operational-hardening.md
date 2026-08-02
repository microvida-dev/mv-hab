# Sprint 53I - Observabilidade, performance, resiliencia e fecho operacional

## 1. Resumo executivo

A Sprint 53I fecha o Programa 53 ao nivel do repositorio sem alterar a
semantica funcional entregue nas Sprints 53A a 53H. Foram adicionados um
harness isolado de benchmark, observabilidade interna sem PII, classificacao
tipada de falhas, checkpoints recuperaveis para exports, health check
read-only, testes reais de worker e concorrencia MySQL, um cenario demo
deterministico e a documentacao operacional completa.

Os cenarios de 1.000, 10.000 e 50.000 candidaturas passaram sem OOM e com
memoria constante. A suite integral, PHPStan, Pint, Composer, Vite, auditorias
de acesso, migrations isoladas e verificacoes documentais passaram. Como nao
houve deploy nem validacao do ambiente alvo, a decisao e
REPOSITORY_PASS_DEPLOYMENT_GATED.

## 2. Commit-base

67d08f1dba67b955dccbc2089613d9d299f3934c

## 3. Branch

sprint-53i-bulk-review-operational-hardening

Nao foi feito merge de main nem force push.

## 4. Commits

- 6ea0da92 - docs(sprint-53i): audit operational hardening and scale scenarios
- 90971c70 - perf(program53): validate bulk review and export at scale
- b80f9398 - feat(program53): add operational observability and failure recovery
- docs(program53): close operational program and production runbooks

O SHA do quarto commit corresponde ao tip final desta branch e e confirmado no
relatorio de execucao depois da criacao do proprio commit.

## 5. Auditoria inicial

- Working tree inicial limpa e commit-base confirmado.
- Laravel 13.12.0, PHP 8.4.21, MySQL 9.6.0, Node 24.11.0 e Composer 2.9.8.
- Ambiente local: cache file, queue efetiva sync, storage local privado e logs
  stack/single.
- Nao existem dependencias diretas de Horizon, Pulse, OpenTelemetry,
  Prometheus, StatsD, Sentry ou Telescope.
- Foram auditados os dominios de revisao em lote, publicacao,
  aperfeicoamentos, notificacoes, export temporal, seguranca, filas, scheduler,
  storage e migrations 53A a 53H.
- Foi identificado que o timeout de 1.800 segundos do job de exportacao exige
  retry_after e worker timeout coerentes no ambiente alvo.
- Nao foi adicionada dependencia externa apenas para observabilidade.

## 6. Arquitetura de observabilidade

Foi implementada uma camada interna e desacoplada:

- Program53OperationalContext para correlation e identificadores limitados;
- Program53ContextRedactor para remocao fail-closed de dados sensiveis;
- Program53MetricsRecorder como contrato substituivel;
- StructuredLogProgram53MetricsRecorder como adapter local;
- Program53Failure, Program53FailureCode e Program53FailureDisposition;
- Program53OperationalHealthService e comando read-only
  program53:operational-check;
- Program53FaultInjector com implementacoes Noop e controlada apenas para
  testes.

As labels sao de baixa cardinalidade e nao incluem nomes, emails, numeros de
candidatura, paths absolutos, conteudo documental ou payloads administrativos.

## 7. Harness de benchmark

O comando program53:benchmark usa base SQLite e storage dedicados, seed
deterministico, queue database e relatorios JSON/Markdown. O comando:

- recusa producao;
- recusa bases e paths fora do perfil isolado;
- recusa queue sync;
- valida volume, analistas, Municipios, formatos e memory limit;
- produz metricas, checksums e warnings sem credenciais ou PII;
- suporta limpeza integral e exit code nao zero em gate falhado.

Os componentes principais sao Program53BenchmarkConfiguration,
Program53BenchmarkEnvironment, Program53ScaleScenarioBuilder,
Program53BenchmarkRunner, Program53BenchmarkMetrics e
Program53BenchmarkReportWriter.

## 8. Datasets

Os dados sao sinteticos e deterministas. A distribuicao inclui candidaturas
completas e incompletas, documentos validos, ausentes, rejeitados e
substituidos, pedidos respondidos e sem resposta, revalidacao, selagem,
publicacao, notificacoes e exportacao nos quatro formatos.

As operacoes administrativas criticas passam pelos Services oficiais. A
preparacao volumosa usa insercao em chunks, sem carregar o dataset integral em
memoria. As percentagens sao parametros tecnicos e nao regras municipais.

## 9. Cenario 1.000

PASS:

- 1.000 candidaturas;
- 3.840 linhas exportadas;
- 3,326929 segundos;
- 38.273.024 bytes de peak memory;
- 66 queries;
- 300,577 candidaturas/segundo;
- pacote ZIP de 739.586 bytes;
- CSV, JSON, XML e XLSX com manifest e checksums.

Artefactos:
storage/qa/program53-benchmark-smoke-1k-1000.json e .md.

## 10. Cenario 10.000

PASS:

- 10.000 candidaturas;
- 38.487 linhas exportadas;
- 32,448897 segundos;
- 38.273.024 bytes de peak memory;
- 259 queries;
- 308,177 candidaturas/segundo;
- pacote ZIP de 7.071.311 bytes;
- chunking e writers NDJSON sem reconsulta da base.

Artefactos:
storage/qa/program53-benchmark-scale-10k-10000.json e .md.

## 11. Cenario 50.000

PASS:

- 50.000 candidaturas;
- 192.615 linhas exportadas;
- 179,257872 segundos;
- 38.273.024 bytes de peak memory;
- 1.059 queries;
- 278,928 candidaturas/segundo;
- pacote ZIP de 35.211.923 bytes;
- sem OOM e sem colecao integral do dataset.

Artefactos:
storage/qa/program53-benchmark-hard-50k-50000.json e .md.

## 12. Hardware e ambiente

Baseline local observada:

- Apple M3;
- 8 CPUs;
- 8 GiB RAM;
- cerca de 4,4 GiB livres no inicio;
- PHP memory_limit do benchmark: 256 MiB;
- MySQL 9.6.0 para concorrencia e migrations;
- SQLite para o harness isolado;
- queue database nos cenarios que validam filas.

Os tempos sao comparaveis apenas no mesmo ambiente e nao constituem SLA legal
ou contratual.

## 13. Memoria

O budget tecnico foi o menor valor praticavel entre CI e 512 MiB. Todos os
cenarios correram com 256 MiB e registaram peak constante de 38.273.024 bytes,
incluindo 50.000 candidaturas. Isto confirma que snapshot, writers e package
operam em streaming.

## 14. Tempos

No cenario de 50.000:

- dataset: 1,207 segundos;
- revisao: 0,176 segundos;
- selagem: 0,111 segundos;
- publicacao: 0,440 segundos;
- snapshot: 3,434 segundos;
- pacote com quatro formatos: 173,601 segundos.

O custo dominante e a producao dos quatro artefactos e do ZIP, nao a leitura
administrativa nem a base de dados.

## 15. Throughput

O throughput observado ficou entre 278,928 e 308,177 candidaturas/segundo e
entre 1.088,005 e 1.197,403 linhas exportadas/segundo. Estes valores formam uma
baseline local; regressao temporal so deve ser avaliada em hardware e
configuracao equivalentes.

## 16. Queries

Foram medidas 66, 259 e 1.059 queries para 1k, 10k e 50k. O crescimento e
proporcional aos chunks de 50 e nao ao numero de linhas dos writers. Os
writers consomem o snapshot NDJSON e nao reconsultam a base por linha.

Foi corrigida a resolucao canonica de documentos repetiveis para impedir que
um documento de membro do agregado seja confundido com o target candidatura.
As relacoes necessarias sao carregadas antecipadamente.

## 17. Indices

O EXPLAIN QUERY PLAN confirmou uso dos indices de scope municipal, fila do
analista, publicacao/resultados e queue no schema de benchmark. Nao foi
adicionado indice de producao por intuicao e a Sprint 53I nao cria migration.
Nao foram observados N+1 criticos no percurso medido.

## 18. Multiplos analistas

O modelo de oito analistas foi validado no harness e as garantias de claim,
readiness e conflito permanecem suportadas por scope, fingerprints,
lockForUpdate e unique constraints. Testes MySQL usam conexoes/processos
independentes nos pontos concorrentes do dominio.

## 19. Concorrencia

Numa base MySQL temporaria passaram:

- Sprint53FCorrectionConcurrencyTest;
- TemporalApplicationResultExportConcurrencyTest;
- Program53RoleTemplateConcurrencyTest.

Resultado: 3 testes, 53 assercoes, zero lost updates e convergencia
idempotente. O fluxo mantem um resultado canonico por operacao e rejeita
fontes stale sem substituir snapshots ou hashes finais.

## 20. Locks

Os estados oficiais continuam protegidos por transacoes, lockForUpdate,
unique constraints e fingerprints. Cache locks coordenam apenas execucoes e
nunca substituem a base como fonte de verdade. A ordem deterministica de
acesso reduz a superficie de deadlocks.

## 21. Deadlocks

Deadlock e classificado como database_deadlock/retryable, com backoff limitado
e logging redigido. Falhas de scope, autorizacao, schema e source fingerprint
sao terminais ou exigem uma nova operacao. Nao foi observada perda ou escrita
parcial nos testes MySQL.

## 22. Queue hardening

Os jobs do Programa 53 foram auditados e endurecidos quanto a queue, tries,
timeout, backoff, retryUntil, afterCommit, unicidade e failed handler.
Program53DatabaseQueueWorkerRecoveryTest executou um worker database real,
interrompeu o processo e provou retry/recuperacao: 1 teste e 8 assercoes.

O health check avisa quando queue sync, retry_after ou heartbeat externo nao
permitem garantir a operacao alvo.

## 23. Retries

Program53FailureClassifier separa retryable de terminal:

- retryable: indisponibilidade temporaria de storage, deadlock e falhas
  transitorias;
- terminal: scope/autorizacao revogada, schema invalido, source stale e pacote
  corrompido;
- retries sao limitados e nunca contornam permission, entitlement, MFA ou
  scope municipal.

## 24. Fault injection

ControlledProgram53FaultInjector permite falhas apenas em testing/benchmark e
nao possui rota HTTP. No ambiente normal, NoopProgram53FaultInjector e o
binding ativo. Foram cobertos checkpoints de selagem, publicacao, notificacao,
snapshot, package, storage e expiracao sem expor PII.

## 25. Notification recovery

Falhas parciais preservam entregas concluidas e retomam apenas itens
pendentes/falhados. O contexto operacional e os codigos de falha sao
registados sem corpo de mensagem, email ou identificadores pessoais. Os testes
confirmam que retry nao duplica a entrega logica nem o resultado publicado.

## 26. Export recovery

ApplicationResultExportCheckpointStore guarda checkpoints tecnicos e valida
ownership, fingerprint, checksum e estado. Um snapshot NDJSON completo pode
ser reutilizado; staging incompleto ou corrompido e eliminado; export
concluido nao e regenerado; export expirado nao e retomado.

O pacote final so fica disponivel depois do move atomico e da validacao de
hash. Os testes de recuperacao cobrem interrupcao, retry, source stale e
package corrompido.

## 27. Storage

Todos os artefactos permanecem em storage privado e usam paths relativos.
Ficheiros .partial nao sao descarregaveis. O cenario demo gera dois ZIP
temporais, ambos com hash SHA-256 verificado, e o harness limpa bases e
storages isolados depois de cada volume.

## 28. Scheduler

schedule:list confirmou:

- corrections:expire a cada cinco minutos;
- reports:expire-temporal-exports de hora a hora.

As operacoes mantem withoutOverlapping/onOneServer quando aplicavel e os
Services sao idempotentes. A disponibilidade real de scheduler e cache
partilhado continua a ser gate de deploy.

## 29. Retencao

A politica tecnica configurada de sete dias nao foi alterada. Foram
documentados metadata, artefactos, expiração, delete, retry, orphan cleanup,
backup e corrida com download. Uma alteracao futura da retencao exige
aprovacao municipal/RGPD e nao pode ser inferida pelo codigo.

## 30. DST

Os testes cobrem transicoes de primavera e outono em Europe/Lisbon, conversao
UTC, prorrogação e serializacao ISO 8601. Nao foi criado qualquer prazo legal.
Publicacao, aperfeicoamento e export preservam o instante canonico.

## 31. Multi-Municipio

O cenario demo contem dois Municipios, duas candidaturas visiveis no Municipio
principal e uma candidatura de controlo. A verificacao confirmou:

- primary_visible_applications = 2;
- control_visible_applications = 1;
- cross_access_denied = true.

Scope, locks, roles, exports, health e metricas nao usam scope global.

## 32. Seguranca

Foram preservados permissions, entitlements, Policies, MFA, rate limiting e
scope municipal da Sprint 53H. A auditoria Programa 53 executou 474
verificacoes, 0 falhas e drift=false. O inventario de rotas manteve zero rotas
backoffice com role fixa, zero novos wildcards e zero permissions diretas.

Candidate continua fora do backoffice; auditor continua read-only; exportacao
sensivel continua separada.

## 33. RGPD

Metricas, logs, benchmark e relatorios excluem nomes, emails, NIF, numeros de
candidatura, documentos, payloads, paths internos e conteudo administrativo.
Os dados de benchmark e demo sao ficticios. O payload do worker transporta
IDs minimos e nao modelos Eloquent serializados com relacoes.

## 34. Metricas

Foram instrumentadas duracao, resultado, failure code, retry, tamanho,
contagens e fases com labels limitadas. O contrato Program53MetricsRecorder
permite adapter futuro para APM sem acoplar o dominio. A referencia de
metricas, cardinalidade e thresholds tecnicos esta em
docs/operations/program-53-observability-design.md.

## 35. Logs

Os logs estruturados incluem operacao, correlation ID, Municipio, concurso,
lote/export, fase, duracao, resultado e codigo de falha quando aplicavel.
Program53ContextRedactor aplica allow-list e elimina valores sensiveis e
cardinalidade livre. Nao sao registadas mensagens brutas de exceptions com
PII.

## 36. Health command

program53:operational-check e read-only e suporta table, JSON e Markdown, com
fail-on-warning/fail-on-critical. A execucao final devolveu:

- 24 verificacoes;
- 21 info;
- 3 warning;
- 0 critical.

Os warnings locais sao esperados e explicitos: queue sync, retry_after nao
validavel nesse driver e ausencia de heartbeat externo de worker. Nenhum foi
silenciado; todos sao gates operacionais do ambiente alvo.

## 37. Migrations

A Sprint 53I nao cria migration.

Validacoes isoladas:

- SQLite: fresh up completo passou; as ultimas nove migrations do Programa 53
  passaram rollback/reapply com migrate --step (86 migrations executadas);
- MySQL temporario: migrations do Programa 53 passaram up/down/up e a base foi
  eliminada no fim;
- bases temporarias MySQL remanescentes: 0.

O rollback integral e ingenuo de todo o historico SQLite para numa migration
herdada anterior ao Programa 53,
2026_07_26_005952_add_municipal_scope_to_visit_domain_tables, porque consulta
information_schema.TABLE_CONSTRAINTS. Nao foi alterada fora do escopo. Isto nao
invalida o gate up/down/up do conjunto Programa 53, mas permanece risco
tecnico herdado.

## 38. Schemas

JSON Schema e XSD v1 permanecem inalterados e validos. O catalogo operacional
e derivado dos enums reais e cobre codigo, label, dominio, descricao, estado
terminal, caracter oficial e versao de schema. Os testes verificam paridade de
CSV, JSON, XML e XLSX e detetam codigo documentado inexistente ou enum sem
catalogo.

## 39. Seeder

MunicipalApplicationDemoProgram53Seeder foi integrado no orquestrador demo.
O cenario e deterministico, idempotente, restrito a ambiente demo/local/testing
explicitamente autorizado e contem:

- 2 Municipios;
- 2 candidaturas Programa 53 no Municipio principal e 1 de controlo;
- 3 lotes, 4 itens, 3 publicacoes e 4 resultados;
- 1 recibo imutavel;
- 1 pedido expirado sem resposta;
- 9 notificacoes oficiais no cenario total;
- 2 exports temporais completed, sealed_batch e delta_between_batches;
- CSV, JSON, XML e XLSX em ambos os pacotes;
- isolamento municipal confirmado.

O comando normal e o --verify-only produziram JSON byte a byte identico. Os
testes provam que verify-only nao altera dados e que o seeder e idempotente.

## 40. Documentacao operacional

Foram criados/finalizados:

- docs/programs/program-53-closure-report.md;
- docs/operations/program-53-process.bpmn;
- docs/operations/program-53-process.md;
- docs/operations/program-53-state-matrix.md;
- docs/operations/program-53-export-code-catalog.md;
- docs/operations/program-53-analyst-manual.md;
- docs/operations/program-53-deadline-configuration-manual.md;
- docs/operations/program-53-queue-runbook.md;
- docs/operations/program-53-retention-policy.md;
- docs/operations/program-53-observability-runbook.md;
- docs/operations/program-53-failure-recovery-matrix.md;
- docs/quality/program-53-performance-report.md.

O BPMN 2.0 e XML bem formado e contem as lanes candidato, tecnico municipal,
sistema e filas. Matriz, catalogo e manuais sao verificados por testes contra
enums, permissions, formatos e estrutura reais.

## 41. Testes

Resultados observados:

- PHPUnit integral: 1.651 testes, 23.888 assercoes, PASS;
- UX canonica: 135 testes, 664 assercoes, PASS;
- Programa 53 dirigido: 142 testes, 1.194 assercoes, PASS;
- documentacao operacional: 4 testes, 194 assercoes, PASS;
- worker database kill/retry: 1 teste, 8 assercoes, PASS;
- concorrencia MySQL: 3 testes, 53 assercoes, PASS;
- seeder/command dirigidos: 8 testes, 90 assercoes, PASS;
- regressao de target canonico, temporal e seeder: 15 testes,
  144 assercoes, PASS.

Nao foi usado php artisan test como substituto da validacao final: a suite
integral foi executada diretamente por PHPUnit.

## 42. Gates

PASS:

- composer validate --strict;
- composer audit --locked, sem advisories;
- composer check-platform-reqs;
- PHPStan integral, 0 erros;
- Pint incremental e integral;
- PHPUnit integral e UX canonica;
- optimize:clear;
- Vite build;
- test integrity: 20 ficheiros alterados, 0 critical, 0 warnings;
- route:list, auditorias de acesso e Programa 53;
- health sem critical;
- benchmarks 1k/10k/50k;
- migrations SQLite/MySQL do Programa 53;
- concorrencia MySQL;
- worker database real;
- BPMN/XML, schemas, paridade, retencao e DST;
- git diff --check.

## 43. Rotas

Nao foram adicionadas rotas HTTP. Os comandos novos sao Artisan.

Resultados:

- route:list --except-vendor: 1.199;
- rotas totais auditadas: 1.202;
- rotas nomeadas: 1.196;
- permission middleware: 937;
- backoffice com role fixa: 0;
- candidate com role fixa herdada: 216;
- nomes duplicados: 0;
- Programa 53: 474 verificacoes, 0 falhas, drift=false.

## 44. Estado Git

O trabalho ficou limitado a quatro commits coesos na branch da sprint. O
estado final limpo e a igualdade entre SHA local e origin sao confirmados
depois do commit de fecho e push, sem merge em main e sem force push.

## 45. Riscos residuais

- O ambiente local usa queue sync por defeito; workers database/reports,
  notificacoes e heartbeat devem ser configurados e monitorizados no alvo.
- retry_after, worker timeout e stopwait precisam de calibracao conjunta antes
  do deploy.
- Nao existe APM externo; o repositorio fornece contratos, logs estruturados e
  runbook, mas o adapter/alerting do ambiente ainda e externo.
- A geracao dos quatro formatos e do ZIP domina o tempo em 50k; dimensionar
  workers, storage e janela operacional com base no hardware alvo.
- Existe uma limitacao herdada no rollback integral SQLite anterior ao
  Programa 53, descrita na seccao 37.
- A validacao automatica nao substitui acessibilidade manual nem smoke
  municipal pos-deploy.

## 46. Deployment gates

Continuam obrigatoriamente fechados ate validacao no ambiente alvo:

- backup e restore testado;
- migration real;
- cache partilhado com locks atomicos;
- worker reports;
- workers de notificacoes;
- scheduler;
- storage privado e permissoes de filesystem;
- geracao e download reais;
- MFA;
- entitlements e assignments municipais;
- monitorizacao, dashboards e alertas;
- validacao manual de acessibilidade;
- smoke operacional pos-deploy;
- rollback operacional ensaiado.

## 47. Exclusoes

Nao foi realizado deploy. Nao foram alteradas regras legais, prazos
regulamentares, eligibility, scoring, listas, contratos ou retencao. Nao foram
adicionados PDF, assinatura qualificada, selo temporal externo, arquivo
municipal externo, API publica, SFTP, APM comercial, novo RBAC, novo motor de
notificacoes ou dados reais.

## 48. Decisao final

REPOSITORY_PASS_DEPLOYMENT_GATED
