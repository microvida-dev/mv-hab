# Sprint 47A.0 - Scope estrutural do operador de plataforma

## Objetivo

Substituir a inferência `municipality_id === null` por uma associação global
explícita, auditável e revogável, sem role fixa nem permission direta.

## Branch e base

- branch: `sprint-47a0-platform-operator-scope`;
- commit-base: `3555c0e402eee330586da9041d9b1f2ac396c7e8`;
- base publicada: documentação de bloqueio sobre a Sprint 46E;
- rollout externo: condicionado a manifesto aprovado.

Commits de implementação:

- `935b87ec` - `feat(security): formalizar scope de operador de plataforma`;
- `dc33ef95` - `feat(access): gerir operadores de plataforma por permissões`;
- `7d4d65db` - `test(access): cobrir fronteiras de operador de plataforma`.

## Estado herdado

A branch 47A bloqueada foi comparada com a 46E. Continha apenas o ADR e o
relatório do bloqueio, sem código funcional parcial. A 47A.0 foi criada a
partir dessa base para preservar a evidência documental.

Baseline inicial real:

- PHPUnit: 1 076 testes, 7 629 asserções;
- UX: 130 testes, 645 asserções;
- rotas totais: 1 165;
- rotas backoffice com role fixa: 706;
- rotas candidate com role fixa: 220;
- rotas com middleware de permission: 195;
- rotas backoffice fixas sem active/MFA/log: 594;
- Pint: PASS;
- PHPStan: 0 erros;
- Composer: PASS;
- build: PASS.

## Implementação

### Modelo estrutural

Foi criada a migration reversível
`2026_07_23_000036_create_platform_operator_assignments_table.php`, sem
backfill e sem alteração a `users.municipality_id`.

O modelo mantém:

- utilizador único;
- estado tipado `active|revoked`;
- origem tipada `bootstrap|platform_operator`;
- ator/data/justificação da concessão;
- duas referências de aprovação no bootstrap;
- ator/data/justificação da revogação;
- revogação lógica;
- bloqueio de eliminação física.

### Services

`PlatformOperatorScopeService`:

- resolve apenas `PlatformOperatorScope::Global`;
- exige conta ativa, não candidate e sem Município;
- exige associação ativa;
- conta operadores ativos;
- identifica o último operador;
- falha fechado.

`PlatformOperatorManagementService`:

- valida bootstrap, concessão e revogação;
- usa transações e `lockForUpdate`;
- não atribui roles nem permissions;
- impede self-grant;
- protege o último operador;
- permite self-revoke seguro;
- exige MFA e permission na gestão normal;
- não reativa automaticamente associações revogadas;
- audita operações.

### Bootstrap

O comando `platform-operators:bootstrap`:

- exige `--manifest`;
- aceita `--dry-run` e `--confirm`;
- rejeita manifesto dentro do repositório;
- valida ambiente, IDs e duas aprovações;
- não seleciona por email, role ou ausência de Município;
- não expõe PII no output;
- é idempotente;
- não foi executado contra ambiente externo.

### Policies e rotas

`MunicipalityFeatureEntitlementPolicy` usa agora
`PlatformOperatorScopeService`, sem fallback por null.

Foi criada `PlatformOperatorAssignmentPolicy` com abilities:

- `viewAny`;
- `view`;
- `create`;
- `revoke`;
- `auditAny`.

As cinco rotas novas usam:

- `auth`;
- `active.backoffice`;
- `mfa.backoffice`;
- `log.backoffice`;
- permission específica;
- Policy;
- nenhuma role fixa.

### Administração mínima

Foi criada interface backoffice para:

- listar associações;
- consultar detalhe;
- conceder scope;
- revogar scope;
- consultar auditoria.

A navegação só apresenta o módulo quando `viewAny` da Policy permite.

### Permissions

Foram adicionadas ao catálogo:

- `platform_operators.view`;
- `platform_operators.manage`;
- `platform_operators.audit`.

São sensíveis para MFA e obtidas apenas por roles. Não existe tabela nem
atribuição direta de permission por utilizador.

### Auditoria

Eventos:

- `platform_operator_bootstrapped`;
- `platform_operator_granted`;
- `platform_operator_revoked`.

Metadata minimizada:

- IDs técnicos;
- origem;
- before/after;
- referências de aprovação;
- justificação;
- operation ID;
- timestamp.

## Testes

Foram criados os nove conjuntos obrigatórios:

- `PlatformOperatorAssignmentPersistenceTest`;
- `PlatformOperatorScopeServiceTest`;
- `PlatformOperatorManagementServiceTest`;
- `PlatformOperatorBootstrapCommandTest`;
- `PlatformOperatorPolicyTest`;
- `PlatformOperatorManagementTest`;
- `PlatformOperatorAuditTest`;
- `PlatformOperatorLastActiveProtectionTest`;
- `PlatformOperatorMunicipalBoundaryTest`.

Foi reforçado `MunicipalityFeatureManagementTest` para criar associações
explícitas em vez de depender de `municipality_id === null`.

Validação direcionada:

- 29 testes;
- 202 asserções;
- PASS.

Validação direcionada com auditoria de rotas:

- 31 testes;
- 610 asserções;
- PASS.

Gates globais:

- PHPUnit: 1 100 testes, 7 777 asserções, PASS;
- UX: 130 testes, 645 asserções, PASS;
- integridade dos testes: 0 violações e 0 avisos;
- Pint global: PASS;
- PHPStan global: 0 erros;
- Composer `validate --strict`: PASS;
- `php artisan optimize:clear`: PASS;
- build Vite: PASS;
- `php artisan route:list --except-vendor`: PASS;
- `git diff --check`: PASS.

Auditoria de rotas depois da implementação:

- rotas totais: 1 170;
- rotas com role fixa: 926;
- rotas backoffice com role fixa: 706;
- rotas candidate com role fixa: 220;
- rotas com middleware de permission: 200;
- rotas backoffice fixas sem active/MFA/log: 594.

As cinco rotas novas são permission-first e não alteram o universo histórico
das 706 rotas a migrar na 47A.1 a 47H.

## Segurança e RGPD

- candidate permanece bloqueado;
- utilizador municipal não recebe scope global;
- auditor é read-only;
- conta inativa é recusada;
- MFA é exigido;
- associação não concede permission;
- não existe self-escalation;
- não existe fallback por null;
- output do comando não contém nome ou email;
- auditoria não contém segredos;
- recusas não mutam dados.

## Migration

- criada: `platform_operator_assignments`;
- reversível;
- sem backfill;
- sem perda de dados em `users`;
- sem tabela de scope limitado.

## Runbooks

- `docs/runbooks/platform-operator-bootstrap.md`;
- `docs/runbooks/platform-operator-rollback.md`.

## Riscos residuais

- o rollout real depende de manifesto externo aprovado;
- antes do bootstrap, operações de plataforma falham fechado;
- uma associação revogada não tem reativação automática;
- scope limitado não faz parte desta versão;
- a migração das 72 rotas de administração pertence à 47A.1.

## Classificação

**REPOSITORY_PASS_DEPLOYMENT_GATED**.

Não existe evidência de bootstrap em staging ou produção e não é declarado
estado `DEPLOYED`.
