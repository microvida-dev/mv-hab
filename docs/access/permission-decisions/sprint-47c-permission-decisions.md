# Decisões de permissions — Sprint 47C

## Âmbito

Este registo resolve as 40 lacunas ou propostas semanticamente insuficientes
do manifesto imutável da Sprint 47C. O lote abrange configuração técnica de
elegibilidade e classificação, execução e revisão de scoring, decisões
administrativas, decisões de reclamação, classificação de IA documental e
atualização do ranking após sorteio.

As outras 38 rotas do manifesto mantiveram uma permission semanticamente
adequada. No total, as 78 rotas usam 29 permissions finais distintas.

## Regras comuns

- Todas as rotas exigem `auth`, `active.backoffice`, `mfa.backoffice` e
  `log.backoffice`.
- Cada rota tem exatamente um middleware `permission:*` e nenhum middleware
  `role:*` ativo.
- A permission é confirmada pela Policy, que bloqueia `candidate`, mantém
  `auditor` sem mutações e valida o scope municipal do registo.
- A configuração técnica de matrizes e critérios não depende de um
  entitlement candidatural: 45 rotas ficam sem `municipality.feature`.
- As 33 operações sobre candidaturas, decisões, IA, scoring e ranking exigem
  `municipality.feature:applications.review`.
- Não foram atribuídas permissions diretamente a utilizadores nem criados
  wildcards.
- `administrative_decisions` expõe apenas `view`, `create`, `approve` e
  `cancel`; permissions sem rota efetiva não foram mantidas.
- Criação e edição de matrizes não aceitam `status`. A criação produz sempre
  `draft`, a edição preserva o estado e as transições usam as permissions
  próprias.

## Permissions criadas e reutilizadas

Novas permissions:

- `administrative_decisions.view`, `create`, `approve`, `cancel`;
- `eligibility.activate`, `deactivate`, `archive`, `duplicate`;
- `scoring.review`, `run`, `lock`, `cancel`, `activate`, `deactivate`,
  `archive`, `duplicate`, `delete`;
- `complaints.decide`, `complaints.cancel`.

Permissions reutilizadas:

- `documents.view`, `documents.review_ai`;
- `eligibility.view`, `eligibility.create`, `eligibility.update`;
- `scoring.view`, `scoring.create`, `scoring.update`;
- `complaints.view`, `complaints.approve`.

As permissions já existentes `eligibility.run`, `approve`, `reject`, `audit`
e `scoring.approve`, `reject`, `audit` permanecem no catálogo. A 47C não
inventou rotas para as usar quando não existem no manifesto.

## Perfis e modelos municipais

| Família | Perfis autorizados pelo catálogo | Perfis recusados ou limitados |
| --- | --- | --- |
| Configuração de elegibilidade | administrator e municipal_technician; jury apenas leitura | candidate bloqueado no backoffice; auditor sem mutação; restantes sem permission |
| Configuração de scoring | administrator e municipal_technician; jury sem gestão de configuração | candidate, auditor, legal/financial/maintenance/support sem permission |
| Scoring operacional | administrator, municipal_technician e jury conforme `view/review/run/lock/cancel` | auditor e candidate bloqueados; restantes sem permission |
| Decisões administrativas | administrator, municipal_technician, jury e legal_manager | auditor read-only por princípio, mas sem concessão neste lote; candidate bloqueado |
| Decisões de reclamação | administrator, municipal_technician e legal_manager para decidir; jury consulta/aprova/cancela | candidate e auditor fora das mutações |
| IA documental | perfis com `documents.view`; revisão apenas administrator e municipal_technician | candidate não entra no backoffice; auditor não recebe mutação |
| Ranking pós-sorteio | administrator, municipal_technician e jury com `scoring.run` | auditor, candidate e restantes perfis sem permission |

O modelo `analista-candidaturas` recebeu apenas
`eligibility.run`, `administrative_decisions.view` e
`administrative_decisions.create`, além das permissions que já possuía.
Não recebeu scoring, aprovação/cancelamento de decisões ou poderes de júri.
O modelo `operador-recolha` não recebeu novos poderes decisórios.

## Evidência de testes

| Código | Teste |
| --- | --- |
| `RTE` | `EligibilityScoringPermissionRoutesTest` |
| `BND` | `EligibilityScoringMunicipalBoundaryTest` |
| `ELG` | `Sprint7EligibilityEngineTest` |
| `SCR` | `Sprint10ScoringRankingTest` |
| `CMP` | `Sprint11ListsComplaintsHearingTest` |
| `LOT` | `LotteryClosureFlowTest` |
| `DAI` | `DocumentAiClassificationPanelTest` |
| `AUD` | `AuditAccessRoutesCommandTest` |

## Matriz das 40 decisões semânticas

| # | Rota | Proposta | Permission final | Tipo | Ability | Razão | Testes |
| ---: | --- | --- | --- | --- | --- | --- | --- |
| 1 | `backoffice.administrative-decisions.approve` | `administrative_processes.approve` | `administrative_decisions.approve` | nova | `approveBackoffice` | Aprovar uma decisão é poder próprio e auditável. | RTE, BND |
| 2 | `backoffice.administrative-decisions.cancel` | nenhuma | `administrative_decisions.cancel` | nova | `cancelBackoffice` | Cancelamento é uma transição distinta. | RTE, BND |
| 3 | `backoffice.administrative-decisions.create-admission` | `administrative_processes.create` | `administrative_decisions.create` | nova | `createBackoffice` | Preparar admissão não equivale a criar o processo. | RTE, BND |
| 4 | `backoffice.administrative-decisions.create-non-admission` | `administrative_processes.create` | `administrative_decisions.create` | nova | `createBackoffice` | Preparar não admissão usa o mesmo poder decisório de criação. | RTE, BND |
| 5 | `backoffice.administrative-decisions.show` | `administrative_processes.view` | `administrative_decisions.view` | nova | `viewBackoffice` | A leitura da decisão é separada da leitura do processo. | RTE, BND |
| 6 | `backoffice.administrative-decisions.store-admission` | `administrative_processes.create` | `administrative_decisions.create` | nova | `createBackoffice` | A persistência da proposta exige permission decisória própria. | RTE, BND |
| 7 | `backoffice.administrative-decisions.store-non-admission` | `administrative_processes.create` | `administrative_decisions.create` | nova | `createBackoffice` | A persistência da não admissão não herda criação de processo. | RTE, BND |
| 8 | `backoffice.complaint-decisions.approve` | `administrative_processes.approve` | `complaints.approve` | reutilizada | `approveBackoffice` | A aprovação pertence ao domínio da reclamação. | RTE, CMP |
| 9 | `backoffice.complaint-decisions.cancel` | nenhuma | `complaints.cancel` | nova | `cancelBackoffice` | Cancelar decisão é transição autónoma. | RTE, CMP |
| 10 | `backoffice.complaint-decisions.create` | `administrative_processes.create` | `complaints.decide` | nova | `decideBackoffice` | O formulário inicia uma decisão sobre reclamação. | RTE, CMP |
| 11 | `backoffice.complaint-decisions.show` | `administrative_processes.view` | `complaints.view` | reutilizada | `viewBackoffice` | A decisão mantém o domínio e scope da reclamação. | RTE, CMP |
| 12 | `backoffice.complaint-decisions.store` | `administrative_processes.create` | `complaints.decide` | nova | `decideBackoffice` | Persistir resultado exerce poder de decisão. | RTE, CMP |
| 13 | `backoffice.document-ai.assistant.score` | `scoring.view` | `documents.view` | reutilizada | `viewBackoffice` | O score é metadado de análise documental, não classificação municipal. | RTE, DAI |
| 14 | `backoffice.document-ai.classifications.index` | `scoring.view` | `documents.view` | reutilizada | `viewAnyBackoffice` | A listagem pertence à IA documental. | RTE, DAI |
| 15 | `backoffice.document-ai.classifications.manual-review` | `scoring.update` | `documents.review_ai` | reutilizada | `reviewBackoffice` | Revisão humana de IA não altera scoring oficial. | RTE, DAI |
| 16 | `backoffice.document-ai.classifications.show` | `scoring.view` | `documents.view` | reutilizada | `viewBackoffice` | O detalhe continua no domínio documental privado. | RTE, DAI |
| 17 | `backoffice.eligibility.criteria.activate` | nenhuma | `eligibility.activate` | nova | `activateBackoffice` | Ativação não pode ser inferida de update. | RTE, BND, ELG |
| 18 | `backoffice.eligibility.criteria.inactivate` | nenhuma | `eligibility.deactivate` | nova | `deactivateBackoffice` | Desativação exige poder próprio. | RTE, BND, ELG |
| 19 | `backoffice.eligibility.rule-sets.activate` | nenhuma | `eligibility.activate` | nova | `activateBackoffice` | Ativação da matriz altera a regra aplicável. | RTE, BND, ELG |
| 20 | `backoffice.eligibility.rule-sets.archive` | `eligibility.update` | `eligibility.archive` | nova | `archiveBackoffice` | Arquivo é transição terminal distinta. | RTE, BND, ELG |
| 21 | `backoffice.eligibility.rule-sets.duplicate` | `eligibility.create` | `eligibility.duplicate` | nova | `duplicateBackoffice` | Duplicação copia critérios e merece poder explícito. | RTE, BND, ELG |
| 22 | `backoffice.lottery-draws.ranking.update` | `scoring.view` | `scoring.run` | reutilizada | `createBackoffice` | A operação cria snapshot pós-sorteio e executa atualização de ranking. | RTE, BND, LOT |
| 23 | `backoffice.scoring.application-scores.lock` | nenhuma | `scoring.lock` | nova | `lockBackoffice` | Bloqueio impede alterações posteriores. | RTE, BND, SCR |
| 24 | `backoffice.scoring.application-scores.manual-review` | `scoring.update` | `scoring.review` | nova | `reviewBackoffice` | Abrir revisão manual é distinto de editar configuração. | RTE, BND, SCR |
| 25 | `backoffice.scoring.application-scores.manual-review.update` | `scoring.update` | `scoring.review` | nova | `reviewBackoffice` | A revisão humana usa poder próprio e auditável. | RTE, BND, SCR |
| 26 | `backoffice.scoring.criteria.activate` | nenhuma | `scoring.activate` | nova | `activateBackoffice` | Ativação de critério não é update genérico. | RTE, BND, SCR |
| 27 | `backoffice.scoring.criteria.inactivate` | nenhuma | `scoring.deactivate` | nova | `deactivateBackoffice` | Inativação de critério tem permission separada. | RTE, BND, SCR |
| 28 | `backoffice.scoring.ranking-snapshots.archive` | `scoring.update` | `scoring.archive` | nova | `archiveBackoffice` | Arquivar ranking interno é transição própria. | RTE, BND, SCR |
| 29 | `backoffice.scoring.ranking-snapshots.lock` | nenhuma | `scoring.lock` | nova | `lockBackoffice` | Bloqueio do snapshot exige poder explícito. | RTE, BND, SCR |
| 30 | `backoffice.scoring.rule-sets.activate` | nenhuma | `scoring.activate` | nova | `activateBackoffice` | Ativar matriz altera a configuração aplicável. | RTE, BND, SCR |
| 31 | `backoffice.scoring.rule-sets.archive` | `scoring.update` | `scoring.archive` | nova | `archiveBackoffice` | Arquivo não pode ser enviado pelo formulário de edição. | RTE, BND, SCR |
| 32 | `backoffice.scoring.rule-sets.duplicate` | `scoring.create` | `scoring.duplicate` | nova | `duplicateBackoffice` | Copiar regras e desempates requer permission dedicada. | RTE, BND, SCR |
| 33 | `backoffice.scoring.rules.destroy` | nenhuma | `scoring.delete` | nova | `deleteBackoffice` | Eliminação é destrutiva e sensível. | RTE, BND, SCR |
| 34 | `backoffice.scoring.runs.cancel` | nenhuma | `scoring.cancel` | nova | `cancelBackoffice` | Cancelamento da execução é transição específica. | RTE, BND, SCR |
| 35 | `backoffice.scoring.runs.create` | `scoring.create` | `scoring.run` | nova | `runAnyBackoffice` | O formulário prepara execução, não configuração. | RTE, BND, SCR |
| 36 | `backoffice.scoring.runs.lock` | nenhuma | `scoring.lock` | nova | `lockBackoffice` | Bloqueio da execução usa permission própria. | RTE, BND, SCR |
| 37 | `backoffice.scoring.runs.run` | nenhuma | `scoring.run` | nova | `runBackoffice` | Executar classificação é ação operacional. | RTE, BND, SCR |
| 38 | `backoffice.scoring.runs.store` | `scoring.create` | `scoring.run` | nova | `runAnyBackoffice` | Criar a execução não deve depender de criar matrizes. | RTE, BND, SCR |
| 39 | `backoffice.scoring.tie-breakers.activate` | nenhuma | `scoring.activate` | nova | `activateBackoffice` | Ativação do desempate altera ordenação potencial. | RTE, BND, SCR |
| 40 | `backoffice.scoring.tie-breakers.inactivate` | nenhuma | `scoring.deactivate` | nova | `deactivateBackoffice` | Inativação do desempate requer permission própria. | RTE, BND, SCR |

## Decisão mixed-context: reclamações

As cinco rotas `complaint-decisions` ficam na 47C porque ligam:

```text
reclamação
→ decisão administrativa
→ possível alteração da lista definitiva e do ranking
```

Foi preservado `applications.review`. As decisões leem dados da candidatura e
podem marcar uma atualização da lista; não são simples operações gerais de
reclamação. O restante domínio de reclamações, audiências, listas e publicação
permanece na 47D.

## Decisão mixed-context: ranking pós-sorteio

`backoffice.lottery-draws.ranking.update` usa `scoring.run`, não
`scoring.view` nem `scoring.update`. A ação cria um
`RankingUpdateRun`, preserva o ranking anterior e regista o snapshot
pós-sorteio. Requer `applications.review`, MFA, `RankingUpdateRunPolicy` e
`ownsLotteryDraw()`.

A foreign key real foi confirmada no model, migration e service como
`lottery_run_id`. O teste valida também que o registo criado é recuperável por
`MunicipalRecordScopeService::rankingUpdateRuns()`.

## Scope municipal

Foram adicionados ou reforçados scopes fail-closed para:

- decisões administrativas, reclamações e decisões de reclamação;
- conjuntos e critérios de elegibilidade;
- conjuntos, critérios, regras e desempates de scoring;
- execuções, pontuações e snapshots;
- sorteios e atualizações de ranking.

`municipality_id = null` não concede acesso. Programa e concurso são as fontes
municipais autoritativas. No sorteio também são considerados o programa do
concurso e o programa da lista definitiva. Relações ausentes não concedem
scope.

## Correções ao metadado do manifesto imutável

O manifesto não foi reescrito. A implementação confirmou estes alvos reais:

- criação de decisão administrativa: `AdministrativeDecisionPolicy` recebe
  o processo como contexto, embora o manifesto indique
  `AdministrativeProcessPolicy`;
- criação/persistência de decisão de reclamação:
  `ComplaintDecisionPolicy`, embora o manifesto indique `ComplaintPolicy`;
- atualização de ranking: `RankingUpdateRunPolicy`, embora o manifesto
  indique `LotteryDrawPolicy`;
- listagem de classificações IA:
  `DocumentAiAnalysisPolicy`, não indicada no manifesto.

As abilities e o scope são os definidos no manifesto; apenas a classe Policy
responsável é a do recurso efetivamente criado/consultado.

## Decisão

As 40 lacunas foram resolvidas sem role fixa, wildcard, permission direta ou
bypass de MFA/feature/Policy. As transições de estado não podem ser executadas
por payloads de criação/edição, o Município A não acede ao B, recusas não
criam mutações e os dois contextos mistos ficam formalmente delimitados.
