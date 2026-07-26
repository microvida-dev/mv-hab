# ADR — Entitlement de contratos e operações do inquilino

## Estado

Aceite para a Sprint 47E.

## Contexto

A Sprint 47E migra 58 rotas de contratos, minutas, cláusulas, validações,
entrega de chaves, transição para inquilino e operação pós-atribuição para
autorização permission-first.

O catálogo comercial atual contém apenas:

- `applications.intake`;
- `applications.review`;
- `applications.export`.

Não existe uma `FeatureKey` para contratos ou exploração pós-atribuição.
`applications.review` termina no processo candidatural e não representa a
gestão jurídica e operacional de um contrato já atribuído. Reutilizá-la
acoplaria incorretamente dois produtos e poderia bloquear contratos históricos
quando a funcionalidade de revisão de candidaturas fosse desativada.

## Decisão

As 58 rotas da Sprint 47E não recebem `municipality.feature:*`.

O acesso exige cumulativamente:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação exata>
&& Policy/ability backoffice
&& scope municipal fail-closed
&& transição de estado válida
```

O entitlement comercial autónomo de contratos e área do inquilino fica
explicitamente adiado para o Programa 48. A sua introdução futura deve incluir
catálogo, dependências, backfill, comportamento de contratos históricos e
testes de rollout. Não pode ser inferida a partir desta decisão.

## Decisão por área

| Área | Natureza | Feature final na 47E | Justificação |
| --- | --- | --- | --- |
| Contratos e ciclo de vida | Jurídica e operacional | Nenhuma | Não existe chave semântica; permission e Policy distinguem cada transição |
| Templates e cláusulas | Catálogo contratual municipal | Nenhuma | Não pertencem ao intake nem à revisão de candidaturas |
| Assinaturas e validações | Integridade contratual | Nenhuma | São operações posteriores à decisão de atribuição |
| Entrega de chaves | Execução da atribuição | Nenhuma | A segurança depende do contrato/candidatura e do Município, não de `applications.review` |
| Transição para inquilino | Provisionamento pós-atribuição | Nenhuma | Cria relações operacionais posteriores à candidatura |
| Comunicações do inquilino | Operação contratual privada | Nenhuma | Exige permission própria e ownership municipal |
| Execução de cobranças | Geração operacional de valores contratuais | Nenhuma | A rota 47E gera faturas internas; pagamentos, reconciliação e cobrança financeira ficam na 47F |
| Relatório de manutenção do inquilino | Consulta operacional | Nenhuma | O conjunto é filtrado pelos contratos/imóveis do Município |

## Rotas mixed-context

### `backoffice.cases.contracts.show`

É uma leitura processual de um contrato real. Usa `contracts.view`,
`ContractPolicy::viewBackoffice` e scope do contrato. Não herda
`applications.review`. Um contrato histórico continua consultável enquanto o
utilizador mantiver permissão e vínculo ao Município.

### `backoffice.tenant-operations.dashboard`

É uma leitura agregada de exploração pós-atribuição. Usa
`contracts.dashboard` e uma Policy própria. Todas as métricas são calculadas
sobre contratos municipais antes da agregação. O dashboard não reutiliza
snapshots globais nem mistura Municípios. O auditor pode receber apenas esta
permission de leitura; nenhuma ação mutável é derivada do dashboard.

## Scope municipal

As fontes autoritativas são:

- contrato → programa, candidatura ou inquilino → Município;
- template/cláusula → programa ou concurso → Município;
- validação/assinatura → contrato → Município;
- entrega de chaves → candidatura ou concurso → programa → Município;
- transição → contrato ou candidatura → Município;
- comunicação → contrato ou inquilino → Município;
- charge run → itens → contratos scoped; quando vazio, criador municipal;
- dashboard/relatório → queries scoped antes de agregação ou limite.

Um template ou cláusula sem programa e sem concurso não fica implicitamente
global. Só um operador de plataforma com assignment estrutural válido pode
consultar catálogos globais. `municipality_id = null` nunca concede âmbito
global a um utilizador municipal.

## Charge runs

O endpoint incluído na 47E é classificado como geração operacional de valores
a cobrar a partir de contratos ativos. Não confirma pagamentos, não reconcilia
movimentos bancários, não emite recibos e não processa importações.

O registo de execução existente pode agregar itens de mais do que um Município
por período. Por isso:

- os contratos processados são scoped pelo ator;
- a execução é bloqueada durante a atualização;
- itens já existentes não são duplicados;
- listagens e detalhe expõem apenas itens do Município;
- totais apresentados são derivados dos itens scoped, não dos totais globais
  persistidos;
- a permission `contracts.charge_runs.run` não concede qualquer permission da
  47F.

Uma futura alteração de schema para particionar a execução por Município deve
ser tratada numa migration nova, com backfill explícito. Não é necessária para
fechar a fronteira de leitura e processamento nesta sprint.

## Segurança e RGPD

- `candidate` não acede às rotas backoffice, mesmo quando possui
  `contracts.view` para a área própria;
- `auditor` recebe apenas permissions de leitura;
- dados bancários, assinaturas, documentos completos, NIF e moradas não entram
  em logs;
- recusas de permission, scope ou estado não produzem efeitos;
- downloads contratuais permanecem privados e fora do lote 47E quando já
  migrados noutra sprint.

## Alternativas rejeitadas

### Reutilizar `applications.review`

Rejeitada por não representar contratos nem operações do inquilino e por
alterar o acesso a contratos históricos.

### Criar uma `FeatureKey` na 47E

Rejeitada porque a sprint não define catálogo comercial, dependências nem
estratégia de rollout. Uma nova chave silenciosa seria um breaking change.

### Considerar `municipality_id = null` como plataforma

Rejeitada. O âmbito global depende do assignment estrutural do operador de
plataforma, não da ausência de Município no utilizador.

### Manter agregados globais no dashboard

Rejeitada por permitir inferência de dados operacionais de outros Municípios.

## Consequências

- nenhuma migration ou `FeatureKey` é criada na 47E;
- todas as 58 rotas usam permission, Policy, MFA, logging e scope;
- testes positivos não precisam de ativar uma feature municipal;
- testes negativos demonstram independência entre permission e ownership;
- o Programa 48 deve decidir o entitlement comercial antes de o tornar
  obrigatório em produção.
