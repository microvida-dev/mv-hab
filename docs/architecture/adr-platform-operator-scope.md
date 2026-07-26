# ADR - Scope estrutural do operador de plataforma

- Estado: **APROVADO TECNICAMENTE — rollout condicionado**
- Data: 2026-07-23
- Sprint: 47A.0
- Âmbito: administração global, entitlements e isolamento municipal

## Contexto

O comportamento histórico inferia acesso de plataforma através de
`users.municipality_id === null`. A ausência de Município não constitui uma
atribuição de acesso: também ocorre em candidatos, contas incompletas e
fixtures. Uma role fixa, um email ou uma permission isolada também não provam
designação como operador global.

O programa 47 exige uma origem de scope explícita, auditável, revogável e
independente de roles. A atribuição deve continuar separada das permissions:

```text
associação global ativa
&& permission da operação através de role ativa
&& Policy permite o alvo
```

## Decisão

Adotar `platform_operator_assignments` como entidade estrutural.

A primeira versão suporta apenas scope global:

- um registo único por utilizador;
- estados `active` e `revoked`;
- origens `bootstrap` e `platform_operator`;
- concessão, revogação, ator, data e justificação;
- duas referências de aprovação obrigatórias no bootstrap;
- revogação lógica, sem eliminação física;
- nenhum backfill na migration;
- nenhuma alteração a `users.municipality_id`;
- nenhuma role fixa de operador;
- nenhuma permission direta por utilizador;
- nenhuma tabela de scope municipal limitado.

`PlatformOperatorScope::Global` é o único scope tipado desta versão. Scope
limitado exigirá novo ADR e modelo de dados próprio.

## Invariantes

Uma conta pode deter scope global apenas quando:

- está ativa;
- não é candidate;
- não pertence a um Município;
- possui associação ativa e não revogada.

A associação não atribui permissions. As permissions são obtidas
exclusivamente através de roles ativas.

Bootstrap:

- usa um manifesto JSON externo ao repositório;
- aceita apenas IDs explícitos;
- exige duas referências de aprovação distintas;
- valida o ambiente;
- exige conta dedicada e MFA confirmado;
- é idempotente;
- não seleciona por role, email ou ausência de Município;
- não atribui roles nem permissions.

Concessão normal:

- só pode ser realizada por operador global ativo;
- exige `platform_operators.manage`;
- exige sessão MFA verificada;
- exige justificação;
- impede self-grant.

Revogação:

- preserva roles e permissions;
- impede a revogação do último operador ativo;
- permite self-revoke apenas quando outro operador permanece ativo;
- nunca elimina a evidência.

## Enforcement

`MunicipalityFeatureEntitlementPolicy` deixou de usar
`municipality_id === null` como fallback. As operações de plataforma falham
fechado sem associação ativa.

A administração de operadores usa:

- `platform_operators.view`;
- `platform_operators.manage`;
- `platform_operators.audit`;
- `auth`;
- `active.backoffice`;
- `mfa.backoffice`;
- `log.backoffice`;
- Policy do registo.

Auditor pode consultar apenas quando possui associação e permissions
adequadas. Candidate e utilizador municipal não recebem scope global.

## Auditoria

São registados:

- `platform_operator_bootstrapped`;
- `platform_operator_granted`;
- `platform_operator_revoked`.

Os eventos guardam IDs técnicos, origem, estado anterior/seguinte,
referências de aprovação, justificação, identificador de operação e timestamp.
Não guardam passwords, tokens, segredos MFA, documentos ou payloads de
identidade.

## Rollout

O código e os testes podem ser publicados sem executar o bootstrap. O acesso
real permanece condicionado a um manifesto externo aprovado.

Estados:

- `REPOSITORY_PASS`: implementação e gates do repositório concluídos;
- `DEPLOYMENT_GATED`: associação real ainda não inicializada;
- `DEPLOYED`: apenas após output real do comando e validação pós-deploy.

Factories e testes não constituem evidência de rollout.

## Alternativas rejeitadas

- coluna booleana ou enum em `users`: mistura identidade e autorização e não
  mantém evidência de concessão;
- role `platform_operator`: reintroduz autorização role-first;
- inferência por `municipality_id = null`: promove contas ambíguas;
- backfill por administrator, permission ou email: não existe aprovação
  estrutural;
- fallback `assignment OR municipality_id null`: mantém o bypass.

## Consequências

- ambientes sem manifesto falham fechado nas operações globais;
- o deploy deve seguir o runbook e pode exigir janela de manutenção;
- contas municipais continuam isoladas;
- revogações são rastreáveis;
- o modelo pode evoluir para scope limitado apenas por decisão arquitetural
  posterior;
- não existe caminho automático de reativação de uma associação revogada.

## Rollback

Não é permitido reintroduzir a inferência por `municipality_id null`.
Incidentes devem usar manutenção, diagnóstico, correção do manifesto ou
roll-forward. A migration só pode ser revertida antes de existir evidência
operacional e em conjunto com uma versão compatível que continue fail-closed.

Consultar `docs/runbooks/platform-operator-rollback.md`.
