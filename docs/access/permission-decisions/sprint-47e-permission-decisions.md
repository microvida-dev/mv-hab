# Decisões de permissions — Sprint 47E

## Âmbito

A Sprint 47E reconciliou e migrou as 58 rotas do bounded context
`contracts` fixadas no manifesto
`docs/access/manifests/sprint-47e-route-manifest.json`:

- 27 rotas de leitura;
- 31 rotas de mutação;
- 2 rotas mixed-context;
- 58 rotas com risco crítico no inventário inicial;
- 9 lacunas sem permission semântica detetadas inicialmente;
- 35 lacunas de scope municipal detetadas inicialmente;
- 26 Form Requests efetivamente associados às rotas reconciliadas.

As rotas financeiras com prefixo contratual, incluindo cauções, regras e
cálculos de renda, ficaram expressamente fora desta sprint e pertencem à
47F.

## Regra de autorização

Cada rota migrada exige cumulativamente:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação exata>
&& Policy/ability backoffice
&& scope municipal fail-closed
&& estado/transição de domínio válida
```

Não existe fallback por nome de role. O middleware de permission não
substitui a Policy, a Policy não substitui o scope e o scope não substitui a
validação da transição.

## Entitlement

O ADR
`docs/architecture/adr-contracts-tenant-entitlement-scope.md` confirmou que o
catálogo atual não possui uma `FeatureKey` adequada para contratos ou
operações pós-atribuição.

Consequentemente:

- nenhuma das 58 rotas usa `municipality.feature:*`;
- `applications.review` não foi reutilizada indevidamente;
- o entitlement comercial autónomo fica para o Programa 48;
- contratos históricos continuam dependentes de permission, Policy e
  ownership municipal;
- a introdução futura de uma feature exige catálogo, dependências, estratégia
  de rollout, backfill explícito e testes próprios.

## Catálogo final

O manifesto usa 32 permissions finais distintas.

Permissions reutilizadas:

- `contracts.view`;
- `contracts.create`;
- `contracts.update`;
- `contracts.delete`.

Permissions novas:

- ciclo contratual: `contracts.issue`, `contracts.activate`,
  `contracts.suspend`, `contracts.terminate`, `contracts.cancel`,
  `contracts.sign`;
- templates: `contracts.templates.activate`,
  `contracts.templates.archive`, `contracts.templates.duplicate`;
- cláusulas: `contracts.clauses.activate`,
  `contracts.clauses.archive`;
- validações: `contracts.validations.create`,
  `contracts.validations.approve`, `contracts.validations.reject`;
- entrega de chaves: `contracts.key_handovers.view`,
  `contracts.key_handovers.schedule`, `contracts.key_handovers.update`,
  `contracts.key_handovers.complete`, `contracts.key_handovers.cancel`;
- transição para inquilino: `contracts.tenant_transitions.view`,
  `contracts.tenant_transitions.run`;
- comunicações: `contracts.communications.view`,
  `contracts.communications.create`, `contracts.communications.message`;
- execução contratual de cobranças: `contracts.charge_runs.view`,
  `contracts.charge_runs.run`;
- exploração pós-atribuição: `contracts.dashboard`,
  `contracts.maintenance_reports.view`.

Não foram criados wildcards, permissions diretas por utilizador ou namespaces
paralelos.

## Matriz semântica por área

| Área | Operação | Permission final | Ability |
| --- | --- | --- | --- |
| Contrato | consultar coleção | `contracts.view` | `viewAnyBackoffice` |
| Contrato | consultar registo | `contracts.view` | `viewBackoffice` |
| Contrato | criar | `contracts.create` | `createBackoffice` |
| Contrato | alterar dados preparatórios | `contracts.update` | `updateBackoffice` |
| Contrato legacy | eliminar em preparação | `contracts.delete` | `deleteBackoffice` |
| Contrato | emitir | `contracts.issue` | `issueBackoffice` |
| Contrato | ativar | `contracts.activate` | `activateBackoffice` |
| Contrato | suspender | `contracts.suspend` | `suspendBackoffice` |
| Contrato | terminar | `contracts.terminate` | `terminateBackoffice` |
| Contrato | cancelar | `contracts.cancel` | `cancelBackoffice` |
| Contrato | registar assinatura | `contracts.sign` | `signBackoffice` |
| Template | ativar | `contracts.templates.activate` | `activateBackoffice` |
| Template | arquivar | `contracts.templates.archive` | `archiveBackoffice` |
| Template | duplicar | `contracts.templates.duplicate` | `duplicateBackoffice` |
| Cláusula | ativar | `contracts.clauses.activate` | `activateBackoffice` |
| Cláusula | arquivar | `contracts.clauses.archive` | `archiveBackoffice` |
| Validação | criar | `contracts.validations.create` | `validateBackoffice` |
| Validação | aprovar | `contracts.validations.approve` | `approveBackoffice` |
| Validação | rejeitar | `contracts.validations.reject` | `rejectBackoffice` |
| Entrega de chaves | consultar | `contracts.key_handovers.view` | `viewAnyBackoffice` / `viewBackoffice` |
| Entrega de chaves | agendar | `contracts.key_handovers.schedule` | `createBackoffice` / `scheduleBackoffice` |
| Entrega de chaves | atualizar | `contracts.key_handovers.update` | `updateBackoffice` |
| Entrega de chaves | concluir | `contracts.key_handovers.complete` | `completeBackoffice` |
| Entrega de chaves | cancelar | `contracts.key_handovers.cancel` | `cancelBackoffice` |
| Transição | consultar | `contracts.tenant_transitions.view` | `viewAnyBackoffice` |
| Transição | executar | `contracts.tenant_transitions.run` | `runBackoffice` |
| Comunicação | consultar | `contracts.communications.view` | `viewAnyBackoffice` / `viewBackoffice` |
| Comunicação | criar | `contracts.communications.create` | `createBackoffice` |
| Comunicação | publicar mensagem | `contracts.communications.message` | `messageBackoffice` |
| Charge run | consultar | `contracts.charge_runs.view` | `viewAnyBackoffice` / `viewBackoffice` |
| Charge run | executar | `contracts.charge_runs.run` | `runBackoffice` |
| Dashboard | consultar agregados | `contracts.dashboard` | `viewAnyBackoffice` |
| Manutenção | consultar relatório | `contracts.maintenance_reports.view` | `viewAnyBackoffice` |

Leitura nunca autoriza uma mutação e `contracts.update` não autoriza emitir,
ativar, suspender, terminar, cancelar, assinar ou concluir uma entrega.

## Menor privilégio por perfil

| Perfil | Capacidades 47E | Limites |
| --- | --- | --- |
| `administrator` | Wildcard estrutural já existente | Continua sujeito a guards, Policy, scope e estado |
| `municipal_technician` | Operação técnica integral do lote 47E | Sem acesso global fora do Município |
| `financial_manager` | Contratos, validações, comunicações, charge runs e dashboard necessários à função | Sem término/cancelamento, entrega de chaves ou transição para inquilino |
| `legal_manager` | Ciclo jurídico, templates, cláusulas, validações e comunicações | Sem charge runs, entrega de chaves ou transição |
| `housing_manager` | Ciclo operacional, entrega de chaves, transição, comunicações e manutenção | Sem administração de templates/cláusulas ou charge runs |
| `maintenance_manager` | Consulta contratual, comunicações, dashboard e relatório de manutenção | Sem alterações contratuais |
| `auditor` | Apenas consultas explícitas e wildcards `*.view`/`*.audit` preexistentes | Sem qualquer mutação 47E |
| `candidate` | Mantém `contracts.view` apenas para a área própria | Middleware, Policy e ownership impedem backoffice |
| Perfis personalizados | Nenhuma atribuição automática | Exigem concessão explícita da permission exata |

## Scope municipal

Foi reforçado o `MunicipalRecordScopeService`; não foi criado um segundo
serviço transversal.

Fontes autoritativas:

- contrato → programa, candidatura ou inquilino → Município;
- template/cláusula → programa ou concurso → Município;
- validação/assinatura → contrato → Município;
- entrega de chaves → candidatura ou concurso → programa → Município;
- transição → contrato ou candidatura → Município;
- comunicação → contrato ou inquilino → Município;
- charge run → itens → contratos scoped; execução vazia → criador municipal;
- dashboard/relatório → query scoped antes de agregação ou limite.

Município nulo, catálogo sem vínculo municipal e relação incompleta falham
fechado. `municipality_id = null` não transforma um recurso em global. Um
operador de plataforma só obtém âmbito global através do assignment
estrutural existente.

## Rotas mixed-context

### `backoffice.cases.contracts.show`

Usa `contracts.view`, `ContractPolicy::viewBackoffice` e o scope do contrato.
Não herda a feature de candidaturas.

### `backoffice.tenant-operations.dashboard`

Usa `contracts.dashboard` e
`LandlordDashboardSnapshotPolicy::viewAnyBackoffice`. As métricas são
calculadas apenas depois de restringir os contratos ao Município do ator.

## Form Requests

Os Requests abrangidos deixaram de depender de autorização incondicional:

- usam a mesma ability da rota e do controller;
- resolvem o modelo real;
- recusam candidate e auditor em mutações;
- não aceitam Município, actor, ownership ou flags internas do cliente;
- não permitem `status` genérico quando existe endpoint de transição;
- validam IDs relacionados dentro da fronteira municipal.

## Estado, concorrência e auditoria

As transições de emissão, assinatura, validação, ativação, suspensão,
término, cancelamento, entrega de chaves, transição para inquilino e charge
run são executadas em Services transacionais. Os pontos críticos usam
`lockForUpdate()` e validação de estado sob lock.

O `ContractController` legacy passou a:

- criar, alterar e eliminar sob transação;
- auditar as três mutações;
- permitir update/delete apenas no estado de preparação;
- impedir alteração genérica do estado no formulário.

Recusas não produzem efeitos. Repetições são idempotentes quando o domínio o
permite ou devolvem erro de estado controlado.

## Testes

- `ContractsPermissionRoutesTest`: manifesto, middleware, permission, Policy,
  ability e ausência de role fixa;
- `ContractsMunicipalBoundaryTest`: Municípios A/B, Município nulo, candidate,
  auditor, conta/role inativa, MFA e agregados;
- `ContractLifecycleSecurityTest`: transições, estados, concorrência lógica,
  idempotência e auditoria;
- regressões: Sprints 13/26 e Case Workspaces.

Resultado final:

- 58 rotas migradas;
- 32 permissions finais;
- 20 abilities;
- zero `role:*` ativo no manifesto;
- zero `authorize(): true` nos Requests abrangidos;
- zero wildcards novos;
- zero permissions diretas por utilizador.

## Decisão

**PASS** para o repositório.

O entitlement comercial continua deliberadamente fora da 47E e deve ser
tratado no Programa 48 sem enfraquecer permission, Policy ou scope.
