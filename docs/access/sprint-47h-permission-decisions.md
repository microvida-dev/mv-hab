# Decisões de Permissions — Sprint 47H

## Referências

- Manifesto: `docs/access/manifests/sprint-47h-route-manifest.json`
- Commit de origem: `58f47b18198a562e105b3b68c67dbc3fdece8c40`
- Estado: decisão aceite para implementação

## Universo imutável

| Contexto | Rotas |
|---|---:|
| Reporting | 38 |
| Communications | 36 |
| Notifications | 41 |
| Configuration | 8 |
| **Total** | **123** |

O inventário inicial encontrou exatamente as 123 rotas esperadas. Todas
tinham middleware de role fixa; 101 não herdavam ainda
`active.backoffice`, `mfa.backoffice` e `log.backoffice`.

## Princípio cumulativo

```text
permission exata
&& Policy/ability semântica
&& scope municipal
&& estado válido
&& MFA
&& auditoria
```

Roles agregam permissions. Não constituem autorização operacional e não
existe bypass baseado no nome `administrator`.

## Permissions

O manifesto fixa 86 permissions únicas:

- 29 permissions existentes, semanticamente adequadas;
- 57 permissions novas, necessárias para separar operações distintas.

As listas normativas completas estão no bloco `permissions` do
manifesto. As principais decisões são:

### Reporting

- leitura geral mantém `reports.view`;
- dashboards executivos mantêm `reports.view_executive`;
- executar um relatório passa a exigir `reports.run`;
- definições, dashboards, widgets, indicadores e filtros recebem
  permissions próprias por operação;
- Case Workspaces mantêm a permission do recurso (`contests.view`,
  `support.view`, `audit_logs.view`);
- nenhum relatório municipal genérico reutiliza
  `applications.export`.

### Communications

- logs separam `view`, `create`, `cancel` e `archive`;
- reenvio e registo postal são operações distintas;
- variáveis de template recebem catálogo próprio;
- tickets separam `view`, `assign`, `resolve`, `message` e download
  privado de anexos;
- atas e minutas reutilizam `documents.*` quando a operação coincide;
- é adicionada `documents.publish`, já referida pela Policy mas ausente
  do catálogo.

### Notifications

- templates, versões e regras de evento recebem permissions próprias;
- aprovar, ativar e arquivar versões são operações independentes;
- alertas internos separam deteção, resolução e dispensa;
- notificações oficiais separam criação, marcação de envio e falha;
- Work Tasks reutilizam o catálogo granular existente.

### Configuration

- leitura/criação/edição reutilizam `settings.*`;
- ativar e desativar configurações são permissions próprias;
- fechar concurso exige `contests.close`;
- configurações globais continuam reservadas a operador global com
  assignment estrutural válido.

## Matriz least-privilege

### `administrator`

Mantém apenas o wildcard existente `*`.

### `municipal_technician`

Recebe leitura e operação municipal comum em reporting, comunicações,
notificações e Work Tasks. Não recebe gestão global de templates,
configuração global ou administração de dashboards/definições.

### `housing_manager`

Recebe reporting habitacional, dashboards operacionais, notificações e
trabalho de equipa compatíveis com a operação habitacional.

### `support_agent`

Recebe apenas tickets, mensagens, anexos, FAQ contextual, notificações
operacionais e Work Tasks necessários ao atendimento. Não recebe
reporting financeiro, configuração ou gestão global de templates.

### `financial_manager`

Mantém reporting financeiro, execução de relatórios autorizados,
notificações operacionais e Work Tasks. Não recebe gestão de templates
ou configuração não financeira.

### `legal_manager`

Mantém reporting, atas/documentos jurídicos, comunicações aplicáveis e
Work Tasks. Não recebe configuração técnica global.

### `auditor`

Mantém leitura/auditoria. Não recebe create, update, delete, publish,
send, retry, cancel, resolve, activate ou deactivate.

### `candidate`

Recebe zero permissions novas da Sprint 47H. As permissions históricas
do portal do candidato não autorizam rotas de backoffice porque estas
usam permissions específicas e Policies de backoffice.

## Entitlements

Não é criado qualquer `FeatureKey`.

Os únicos entitlements existentes (`applications.intake`,
`applications.review` e `applications.export`) não representam
reporting genérico, comunicações, notificações ou configuração. A sua
reutilização seria semanticamente incorreta.

## Operador global

O acesso global depende exclusivamente de
`PlatformOperatorAssignment` ativo, não revogado e validado por
`PlatformOperatorScopeService`. Um utilizador com
`municipality_id = null` sem esse assignment falha fechado.

## Downloads críticos

O manifesto classifica como críticos:

- `backoffice.procedure-minutes.download`;
- `backoffice.support-ticket-attachments.download`.

Ambos exigem permission específica, MFA, Policy, scope municipal,
storage privado, validação de existência/path e auditoria.
