# Sprint 47A - Administração, utilizadores e segurança

## 1. Objetivo

Migrar as rotas de administração, segurança, utilizadores, equipas e RGPD para
permission-first, começando pela formalização estrutural do operador de
plataforma.

## 2. Branch e base

- branch: `sprint-47a-administration-security-permissions`;
- commit-base: `80d8be99f8ccb6be9264cab45f60cb9fb9e45779`;
- base funcional: Sprint 46E publicada;
- rotas atribuídas à 47A pelo inventário: 72.

## 3. Condição bloqueante avaliada

A Sprint exigia distinguir um operador de plataforma de um utilizador municipal
sem:

- usar nome de role;
- inferir pela ausência de Município;
- promover automaticamente utilizadores existentes;
- executar backfill ambíguo.

Foi revista a infraestrutura real:

- schema e modelo `User`;
- `MunicipalityFeatureEntitlementPolicy`;
- `MunicipalRecordScopeService`;
- `RoleAssignmentService`;
- rotas e controller de gestão de entitlements;
- `SystemAccessSeeder`, `UserSeeder`, `ProgramSeeder` e seeders municipais;
- testes de gestão de funcionalidades municipais;
- auditoria e dados locais minimizados.

## 4. Resultado da investigação

Não existe marcador estrutural de operador.

O comportamento atual usa:

```text
utilizador não candidate
&& municipality_id = null
&& permission aplicável
```

Os testes criam um administrator sem Município e chamam-lhe operador. Isto
valida o comportamento histórico, mas não constitui evidência de identidade
para um backfill.

Nos dados locais:

- existem 24 utilizadores;
- apenas 1 não tem Município;
- esse utilizador possui apenas a role candidate;
- não existem atores em eventos históricos de alteração de entitlements.

Logo, não é possível identificar operadores atuais de forma inequívoca.

## 5. ADR

Foi criado:

- `docs/architecture/adr-platform-operator-scope.md`.

A recomendação é uma entidade explícita `platform_operator_assignments`, com
scope global/limitado, estado, ator, justificação, concessão e revogação. O
rollout deve ter duas fases para preservar acesso durante um backfill aprovado.

Não foi criada migration porque faltam os IDs aprovados por ambiente e o actor
de bootstrap.

## 6. Rotas e matriz de permissions

Rotas migradas: **0 de 72**.

Nenhuma permission foi criada, alterada ou atribuída. A matriz 47A inventariada
permanece pendente:

- users: view, create, update, disable, reset password e sessões;
- roles: view, create, update, delete, assign e audit;
- teams: view, create, update e gestão de membros;
- security: view, update e audit;
- permission reviews: view, create e complete.

Os nomes reais só podem ser aplicados após desbloquear o scope estrutural.

## 7. FeatureKeys, Policies e Form Requests

- FeatureKeys criadas: nenhuma;
- Policies alteradas: nenhuma;
- Form Requests alterados: nenhum;
- Services alterados: nenhum;
- middleware alterado: nenhum.

Não foi introduzida autorização parcial nem compatibilidade insegura.

## 8. Scope municipal

O bloqueio ocorre antes da migração de rotas porque:

- `municipality_id = null` não prova scope global;
- role administrator não prova scope global;
- permissions de entitlements não provam que o utilizador foi designado
  operador;
- a base local não contém operador identificável;
- ambientes externos podem conter utilizadores diferentes e não foram
  consultados.

## 9. MFA, auditoria e feedback

O comportamento existente não foi alterado:

- MFA permanece obrigatório nas rotas já protegidas;
- auditoria existente permanece;
- feedback seguro da Sprint 46D permanece;
- candidate continua bloqueado;
- auditor continua sem mutações;
- não houve alteração de estado nem de dados.

## 10. Testes

Testes criados: nenhum.

Testes alterados: nenhum.

A Sprint parou antes da implementação, conforme condição expressa do programa.
O baseline publicado da 46E permanece:

- PHPUnit: 1.076 testes, 7.629 asserções;
- UX: 130 testes, 645 asserções;
- PHPStan: 0 erros;
- Pint: PASS;
- Composer: PASS;
- build: PASS.

## 11. Auditoria de rotas

Sem alterações:

- total: 1.165;
- role fixa: 926;
- backoffice com role fixa: 706;
- candidate com role fixa: 220;
- permission middleware: 195;
- sem active/MFA/log: 594.

## 12. Gaps e riscos

Bloqueio:

- falta lista explícita e aprovada de operadores atuais por ambiente;
- falta decisão global versus limitado;
- falta actor autorizado para bootstrap;
- qualquer backfill por null, role, email ou wildcard pode promover utilizador
  indevido;
- enforcement imediato sem backfill pode remover acesso administrativo.

O risco é herdado da 45D e não foi agravado.

## 13. Ação necessária

Para retomar:

1. fornecer IDs internos dos operadores aprovados por ambiente, fora do
   repositório;
2. aprovar `platform_operator_assignments` e o rollout em duas fases;
3. identificar o responsável pelo bootstrap inicial;
4. aprovar runbook de rollback;
5. confirmar se o primeiro release suporta apenas scope global ou também
   limitado.

## 14. Classificação final

**BLOCKED**

A condição de paragem “operador de plataforma não pode ser identificado com
segurança” foi satisfeita. Não é permitido iniciar 47B nem contornar o bloqueio
com role fixa, permission direta, FeatureKey ou backfill inferido.
