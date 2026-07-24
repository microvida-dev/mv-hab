# Sprint 47D - Audiências, reclamações, listas, atribuições e sorteios permission-first

## Resumo executivo

A Sprint 47D migrou para autorização permission-first as 78 rotas
backoffice do manifesto de audiências, reclamações, listas, atribuições
residuais e sorteios.

Cada rota exige cumulativamente:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação exata>
&& municipality.feature:applications.review
&& Policy
&& scope municipal fail-closed
&& transição de estado válida
```

Foram preservados os fluxos candidate próprios, o auditor ficou limitado a
leitura e não foi criada qualquer permission direta, wildcard, role fixa,
FeatureKey, migration ou decisão automática.

Classificação final: **REPOSITORY_PASS_DEPLOYMENT_GATED**.

O repositório está aprovado. O rollout continua condicionado apenas pelos
gates externos herdados das Sprints 47A, 47B e 47C.

## Referências Git

- branch: `sprint-47d-hearings-complaints-lists-permissions`;
- commit-base:
  `1aa94d6b8695cdc4df12636b96ab2c0b5d51e9c2`;
- `f68f97f4` - manifesto das rotas 47D;
- `afb8eabc` - ADR do entitlement;
- `0c41859d` - decisões semânticas de permissions;
- `1505a5a9` - autorização permission-first dos fluxos;
- `24d2c772` - transações, auditoria e transições críticas;
- `7e2574b3` - testes de rotas e limites municipais;
- `38e5ce04` - compatibilidade legacy de Concursos e ability de Listas;
- `819bacc8` - snapshot observado do inventário de acesso;
- commit final de documentação e evidência criado no fecho da sprint.

## Manifesto e reconciliação

O manifesto está em
`docs/access/manifests/sprint-47d-route-manifest.json`.

Resultado:

| Contexto | Rotas |
| --- | ---: |
| Audiências | 13 |
| Reclamações | 15 |
| Listas | 23 |
| Atribuições e sorteios | 27 |
| **Total** | **78** |

Foram reconciliados route name, URI, método HTTP, controller, action,
middleware resolvido, permission, feature, Policy, ability, Form Request,
modelo, fonte municipal, risco, auditoria e cobertura.

As cinco rotas `complaint-decisions` concluídas na 47C ficaram explicitamente
fora do lote:

- `backoffice.complaint-decisions.create`;
- `backoffice.complaint-decisions.store`;
- `backoffice.complaint-decisions.show`;
- `backoffice.complaint-decisions.approve`;
- `backoffice.complaint-decisions.cancel`.

O manifesto foi ajustado uma vez após o gate completo, de forma explícita e
auditável: `backoffice.lists.automation.index` passou de
`ContestPolicy::viewBackoffice` para `viewListsBackoffice`. Nenhum outro
atributo da rota mudou. A alteração evitou que o scope específico de Listas
modificasse o contrato legacy das páginas gerais de Concursos.

## Entitlement

O ADR
`docs/architecture/adr-hearings-lists-entitlement-scope.md` aprovou a
reutilização de `applications.review` nas 78 rotas.

A decisão baseia-se em que audiências, reclamações, listas e sorteios são
fases de análise, contraditório, ordenação ou conclusão de candidaturas.
Com a feature desligada:

- a operação backoffice é recusada antes do controller;
- os dados existentes não são alterados;
- os fluxos candidate próprios mantêm a autorização por ownership;
- permission, Policy, MFA e scope continuam independentes da feature.

Não foi criada nova `FeatureKey`.

## Permissions e menor privilégio

O manifesto usa 41 permissions finais distintas:

- 5 permissions existentes reutilizadas;
- 36 permissions criadas para lacunas semânticas reais.

Os novos poderes distinguem:

- audiências: consultar, criar, emitir, rever, aceitar, rejeitar, fechar e
  cancelar;
- reclamações: atribuir, marcar como recebida, rever, pedir informação,
  marcar como vencida e fechar;
- listas: gerar, rever, aprovar, publicar, bloquear, arquivar, cancelar e
  gerir o período de reclamações;
- atribuições: processar desistência;
- sorteios: consultar, criar, alterar, carregar/bloquear participantes,
  executar, validar, cancelar, gerar/enviar convocatórias, gerir presenças,
  registar vencedor, gerar relatório e exportar.

Distribuição por template:

- `administrator`: poderes estruturais existentes, sempre sujeitos às
  restantes camadas;
- `municipal_technician`: operação técnica dos fluxos 47D;
- `jury`: leitura e poderes formais delimitados, sem administração integral
  do sorteio;
- `legal_manager`: audiências e reclamações jurídicas, sem execução de
  sorteios;
- `housing_manager`: sorteios, desistências e operação habitacional;
- `auditor`: leitura apenas;
- `candidate`: nenhuma permission backoffice;
- perfis personalizados: nenhuma concessão automática.

Não foram criados wildcards nem permissions atribuídas diretamente a
utilizadores.

## Policies e abilities

Foram criadas ou reforçadas 14 Policies:

- `AdditionalInformationRequestPolicy`;
- `ComplaintPolicy`;
- `ContestClosurePolicy`;
- `ContestPolicy`;
- `ControlledWithdrawalPolicy`;
- `DefinitiveListPolicy`;
- `DrawConvocationPolicy`;
- `HearingPolicy`;
- `HearingSubmissionPolicy`;
- `ListAutomationRunPolicy`;
- `LotteryDrawPolicy`;
- `LotteryResultPolicy`;
- `PostDrawReportPolicy`;
- `ProvisionalListPolicy`.

As 78 rotas usam 34 abilities distintas. Leitura e mutação não partilham uma
ability genérica quando a transição tem significado próprio.

Exemplos: `issueBackoffice`, `acceptBackoffice`, `rejectBackoffice`,
`requestInformationBackoffice`, `generateBackoffice`,
`openComplaintPeriodBackoffice`, `lockBackoffice`, `runBackoffice`,
`validateBackoffice`, `sendBackoffice`, `registerAttendanceBackoffice` e
`registerWinnerBackoffice`.

`ContestPolicy::viewListsBackoffice` separa a consulta de automação de Listas
da consulta geral de Concursos e aplica ownership municipal sem regressão no
módulo legacy.

## Form Requests

Foram revistos 32 Form Requests; os 29 que tinham autorização incondicional
deixaram de usar `authorize(): true`.

Os Requests abrangidos:

- usam a mesma ability da operação;
- resolvem o modelo real da rota;
- bloqueiam candidate;
- bloqueiam auditor nas mutações;
- validam IDs relacionais dentro do Município;
- não aceitam `municipality_id`, actor ou flags de auditoria;
- não permitem forjar transições internas;
- preservam as regras funcionais existentes.

## Controllers, Services e queries

Foram alterados 19 controllers e 18 Services.

Os controllers continuam finos:

- autorizam antes de consultar ou mutar;
- aplicam o scope antes de paginação/eager loading;
- delegam transições e auditoria nos Services;
- preservam o feedback seguro da Sprint 46D.

Não foram alteradas regras de elegibilidade, scoring, ranking, contratos,
pagamentos ou publicação pública.

## Isolamento municipal

`MunicipalRecordScopeService` foi reforçado para:

- audiências e respetivas submissões;
- reclamações e pedidos de informação;
- listas provisórias e definitivas;
- execuções de automação de listas;
- sorteios, resultados, convocatórias e relatórios;
- desistências e fechos de concurso.

As relações autoritativas são:

- audiência/submissão -> candidatura -> programa -> Município;
- reclamação/pedido -> candidatura -> programa -> Município;
- lista/run -> concurso -> programa -> Município;
- sorteio/recurso associado -> concurso/programa -> Município.

O scope falha fechado quando o utilizador não tem Município ou quando a
relação não prova ownership. As queries de índices são filtradas antes da
paginação e os IDs externos são rejeitados antes da mutação.

Os testes provam:

- Município A consulta os seus registos;
- Município A não consulta nem altera B;
- concurso local abre a automação de listas;
- concurso externo é recusado;
- recusas não alteram estado nem criam auditoria indevida.

## Transações, concorrência e idempotência

Foram reforçados Services de:

- audiência e revisão de submissões;
- reclamações e pedidos de informação;
- listas provisórias e definitivas;
- publicação e automação de listas;
- carregamento de participantes e execução de sorteio;
- convocatórias e presenças;
- desistências.

As operações críticas usam transações e `lockForUpdate()` onde existe risco
de publicação, sorteio, vencedor, convocatória ou transição duplicada.

Correção relevante: um sorteio só pode executar a partir dos estados
preparados previstos. Um sorteio concluído não pode ser executado novamente
nem substituir resultados existentes.

Operações em lote de presenças são atómicas. Transições repetidas mantêm o
estado e a evidência original ou devolvem erro de domínio controlado.

## Auditoria, filas e feedback

Foram preservados ou reforçados eventos para:

- emissão/fecho de audiência e revisão de resposta;
- atribuição, receção, pedido adicional e fecho de reclamação;
- geração, revisão, aprovação, publicação, bloqueio e arquivo de listas;
- execução, validação e cancelamento de sorteio;
- participantes, convocatórias, presenças, vencedor e relatórios;
- desistência.

A metadata permanece minimizada: não inclui NIF, documentos, moradas,
tokens, códigos MFA nem conteúdo integral de reclamações/audiências.

Não foram alteradas filas nem convertido processamento assíncrono em
síncrono. A infraestrutura comum mantém 403 JSON, página 403 HTML, redirect
303 seguro quando aplicável, correlation ID e auditoria deduplicada.

## Segurança e RGPD

- candidate permanece fora do backoffice;
- auditor não executa mutações;
- MFA e logging são obrigatórios nas 78 rotas;
- listas permanecem privadas antes de publicação válida;
- downloads e relatórios continuam privados;
- Município A não acede a B;
- nenhuma recusa produz efeitos laterais;
- nenhuma Policy ou entitlement foi contornado;
- nenhum dado pessoal novo foi exposto.

## Base de dados e migrations

A Sprint 47D não cria migrations e não altera schema.

`php artisan migrate:status` confirmou 65 migrations em estado `Ran`.

## Testes criados

- `HearingsComplaintsListsPermissionRoutesTest`;
- `HearingsComplaintsListsMunicipalBoundaryTest`;
- `AllocationLotteryMunicipalBoundaryTest`.

Cobertura dirigida final:

- 12 testes;
- 2.464 asserções;
- manifesto, guards, permission, feature, Policy e ability;
- Município A/B;
- candidate, auditor, role inativa, conta/MFA e Município nulo;
- recusa sem efeitos;
- transições, auditoria, sorteio duplicado e validação antecipada.

## Testes legacy alterados

- `Sprint11ListsComplaintsHearingTest`: adiciona Município, feature, MFA,
  permissions e estados coerentes; substitui redirects genéricos por destino,
  sucesso, ausência de erros e efeito persistido.
- `LotteryClosureFlowTest`: alinha Município/feature/MFA e valida sucesso e
  persistência nas operações do sorteio.
- `ComplaintCaseWorkspaceTest`: alinha a reclamação com o Município da
  candidatura para testar autorização real.
- `CreatesEnterpriseCaseFixtures`: cria fixtures relacionadas no mesmo
  Município em vez de depender de dados incidentais.
- `AuditAccessRoutesCommandTest`: atualiza apenas os contadores produzidos
  pelo comando real após a migração das 78 rotas.

Nenhuma asserção funcional foi removida para aceitar uma recusa como sucesso.
O grep de padrões proibidos em testes alterados devolveu vazio.

## Regressões encontradas e corrigidas

### Contrato legacy de Concursos

O primeiro gate completo detetou quatro recusas em páginas gerais de
Concursos. A causa era o uso da ability genérica `viewBackoffice` por um fluxo
de Listas com requisitos municipais diferentes.

Correção:

- contrato legacy de Concursos restaurado;
- ability `viewListsBackoffice` criada para automação de listas;
- scope municipal mantido no fluxo 47D;
- teste local/externo adicionado;
- manifesto e decisões atualizados explicitamente.

### Snapshot de auditoria

O teste do comando ainda esperava os valores da 47C. Foi atualizado com os
valores observados após executar novamente o comando, sem cálculo manual.

### Reexecução de sorteios

A revisão transacional identificou que um sorteio concluído podia ser
reexecutado e substituir resultados. O Service passou a validar e bloquear o
estado sob transação. O teste prova ausência de novos resultados.

## Resultados dos testes

- lote de regressão após a correção de Concursos:
  27 testes, 3.045 asserções, PASS;
- testes especializados 47D:
  12 testes, 2.464 asserções, PASS;
- PHPUnit completo:
  1.141 testes, 13.301 asserções, PASS;
- filtro UX:
  130 testes, 645 asserções, PASS;
- integridade:
  0 violações críticas e 0 avisos.

## Inventário antes/depois

Valores obtidos pelos comandos reais:

| Métrica | Antes (47C) | Depois (47D) | Delta |
| --- | ---: | ---: | ---: |
| Rotas na coleção auditada | 1.170 | 1.170 | 0 |
| Rotas com role fixa | 674 | 596 | -78 |
| Rotas backoffice com role fixa | 454 | 376 | -78 |
| Rotas candidate com role fixa | 220 | 220 | 0 |
| Rotas com permission middleware | 452 | 530 | +78 |
| Backoffice fixas sem active/MFA/log | 413 | 335 | -78 |

Inventário residual, exclusivamente das rotas backoffice ainda fixas:

| Sprint | Rotas |
| --- | ---: |
| 47E | 58 |
| 47F | 99 |
| 47G | 96 |
| 47H | 123 |
| **Total** | **376** |

O snapshot completo está em
`docs/access/progress/sprint-47d-after.json` e declara:

```json
"inventory_scope": "backoffice_fixed_role_routes_only"
```

Os 305 scopes por confirmar, 57 mutações cuja auditoria não foi detetada e
267 rotas sem teste nominal pertencem ao inventário residual 47E-47H. Não são
falhas das 78 rotas concluídas nesta sprint.

## Quality gates finais

- `composer quality:tests:integrity`: PASS, 0/0;
- `composer quality:pint:changed`: PASS, 93 ficheiros PHP;
- `composer quality:pint`: PASS;
- PHPStan global: PASS, 0 erros;
- PHPUnit completo: PASS;
- PHPUnit UX: PASS;
- `composer validate --strict`: PASS;
- `php artisan optimize:clear`: PASS;
- `npm run build`: PASS;
- `php artisan migrate:status`: PASS, 65 migrations `Ran`;
- `php artisan route:list --except-vendor`: PASS, 1.167 rotas;
- `php artisan access:audit-routes --format=json`: PASS;
- `git diff --check`: PASS;
- auditoria manual dos testes: PASS.

## Riscos residuais e deployment gates

Não foi declarado `DEPLOYED`.

Mantêm-se:

1. Sprint 47A.0: bootstrap real do operador depende de manifesto externo;
2. Sprint 47A.1: migrations e dados municipais exigem validação por ambiente;
3. Sprint 47B: registos históricos com Município nulo exigem regularização
   por fonte autoritativa;
4. Sprint 47C: rollout permission-first depende dos gates anteriores;
5. Sprint 47D: confirmar templates de roles e feature entitlement com dados
   reais de cada Município antes da ativação.

## Backlog

O inventário residual deve ser tratado apenas nas sprints previstas:

- 47E: contratos;
- 47F: finanças e pagamentos;
- 47G: manutenção, vistorias e visitas;
- 47H: comunicações, notificações, relatórios e configuração residual.

Não foi iniciado trabalho da Sprint 47E.

## Decisão final

**REPOSITORY_PASS_DEPLOYMENT_GATED**

As 78 rotas da 47D estão permission-first, com guards completos, permission
exata, entitlement aprovado, Policies, scope municipal, MFA, transições
seguras, auditoria e testes. O repositório está verde; apenas permanecem
condições externas de rollout já identificadas.
