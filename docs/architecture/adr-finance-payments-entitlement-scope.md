# ADR — Entitlement de finanças, rendas e pagamentos

## Estado

Aceite para a Sprint 47F.

## Contexto

A Sprint 47F migra 99 rotas de finanças, rendas e pagamentos para
autorização permission-first:

- 67 rotas do bounded context `finance`;
- 32 rotas do bounded context `payments`;
- 48 operações de leitura;
- 51 operações de mutação.

O catálogo comercial atual contém apenas:

- `applications.intake`;
- `applications.review`;
- `applications.export`.

Não existe uma `FeatureKey` para gestão financeira, rendas, pagamentos,
importações, recibos ou operação pós-contratual. `applications.review`
representa a análise de candidaturas e não constitui um entitlement financeiro.
Reutilizá-la criaria um acoplamento comercial incorreto e poderia bloquear o
histórico financeiro quando a revisão de candidaturas fosse suspensa.

## Decisão

As 99 rotas da Sprint 47F não recebem `municipality.feature:*`.

O acesso exige cumulativamente:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação financeira exata>
&& Policy/ability backoffice
&& scope municipal fail-closed
&& estado/transição financeira válida
```

Não é criada uma nova `FeatureKey` nesta sprint e não é reutilizada
`applications.review`.

O entitlement comercial autónomo de finanças e pagamentos fica explicitamente
adiado para o Programa 48. A sua introdução futura deve incluir catálogo,
dependências, rollout, tratamento de registos históricos, migração de dados e
testes próprios.

## Decisão por área

| Área | Natureza | Feature final na 47F | Justificação |
| --- | --- | --- | --- |
| Cauções | Evidência financeira contratual | Nenhuma | Depende do contrato, da permission exata e do Município |
| Cálculo e revisão de renda | Cálculo financeiro regulamentar | Nenhuma | Não pertence ao ciclo de candidaturas |
| Matrizes e regras de renda | Catálogo financeiro municipal | Nenhuma | Exige vínculo a programa/concurso e scope fail-closed |
| Contas e planos | Operação pós-contratual | Nenhuma | O contrato e a conta determinam o Município |
| Incumprimentos e avisos | Cobrança administrativa | Nenhuma | Exige poderes próprios para emitir, resolver e cancelar |
| Acordos e prestações | Regularização financeira | Nenhuma | As mutações dependem de estado, lock e auditoria |
| Pagamentos | Movimento financeiro | Nenhuma | Confirmar e reverter são poderes distintos |
| Importações | Ingestão de dados bancários privados | Nenhuma | Exige isolamento municipal e processamento idempotente |
| Recibos | Evidência financeira oficial | Nenhuma | Gerar, descarregar e cancelar são poderes independentes |
| Faturas do inquilino | Operação financeira contratual | Nenhuma | Depende da conta e do contrato municipais |
| Comprovativos de comunicação | Documento privado | Nenhuma | A autorização deriva da comunicação e do destinatário |

## Semântica comercial futura

O Programa 48 deve suportar, no mínimo, três estados funcionais:

### Feature ativa

- permite leitura histórica e operacional;
- permite mutações autorizadas;
- continua sujeita a permission, Policy, MFA, scope e regras de estado.

### Feature read-only ou suspensa

- permite consulta histórica e auditoria;
- bloqueia novos cálculos, pagamentos, importações, recibos e demais mutações;
- não elimina nem oculta evidência financeira já produzida;
- não concede permissões por si só.

### Feature nunca contratada

- não disponibiliza acesso funcional ao módulo;
- não expõe contagens, saldos ou existência de registos;
- mantém apenas as obrigações técnicas de retenção e administração de
  plataforma que venham a ser definidas.

Estes estados não são implementados na 47F porque o modelo atual representa
apenas entitlements binários. Introduzi-los sem desenho de catálogo e rollout
seria um breaking change.

## Menor privilégio

- `financial_manager` é o perfil funcional principal para mutações
  financeiras;
- `administrator` conserva administração estrutural, mas continua sujeito a
  Policy, scope e transições válidas;
- `municipal_technician` não recebe automaticamente todo o novo catálogo
  financeiro;
- `auditor` recebe apenas leitura e auditoria explícitas;
- `candidate` não recebe permissions backoffice;
- perfis personalizados exigem concessão explícita da permission exata.

Uma permission de leitura não autoriza mutação. Em particular:

- `confirm` não equivale a `approve`;
- `reverse` não equivale a `update`;
- `cancel` não equivale a `update`;
- `download` não equivale a `view`;
- `generate` não equivale genericamente a `create`.

## Scope municipal

As fontes autoritativas são:

- caução → contrato → Município;
- cálculo de renda → contrato, atribuição ou candidatura → Município;
- revisão manual → cálculo de renda → Município;
- matriz/regra → programa ou concurso → Município;
- conta financeira → contrato → Município;
- pagamento/recibo → conta financeira → contrato → Município;
- incumprimento/aviso/acordo/prestação → conta financeira → contrato →
  Município;
- revisão de renda/alteração de rendimento → conta ou contrato → Município;
- importação → criador municipal e linhas associadas a contas scoped;
- fatura/pagamento do inquilino → conta financeira → contrato → Município;
- comprovativo de comunicação → comunicação e destinatário → Município.

O scope é aplicado antes de paginação, agregação, exportação ou mutação.
Município nulo, relações incompletas e catálogos sem vínculo municipal falham
fechado. Um operador de plataforma só obtém âmbito global através do assignment
estrutural explícito já existente.

## Integridade financeira

O entitlement não substitui controlos de domínio. As operações críticas
continuam a exigir:

- valores decimais determinísticos, sem `float`;
- validação server-side;
- transações;
- `lockForUpdate()` onde exista concorrência;
- confirmação e reversão idempotentes ou recusadas por estado;
- prevenção de recibos e importações duplicados;
- armazenamento privado;
- auditoria minimizada sem IBAN, NIF, referências completas ou conteúdo de
  ficheiros.

## Alternativas rejeitadas

### Reutilizar `applications.review`

Rejeitada por não representar finanças nem pagamentos e por acoplar o acesso
ao histórico financeiro ao ciclo candidatural.

### Criar uma `FeatureKey` na 47F

Rejeitada porque a sprint não define catálogo comercial, estados read-only,
dependências, rollout ou backfill. Uma chave silenciosa não constituiria um
entitlement operacional seguro.

### Considerar `municipality_id = null` como plataforma

Rejeitada. O âmbito global depende do assignment estrutural do operador, não da
ausência de Município.

### Usar permissions genéricas para transições

Rejeitada porque permitiria escalar `view`, `create`, `update` ou `approve`
para confirmar, reverter, cancelar, gerar ou descarregar evidência financeira.

## Consequências

- nenhuma migration ou `FeatureKey` é criada na 47F;
- as 99 rotas usam permission, Policy, guards e scope municipal;
- testes positivos não precisam de ativar uma feature municipal;
- testes negativos demonstram independência entre permission e ownership;
- o Programa 48 deve decidir o entitlement comercial antes de o tornar
  obrigatório em produção;
- o rollout futuro não pode enfraquecer acesso histórico, auditoria,
  isolamento municipal ou integridade financeira.
