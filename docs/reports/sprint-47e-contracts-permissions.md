# Sprint 47E — Contratos e operações do inquilino permission-first

## Resumo executivo

A Sprint 47E migrou as 58 rotas backoffice de contratos, templates,
cláusulas, validações, assinatura, entrega de chaves, transição para
inquilino e operação pós-atribuição que ainda dependiam de middleware de role
fixa.

O acesso final exige:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação exata>
&& Policy/ability
&& scope municipal fail-closed
&& estado/transição válida
```

Não foram alteradas regras de renda, cauções, pagamentos, reconciliação,
recibos, elegibilidade, scoring, listas ou portal público. Os fluxos
financeiros permanecem no lote 47F.

Decisão da sprint: **PASS**.

## Git e artefactos

- branch: `sprint-47e-contracts-permissions`;
- commit base:
  `9c9439a3a1dc9e532cb968eeb97c8bce8c6a91e6`;
- manifesto:
  `docs/access/manifests/sprint-47e-route-manifest.json`;
- ADR:
  `docs/architecture/adr-contracts-tenant-entitlement-scope.md`;
- decisões:
  `docs/access/permission-decisions/sprint-47e-permission-decisions.md`;
- snapshot:
  `docs/access/progress/sprint-47e-after.json`.

Commits funcionais:

- `9736363c` — manifesto imutável das 58 rotas;
- `bc7f5876` — decisão de entitlement e scope;
- `c3710be9` — permissions, guards, Policies e fronteira municipal;
- `ee32ed24` — ciclo contratual, transações e operação do inquilino;
- `c4b6638a` — testes de segurança, ciclo de vida e regressão;
- `bb396df7` — valores observados do inventário.

## Manifesto

O manifesto reconciliou:

| Dimensão | Total |
| --- | ---: |
| Rotas | 58 |
| Leituras | 27 |
| Mutações | 31 |
| Mixed-context | 2 |
| Risco crítico | 58 |
| Lacunas semânticas iniciais de permission | 9 |
| Lacunas iniciais de scope | 35 |
| Form Requests associados | 26 |

Todos os route names, URIs, métodos, controllers, actions, permissions,
Policies, abilities, modelos, fontes municipais e necessidades de auditoria
foram reconciliados com a Route Collection real.

As rotas de cauções, cálculos e regras de renda, contas, faturas, pagamentos,
cobranças, recibos, incumprimento e regularizações não foram absorvidas pela
47E.

## Entitlement

O catálogo comercial contém apenas features de candidaturas e não possui uma
chave semântica para contratos ou área do inquilino.

Foi decidido:

- não reutilizar `applications.review`;
- não criar uma FeatureKey silenciosa;
- manter as 58 rotas sem `municipality.feature:*`;
- exigir permission, Policy, MFA, logging, scope e estado;
- remeter o entitlement comercial autónomo para o Programa 48.

Isto preserva a consulta autorizada de contratos históricos e evita acoplar o
ciclo contratual ao produto de análise de candidaturas.

## Permissions

Foram usadas 32 permissions distintas:

- 4 reutilizadas: `contracts.view`, `contracts.create`,
  `contracts.update`, `contracts.delete`;
- 28 novas para transições e subdomínios específicos.

As novas permissions distinguem:

- emissão, ativação, suspensão, término, cancelamento e assinatura;
- ativação, arquivo e duplicação de templates;
- ativação e arquivo de cláusulas;
- criação, aprovação e rejeição de validações;
- consulta, agendamento, atualização, conclusão e cancelamento de entrega de
  chaves;
- consulta e execução da transição para inquilino;
- consulta, criação e publicação de mensagens;
- consulta e execução de charge runs;
- dashboard e relatório operacional de manutenção.

Não foram criados wildcards ou assignments diretos a utilizadores.

## Guards, Policies e Form Requests

As 58 rotas:

- deixaram de usar `role:*`;
- passaram a exigir `active.backoffice`, `mfa.backoffice` e
  `log.backoffice`;
- usam exatamente uma permission final;
- chamam uma ability específica de Policy;
- aplicam scope antes da paginação, agregação ou eager loading.

Foram criadas ou reforçadas Policies para:

- contratos;
- templates;
- cláusulas;
- validações e assinaturas;
- entregas de chaves;
- transições;
- comunicações;
- charge runs;
- dashboard do senhorio.

As 20 abilities finais separam leitura, criação, atualização, eliminação,
emissão, ativação, suspensão, término, cancelamento, assinatura, validação,
aprovação, rejeição, arquivo, duplicação, agendamento, conclusão, execução e
mensagem.

Os 26 Form Requests associados deixaram de aceitar autorização incondicional
e alinham a autorização com a rota, controller e Policy. O cliente não
controla Município, actor, ownership, flags internas ou estado final.

## Fronteira municipal

O `MunicipalRecordScopeService` foi expandido, mantendo uma única fronteira
transversal.

Relações autoritativas:

- contrato → programa/candidatura/inquilino → Município;
- template ou cláusula → programa/concurso → Município;
- validação ou assinatura → contrato → Município;
- entrega → candidatura/concurso → programa → Município;
- transição → contrato/candidatura → Município;
- comunicação → contrato/inquilino → Município;
- charge run → itens → contratos scoped;
- dashboard e relatórios → contratos/imóveis scoped.

O sistema falha fechado para:

- utilizador municipal sem Município;
- recurso sem relação autoritativa;
- template/cláusula sem programa ou concurso municipal;
- ID relacionado pertencente a outro Município;
- route model binding de recurso estrangeiro.

O operador de plataforma mantém apenas o scope estrutural explícito já
existente.

## Ciclo contratual

Foi preservada a state machine real dos contratos. Endpoints genéricos de
update não podem emitir, ativar, suspender, terminar ou cancelar.

Os Services de ciclo de vida:

- usam `DB::transaction()`;
- bloqueiam o registo com `lockForUpdate()` quando a operação é crítica;
- validam o estado sob lock;
- impedem dupla emissão, assinatura, validação, ativação, entrega ou
  transição;
- mantêm idempotência ou erro de domínio controlado;
- auditam apenas operações concluídas.

O `ContractController` legacy foi reforçado:

- store/update/destroy transacionais;
- auditoria nas três mutações;
- update/delete limitados a contratos em preparação;
- campo genérico de estado removido do formulário.

## Assinaturas e documentos

A sprint preservou:

- storage privado;
- acesso apenas por controllers autorizados;
- actor determinado no servidor;
- versões e integridade do documento;
- metadados de auditoria minimizados.

Não foram expostos URLs públicos, tokens, chaves, assinaturas, NIF, moradas,
documentos completos ou dados bancários.

## Entrega de chaves e transição

Agendamento, atualização, conclusão e cancelamento usam permissions e
abilities distintas. A conclusão é bloqueada e auditada dentro de transação.

A transição para inquilino:

- exige `contracts.tenant_transitions.run`;
- resolve a candidatura/contrato no Município do ator;
- impede execução duplicada;
- não cria um segundo inquilino;
- mantém consistentes contrato, habitação e vínculo do inquilino.

## Comunicações e charge runs

Consultar uma comunicação não permite publicar mensagem. A criação e a
mensagem usam permissions distintas e o envio é transacional e auditável.

O charge run incluído na 47E só gera valores contratuais internos:

- não confirma pagamentos;
- não reconcilia movimentos;
- não emite recibos;
- não importa ficheiros bancários.

Os contratos processados são scoped e os totais apresentados são calculados
apenas sobre os itens visíveis ao Município. O schema atual ainda permite um
registo agregado partilhado; uma futura partição por Município deverá usar
migration e backfill explícitos.

## Dashboard e relatórios

As métricas do dashboard pós-atribuição são agregadas depois do scope
municipal. Não são usados totais globais persistidos.

O relatório de manutenção limita pedidos através do contrato ou imóvel
municipal e não carrega documentos privados.

## Auditoria e RGPD

Foram auditadas as mutações relevantes:

- criação, alteração e eliminação legacy;
- emissão, ativação, suspensão, término e cancelamento;
- assinatura e validações;
- ativação/arquivo/duplicação de catálogo;
- entrega de chaves;
- transição para inquilino;
- comunicações;
- charge runs.

Metadata inclui actor, Município, recurso, estado minimizado e correlation ID
quando disponível. Não inclui conteúdo documental, assinatura, NIF, morada
completa, dados bancários, tokens ou MFA.

Candidate permanece fora do backoffice. Auditor permanece read-only.

## Testes

Testes criados:

- `tests/Feature/Security/ContractsPermissionRoutesTest.php`;
- `tests/Feature/Security/ContractsMunicipalBoundaryTest.php`;
- `tests/Feature/Security/ContractLifecycleSecurityTest.php`.

Cobertura:

- manifesto e guards;
- uma permission por rota;
- ausência de role fixa;
- Policies e abilities;
- Municípios A/B;
- Município nulo;
- candidate e auditor;
- conta e role inativas;
- MFA;
- permission sem scope e scope sem permission;
- recusa sem efeito;
- ciclo contratual;
- templates, cláusulas, validações e assinatura;
- entrega, transição, comunicações e charge runs;
- idempotência, locking e auditoria.

Regressões revistas:

- `Sprint13ContractsRentDepositTest`;
- `Sprint26TenantPostAwardTest`;
- `CaseWorkspaceRelationsTest`;
- `CreatesEnterpriseCaseFixtures`;
- `LegacyScreenNormalizationTest`.

O ajuste nas fixtures explicitou o Município autoritativo. Não foi relaxada
qualquer asserção de segurança.

## Quality gates

Resultados observados:

| Gate | Resultado |
| --- | --- |
| Integridade dos testes | PASS — 0 violações, 0 avisos |
| Pint incremental | PASS — 73 ficheiros |
| Pint global | PASS |
| PHPStan | PASS — 0 erros |
| PHPUnit completo | PASS — 1.156 testes, 14.129 asserções |
| PHPUnit UX | PASS — 130 testes, 645 asserções |
| Composer validate strict | PASS |
| Optimize clear | PASS |
| Vite build | PASS — Vite 8.0.16 |
| Migrations | PASS — 65 `Ran` |
| Route Collection | 1.170 rotas |
| `route:list` | PASS — 1.171 linhas de output |
| `git diff --check` | PASS |

## Inventário antes/depois

Valores produzidos pelos comandos reais:

| Métrica | Antes da 47E | Depois da 47E | Variação |
| --- | ---: | ---: | ---: |
| Route Collection | 1.170 | 1.170 | 0 |
| Rotas com role fixa | 596 | 538 | -58 |
| Backoffice com role fixa | 376 | 318 | -58 |
| Candidate com role fixa | 220 | 220 | 0 |
| Rotas com permission middleware | 530 | 588 | +58 |
| Backoffice fixas sem active | 335 | 277 | -58 |
| Backoffice fixas sem MFA | 335 | 277 | -58 |
| Backoffice fixas sem logging | 335 | 277 | -58 |

Snapshot residual:

- 318 rotas backoffice com role fixa;
- 99 atribuídas à 47F;
- 96 atribuídas à 47G;
- 123 atribuídas à 47H.

## Base de dados

Não foram criadas migrations nem alterado o schema.

## Riscos residuais

1. Não existe FeatureKey comercial para contratos; decisão adiada para o
   Programa 48.
2. `tenant_charge_runs` pode representar um agregado partilhado. A exposição
   e os totais estão scoped, mas uma partição física futura exige migration.
3. Os testes validam locking, estado e repetição sequencial; não substituem
   um ensaio de concorrência real com duas ligações de produção.
4. O acesso estrutural de operador de plataforma continua dependente da
   configuração e assignments definidos nas sprints anteriores.

## Decisão final

**PASS**

As 58 rotas foram migradas para permission-first, a fronteira municipal
falha fechado, o ciclo contratual está transacional e auditável, os gates
estão verdes e o inventário observado coincide com a redução de 58 rotas.
