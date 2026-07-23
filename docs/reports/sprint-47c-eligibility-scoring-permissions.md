# Sprint 47C — Elegibilidade, classificação e decisões permission-first

## Resumo executivo

A Sprint 47C migrou as 78 rotas do manifesto de elegibilidade, classificação,
decisões administrativas, decisões de reclamação, classificação de IA
documental e atualização de ranking pós-sorteio para autorização
permission-first.

As rotas combinam agora:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação exata>
&& municipality.feature:applications.review, quando operacional
&& Policy com scope municipal do registo
```

Classificação final: **REPOSITORY_PASS_DEPLOYMENT_GATED**.

O repositório, testes e build estão aprovados. Permanecem apenas gates externos
já conhecidos: bootstrap de operador da 47A.0, regularização municipal dos
dados por ambiente e aplicação das migrations 47A/47B em ambientes
persistentes antes do rollout.

## Referências Git

- branch: `sprint-47c-eligibility-scoring-permissions`;
- commit-base funcional:
  `be591d9d42dc21987a761f93c5695fe92b2ee697`;
- `a67c3dd7` — manifesto imutável da 47C;
- `ff5d6bb1` — implementação permission-first, Policies, Requests e scope;
- `201cfcf0` — fixtures reais de reclamações e sorteios;
- `12bde5ce` — testes de rotas, limites municipais e snapshot progressivo;
- `300852a7` — relatório e registo das decisões semânticas;
- `e1b5e88c` — estabilização das fixtures temporais da agenda;
- documentação de fecho atualizada antes da publicação.

## Manifesto e reconciliação

O ficheiro
`docs/access/manifests/sprint-47c-route-manifest.json` foi fixado antes da
implementação e mantido imutável:

- 78 route names únicos;
- 78 rotas existentes na Route Collection;
- zero rotas candidate;
- 78 rotas sem middleware `role:*` ativo;
- 78 rotas com `auth`, `active.backoffice`, `mfa.backoffice` e
  `log.backoffice`;
- 78 rotas com uma única permission exata;
- 29 permissions distintas;
- 33 rotas operacionais com `applications.review`;
- 45 rotas de configuração técnica sem entitlement artificial.

O teste percorre o manifesto e valida o middleware efetivamente resolvido,
incluindo a exclusão explícita do grupo legacy de roles.

## Permissions

Foram distinguidas:

- decisões administrativas: `view`, `create`, `approve`, `cancel`;
- reclamações mixed-context: `view`, `decide`, `approve`, `cancel`;
- elegibilidade: `view`, `create`, `update`, `activate`, `deactivate`,
  `archive`, `duplicate`;
- classificação: `view`, `create`, `update`, `review`, `run`, `lock`,
  `cancel`, `activate`, `deactivate`, `archive`, `duplicate`, `delete`;
- IA documental: `documents.view` e `documents.review_ai`.

O catálogo continua a conter as permissions oficiais de `run`, aprovação,
rejeição e auditoria que já existiam noutros fluxos. Não foram criadas rotas
ou permissions sem uso. Em particular, `administrative_decisions` ficou
limitado às quatro ações efetivamente presentes no manifesto.

O `administrator` e o `municipal_technician` receberam o conjunto necessário.
O `jury` recebeu apenas leitura/revisão/execução/bloqueio e decisões previstas.
O `legal_manager` recebeu decisões administrativas e de reclamação, sem
scoring. O modelo `analista-candidaturas` recebeu apenas elegibilidade
operacional e criação/leitura de propostas administrativas.

## FeatureKeys e contextos mistos

Foi reutilizada exclusivamente `applications.review`.

Distribuição:

| Contexto | Rotas |
| --- | ---: |
| Decisões administrativas | 7 |
| Decisões de reclamação | 5 |
| IA documental | 4 |
| Scoring operacional | 16 |
| Ranking pós-sorteio | 1 |
| Configuração técnica sem feature | 45 |

As cinco rotas de decisão de reclamação permanecem na 47C porque uma decisão
pode alterar a lista definitiva e o ranking. O restante domínio de
reclamações, audiências, listas e publicação fica na 47D.

A atualização pós-sorteio usa `scoring.run`: cria um snapshot de
`RankingUpdateRun` e não é leitura nem edição de configuração.

## Policies

Foram criadas ou reforçadas abilities backoffice específicas em 15 Policies:

- `AdministrativeDecisionPolicy` e `ComplaintDecisionPolicy`;
- `DocumentAiAnalysisPolicy` e `DocumentAiScorePolicy`;
- Policies de conjunto/critério de elegibilidade;
- Policies de conjunto, critério, regra e desempate de scoring;
- `ScoringRunPolicy`, `ApplicationScorePolicy` e `RankingSnapshotPolicy`;
- `RankingUpdateRunPolicy`.

As abilities distinguem `viewAnyBackoffice`, `viewBackoffice`,
`createBackoffice`, `updateBackoffice`, `reviewBackoffice`,
`runAnyBackoffice`, `runBackoffice`, `lockBackoffice`,
`activateBackoffice`, `deactivateBackoffice`, `archiveBackoffice`,
`duplicateBackoffice`, `deleteBackoffice`, `approveBackoffice`,
`cancelBackoffice` e `decideBackoffice`.

A auditoria estática revelou e corrigiu a ausência de
`ScoringCriterionPolicy::viewBackoffice`, necessária ao índice de regras de
scoring. O teste prova acesso local e recusa cross-municipality.

## Form Requests

Foram revistos 22 Form Requests:

- nenhum mantém `authorize(): true`;
- usam a mesma ability do controller;
- confirmam o modelo associado à rota;
- não aceitam `municipality_id`;
- IDs de programa, concurso e matriz são validados no Município autenticado;
- preservam as restantes validações funcionais.

Foi ainda fechado um bypass de estados:

- criar conjunto de elegibilidade/scoring produz sempre `draft`;
- editar não aceita nem altera `status`;
- ativar, desativar e arquivar exigem as rotas e permissions próprias;
- parâmetros `status` forjados são ignorados e não causam mutação.

## Isolamento municipal

`MunicipalRecordScopeService` foi reforçado com:

- `administrativeDecisions()` e `ownsAdministrativeDecision()`;
- `complaints()`, `ownsComplaint()`, `complaintDecisions()` e
  `ownsComplaintDecision()`;
- `eligibilityRuleSets()` e `eligibilityCriteria()`;
- `scoringRuleSets()`, `scoringCriteria()`, `scoringRules()` e
  `tieBreakerRules()`;
- `scoringRuns()`, `applicationScores()` e `rankingSnapshots()`;
- `lotteryDraws()`, `ownsLotteryDraw()` e `rankingUpdateRuns()`.

Os scopes são fail-closed. Município nulo não concede acesso, Município A não
consulta nem altera B e relações inexistentes não abrem o registo. Programa e
concurso são fontes autoritativas. No sorteio são considerados também o
programa da lista definitiva e a cadeia do concurso.

A foreign key de `RankingUpdateRun` foi confirmada como `lottery_run_id` no
model, migration e service. O teste valida a consulta municipal do registo
após a criação.

## Controllers e queries

Foram atualizados 14 controllers. Continuam finos:

- autorizam antes de consultar/mutar;
- filtram listagens pelo `MunicipalRecordScopeService`;
- usam eager loading e paginação já existentes;
- delegam cálculo, scoring, decisão e auditoria nos Services atuais.

Não foram alteradas regras de elegibilidade, fórmulas de pontuação, ranking,
listas, decisão administrativa ou sorteio.

## MFA, auditoria e feedback

- `mfa.backoffice` está presente nas 78 rotas;
- as novas ações sensíveis foram adicionadas ao catálogo por permission
  completa;
- `log.backoffice` está presente nas 78 rotas;
- os eventos de auditoria dos Services foram preservados;
- recusas HTML continuam a usar o feedback seguro da Sprint 46D;
- recusas JSON devolvem autorização negada sem mutação;
- nenhum payload técnico, NIF, documento privado ou caminho interno foi
  adicionado ao feedback.

## Regressões encontradas e correções

### Decisão de reclamação

O teste legacy aceitava qualquer redirect e a fixture não tinha Município,
feature e MFA coerentes. Foi corrigido sem bypass:

- utilizador, programa, concurso e reclamação partilham o Município;
- `applications.review` está ativo apenas nesse Município;
- MFA é confirmado na sessão;
- `complaints.decide` e `ownsComplaint()` são verificados;
- o teste exige mensagem de sucesso, ausência de erros, decisão persistida,
  redirect exato e estado final `Accepted`.

### Ranking após sorteio

O teste legacy também tratava redirect de recusa como sucesso. Foi alinhado:

- Município e feature reais;
- `scoring.run`;
- MFA;
- `ownsLotteryDraw()`;
- redirect exato e mensagem de sucesso;
- persistência e recuperação municipal de `RankingUpdateRun`.

### Estados de matrizes

A revisão final detetou que `create/update` ainda podiam receber `status`.
Essa entrada foi removida das views e Requests. O teste cobre tentativa de
ativação/arquivo por payload com apenas permissions de criação/edição.

### Fixtures de agenda dependentes da hora

O gate pós-commit executado às 23:11 revelou duas fixtures que usavam
`now()->addHour()` mas esperavam um evento de `today()`. Após as 23:00, o
evento passava para o dia seguinte. As datas de vistoria e visita foram
fixadas às 12:00 do próprio dia, sem alterar providers ou comportamento de
produção.

## Testes

Cobertura criada/reforçada:

- manifesto e middleware efetivo das 78 rotas;
- permission, feature e Policy independentes;
- Município A/B e IDs relacionais externos;
- candidate, auditor, role inativa e MFA;
- criação/edição sem transição implícita de estado;
- decisões administrativas e de reclamação;
- scoring, ranking e atualização pós-sorteio;
- IA documental sem confusão com scoring oficial;
- baseline real do comando de auditoria.

Resultados:

- regressões de reclamação/sorteio: 8 testes, 90 asserções;
- lote dirigido principal: 34 testes, 1 604 asserções;
- filtro Eligibility: 35 testes, 1 221 asserções;
- filtro Scoring: 23 testes, 1 162 asserções;
- filtro Ranking: 9 testes, 52 asserções;
- filtro Document AI: 41 testes, 233 asserções;
- PHPUnit completo: 1 129 testes, 10 827 asserções, PASS;
- filtro UX: 130 testes, 645 asserções, PASS;
- integridade de testes: zero violações críticas e zero avisos.

## Quality gates

- `composer validate --strict`: PASS;
- `php artisan optimize:clear`: PASS;
- Pint incremental: PASS;
- Pint global: PASS;
- PHPStan global: 0 erros;
- PHPUnit completo: PASS;
- PHPUnit UX: PASS;
- `php artisan route:list --except-vendor`: PASS, 1 170 rotas;
- `php artisan migrate:status`: PASS, todas as migrations locais `Ran`;
- `npm run build`: PASS;
- `git diff --check`: PASS;
- auditoria de testes enfraquecidos: resultado vazio.

## Inventário antes/depois

| Métrica | Depois da 47B | Depois da 47C | Delta |
| --- | ---: | ---: | ---: |
| Rotas totais | 1 170 | 1 170 | 0 |
| Rotas com role fixa | 752 | 674 | -78 |
| Rotas backoffice com role fixa | 532 | 454 | -78 |
| Rotas candidate com role fixa | 220 | 220 | 0 |
| Rotas com permission middleware | 374 | 452 | +78 |
| Backoffice fixas sem active/MFA/log | 491 | 413 | -78 |

Inventário residual:

| Sprint | Rotas backoffice ainda fixas |
| --- | ---: |
| 47D | 78 |
| 47E | 58 |
| 47F | 99 |
| 47G | 96 |
| 47H | 123 |
| **Total** | **454** |

A evidência integral está em
`docs/access/progress/sprint-47c-after.json`.

## Base de dados e deploy

Esta sprint não cria migrations nem altera schema.

Gates externos antes de deploy em ambientes persistentes:

1. concluir o bootstrap de operador previsto na 47A.0;
2. aplicar migrations 47A/47B antes do código permission-first;
3. regularizar registos históricos sem Município apenas por relações
   autoritativas;
4. confirmar entitlements municipais por ambiente.

## Segurança e RGPD

- candidate permanece fora das rotas backoffice;
- auditor não executa mutações;
- Município A não acede ao Município B;
- IA documental continua no domínio documental privado;
- decisões e scoring exigem MFA;
- recusas não criam efeitos;
- não foram criadas permissions diretas nem wildcards;
- nenhuma Policy foi enfraquecida;
- não foram expostos dados pessoais ou caminhos privados.

## Riscos residuais

- 454 rotas backoffice ainda usam role fixa e pertencem exclusivamente às
  Sprints 47D–47H;
- 413 ainda não têm o trio de guards backoffice;
- dados históricos sem Município permanecem fail-closed até regularização;
- a publicação da branch não substitui os gates operacionais de deploy.

## Backlog

1. Não iniciar 47D nesta execução.
2. Usar a branch publicada da 47C como única base da 47D.
3. Reconciliar primeiro as 78 rotas mixed-context de listas, reclamações,
   audiências, fecho e sorteios da 47D.
4. Manter o inventário até `backoffice_fixed_role_routes = 0`.

## Decisão

**REPOSITORY_PASS_DEPLOYMENT_GATED**

As 78 rotas da Sprint 47C estão permission-first, com guards completos,
permission exata, Policies específicas, scope municipal, MFA, auditoria e
feedback seguro. O repositório está tecnicamente aprovado; o deploy depende
apenas dos gates externos de operação e dados já identificados.
