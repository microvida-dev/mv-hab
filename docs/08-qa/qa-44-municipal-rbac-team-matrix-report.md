# QA-44 Municipal RBAC and Team Matrix Report

## Sumario executivo

QA-44 validou a matriz de roles, equipas municipais, permissoes negativas e ownership operacional por equipa, preservando o RBAC criado/reforcado na QA-30 e os Work Tasks da QA-31.

## Ficheiros analisados

- `config/mvhab.php`
- `database/seeders/SystemAccessSeeder.php`
- `database/seeders/MunicipalTeamSeeder.php`
- `app/Models/User.php`
- `app/Models/MunicipalTeam.php`
- `app/Models/WorkTask.php`
- `app/Policies/WorkTaskPolicy.php`
- `app/Policies/RoleAssignmentPolicy.php`
- `app/Policies/TeamManagementPolicy.php`

## Alteracoes implementadas

- `docs/11-operacoes/municipal-rbac-team-matrix.md`
- `docs/11-operacoes/municipal-access-review-checklist.md`
- Testes negativos de RBAC/equipas.

## Testes criados

- `tests/Feature/QA44MunicipalRbacTeamMatrixTest.php`
- `tests/Feature/Security/MunicipalRbacNegativeAuthorizationTest.php`
- `tests/Feature/Security/MunicipalTeamOwnershipTest.php`
- `tests/Feature/Security/CandidateTenantIdorProtectionTest.php`
- `tests/Feature/Security/SensitiveBackofficeAccessTest.php`
- `tests/Feature/Security/AuditorReadOnlyAccessTest.php`

## Validacoes

- Roles institucionais existem e sao preservadas.
- 8 equipas municipais existem e estao ativas.
- Candidate/inquilino funcional nao recebe role administrativa adicional.
- Auditor tem leitura/auditoria sem mutacao.
- Support agent nao acede a documentos sensiveis.
- Work Tasks respeitam equipa/atribuição/policy.
- Revisao trimestral de acessos documentada.

## Riscos residuais

- Rever periodicamente atribuicoes reais de utilizadores antes de staging municipal com dados reais.

## Resultado

Bloco QA-44 validado por testes de matriz e permissoes negativas.
