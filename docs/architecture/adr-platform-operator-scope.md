# ADR - Scope estrutural do operador de plataforma

- Estado: **BLOQUEADO - decisão proposta, backfill por aprovar**
- Data: 2026-07-23
- Sprint: 47A
- Âmbito: administração global, entitlements e isolamento municipal

## 1. Contexto

O MV-HAB distingue atualmente um utilizador municipal de um suposto operador de
plataforma através de `users.municipality_id`:

```php
$user->municipality_id === null
```

Este sinal não é um scope estrutural. `municipality_id` nullable também é usado
por candidatos e por fixtures de teste. A Policy de gestão de funcionalidades
municipais combina hoje ausência de Município, exclusão da role candidate e
permission, mas continua a inferir o contexto global a partir da ausência de
tenancy.

A Sprint 47A exige que o operador:

- não dependa do nome de uma role;
- seja tipado e explicitamente atribuído;
- seja auditável e reversível;
- não possa promover-se a si próprio;
- preserve operadores existentes através de backfill baseado em evidência;
- possa evoluir para acesso limitado a alguns Municípios.

## 2. Evidência verificada

### Código e schema

- `users` só possui `municipality_id` como indicador de tenancy;
- não existe coluna de scope operacional;
- não existe entidade de associação de operadores;
- não existe allowlist em configuração;
- não existe seeder estrutural de operador de plataforma;
- `MunicipalityFeatureEntitlementPolicy::hasPlatformScope()` usa
  `municipality_id === null`;
- os testes de entitlements criam um administrator sem Município e tratam-no
  como operador;
- `ProgramSeeder` associa o administrador estrutural de demonstração ao
  Município criado;
- os seeders municipais associam perfis operacionais a um Município.

### Dados locais

Auditoria read-only executada em 2026-07-23:

- utilizadores: 24;
- utilizadores sem Município: 1;
- o único utilizador sem Município possui apenas a role `candidate`;
- operadores de plataforma explicitamente identificáveis: 0;
- atores distintos em eventos `municipality_feature_enabled` ou
  `municipality_feature_disabled`: 0.

Não foram consultados nem registados passwords, tokens, documentos ou dados
pessoais no ADR.

### Conclusão

Não existe evidência persistida que permita identificar os operadores atuais.
Role, permission, ausência de Município, email conhecido ou atividade histórica
isolada seriam inferências e não satisfazem o requisito de backfill explícito.

## 3. Alternativas avaliadas

### A. Coluna em `users`

Adicionar `operational_scope` com enum `municipal|platform`.

Vantagens:

- implementação simples;
- leitura eficiente;
- tipagem direta no modelo.

Limitações:

- não regista concessão, revogação, justificação ou vigência;
- dificulta operadores limitados a alguns Municípios;
- mistura identidade e autorização operacional;
- exige infraestrutura adicional para auditoria.

### B. Role fixa de plataforma

Criar `platform_operator`.

Rejeitada:

- repete o problema role-first;
- o nome da role passaria a definir scope;
- entra em conflito com roles personalizadas;
- não garante auditabilidade de ownership global.

### C. Inferir por `municipality_id = null`

Manter o comportamento atual e fazer backfill de todos os utilizadores sem
Município.

Rejeitada:

- promoveria candidatos ou contas incompletas;
- permite self-escalation indireta;
- ausência de tenancy não prova autorização global;
- viola menor privilégio e a condição expressa da Sprint 47A.

### D. Entidade explícita de associação

Criar `platform_operator_assignments`:

- `id`;
- `user_id`;
- `scope_mode`, enum tipado `global|limited`;
- `status`, enum tipado `active|revoked`;
- `granted_by`;
- `granted_at`;
- `grant_justification`;
- `revoked_by`;
- `revoked_at`;
- `revoke_justification`;
- timestamps.

Para evolução limitada:

- tabela associativa `platform_operator_municipalities`;
- unicidade por operador e Município;
- Policies para gestão da atribuição.

Vantagens:

- scope explícito;
- histórico e justificação;
- revogação sem apagar evidência;
- não depende de role;
- suporta evolução global/limitada;
- permite fail-closed sem associação ativa.

É a alternativa recomendada.

## 4. Decisão proposta

Adotar a alternativa D num rollout em duas fases:

### Fase 1 - estrutura e backfill explícito

1. criar enums, entidade, migration reversível e índices;
2. criar Service transacional de concessão/revogação;
3. impedir self-grant e self-revoke inseguro;
4. registar before/after, ator e justificação;
5. fornecer comando operacional idempotente que aceite IDs explicitamente
   aprovados por ambiente;
6. manter temporariamente a Policy anterior apenas durante a janela controlada
   de backfill;
7. confirmar que existe pelo menos um operador ativo aprovado.

### Fase 2 - enforcement

1. substituir inferência de `municipality_id` por associação ativa;
2. atualizar Policy, serviços e testes;
3. retirar compatibilidade temporária;
4. testar operador, administrador municipal, candidate e auditor;
5. testar scope limitado quando for ativado;
6. auditar todas as concessões e revogações.

O comando de backfill não deve aceitar `--all-without-municipality`, role,
wildcard ou pesquisa por email. A lista de IDs deve ser aprovada fora do
repositório e fornecida explicitamente por ambiente.

## 5. Bloqueio

A implementação não pode começar sem:

- lista aprovada dos IDs dos operadores atuais em cada ambiente;
- confirmação de pelo menos dois responsáveis sobre essa lista;
- definição do operador que executa o bootstrap inicial;
- runbook de rollout e rollback;
- decisão sobre operador global versus limitado.

Sem estes dados, qualquer migration de backfill seria ambígua e poderia
conceder ou remover acesso global indevidamente.

## 6. Segurança e RGPD

- nenhuma role concede scope de plataforma por si só;
- ausência de Município falha fechado;
- candidate nunca pode receber associação;
- auditor permanece read-only;
- grants e revogações exigem MFA, permission específica e justificação;
- nenhuma permission é atribuída diretamente ao utilizador;
- o log não deve incluir payloads, documentos ou PII;
- a associação deve ser soft-revoked, não apagada;
- operações recusadas não podem alterar estado.

## 7. Rollback

A migration estrutural futura deve remover primeiro chaves e índices e depois
as tabelas, sem alterar `users.municipality_id`. O rollback funcional deve
repor temporariamente a Policy anterior apenas durante incidente controlado e
com registo explícito; não deve ser usado como compatibilidade permanente.

## 8. Consequências

- A Sprint 47A fica bloqueada antes da migração das 72 rotas inventariadas.
- As Sprints 47B-47H não arrancam, porque devem derivar do HEAD publicado da
  sprint anterior.
- Não foi criada dívida de schema parcial.
- O risco herdado permanece documentado, sem ser agravado.
