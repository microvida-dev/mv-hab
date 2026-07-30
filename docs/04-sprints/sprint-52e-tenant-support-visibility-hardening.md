# Sprint 52E — Visibilidade do apoio apenas para inquilinos ativos

## Objetivo

Consolidar a fronteira já introduzida na Sprint 52A para que o módulo de
pedidos de apoio permaneça oculto e inacessível enquanto o utilizador não
possuir um ciclo pós-atribuição integralmente concluído.

## Auditoria da base

A base `96e8993e7f6638bad91a165168fe7530f943ef48` já continha a maior parte
do requisito:

- as seis rotas candidate de apoio usam o middleware `tenant.support`;
- a navegação apresenta **Apoio** apenas quando
  `TenantSupportEligibilityService::isAvailableFor()` devolve `true`;
- criação, leitura, resposta e anexos estão protegidos por Policies;
- `SupportTicketService` e `SupportTicketMessageService` repetem a validação
  antes de mutações;
- o acesso exige simultaneamente perfil de inquilino ativo, transição
  concluída, contrato ativo e entrega de chaves concluída;
- tickets históricos são preservados, mesmo quando o acesso deixa de estar
  disponível.

A auditoria identificou uma única divergência de defesa em profundidade:
`SupportTicketPolicy::viewAny()` validava apenas a permission `support.view`.
Como o role `candidate` possui essa permission, uma invocação direta da Gate
fora do grupo de rotas podia autorizar a listagem sem revalidar o ciclo de
inquilino. As rotas atuais continuavam protegidas pelo middleware, mas o
contrato da Policy não era fail-closed de forma autónoma.

## Alteração aplicada

`SupportTicketPolicy::viewAny()` passa a aplicar, para candidatos, a mesma
fronteira autoritativa usada pelo middleware, navegação e restantes métodos da
Policy:

```text
candidate role
+ support.view
+ tenant support eligibility
= listagem autorizada
```

O comportamento backoffice não é alterado.

## Caracterização automatizada

A suite da Sprint 52E valida:

1. presença do middleware `tenant.support` nas seis rotas candidate;
2. ocultação na navegação antes do ciclo completo;
3. recusa por URL direta antes da ativação;
4. recusa autónoma por `viewAny`, `create`, `view` e `update`;
5. perfil ativo e `activated_at` obrigatório;
6. transição concluída e `completed_at` obrigatório;
7. contrato com estado `active` e `activated_at` obrigatório;
8. entrega de chaves concluída e `completed_at` obrigatório;
9. disponibilização conjunta de navegação, rota e Policy após o ciclo completo;
10. kill switch global fail-closed;
11. preservação física de tickets históricos quando o contrato termina.

## Impacto

```text
Rotas: 1173 → 1173
Migrations: 0
Permissions: 0 alterações
Tabelas/colunas: 0 alterações
Dados históricos: preservados
Backoffice: sem alteração funcional
```

## Deployment

Não existem passos de base de dados. Após deployment:

```bash
php artisan optimize:clear
php artisan config:cache
```

A variável já existente continua a funcionar como kill switch:

```dotenv
MVHAB_TENANT_SUPPORT_ENABLED=true
```
