# Sprint 46A — Auditoria de integridade dos testes

## Identificação

- ID: `TECH-TEST-001`
- Branch: `sprint-46a-regression-test-integrity`
- Base validada: `d00406d3b22fee5dabfd326495eac752d9276312`
- Primeiro commit inequívoco da 45B: `03cd51a1aa3be5a5c1f4507ffcf0a914375a625c`
- Commit anterior à 45B: `49507e7cee0519c010dfa5dd0967bff0652a62df`

## Objetivo

Auditar todas as alterações em testes das Sprints 45B, 45C e 45D, confirmar a representação correta de permission middleware, MFA, Município, entitlements e Policies, e eliminar fixtures municipais excessivamente permissivas sem alterar o comportamento de produção.

## Commits

- `772ab203` — `docs(test): inventariar regressões de autorização`
- `4b9e4813` — `test(entitlements): explicitar funcionalidades municipais`
- `d1384fe1` — `build(quality): adicionar auditoria de integridade dos testes`
- relatório final: este commit

## Âmbito alterado

- 3 artefactos de qualidade em `docs/quality`.
- 47 ficheiros de teste/concerns corrigidos para features explícitas.
- 1 teste unitário novo para o gate.
- 1 script de qualidade sem dependências externas.
- `composer.json`, apenas para adicionar `quality:tests:integrity`.
- 0 ficheiros de produção.
- 0 controllers, middleware, Policies, services, routes ou migrations alterados.

## Inventário 45B–45D

O intervalo contém 93 ficheiros:

- 45 adicionados;
- 48 modificados;
- origem provável: 45B=50, 45C=16 e 45D=27;
- todos têm classificação e ação documentadas.

Artefactos:

- `docs/quality/tech-test-001-regression-inventory.json`
- `docs/quality/tech-test-001-regression-inventory.md`
- `docs/quality/tech-test-001-critical-coverage-map.md`

O JSON inclui caminho, sprint provável, tipo, domínio, razão, alterações MFA/Município/entitlement, features, permissions, Policies, assertions, mocks, helpers, cobertura multi-Município, risco, classificação e ação.

## Decisões arquiteturais

### Fixtures municipais

`InteractsWithMunicipalFeatures` passou a:

- receber `FeatureKey` por argumentos variádicos;
- exigir uma feature explícita na criação de Município com entitlement;
- disponibilizar ativação singular e múltipla com `Municipality` explícito;
- ativar exclusivamente a lista recebida;
- não criar permissions nem roles;
- não usar `FeatureKey::cases()` como comportamento normal.

Os cenários passaram a declarar:

- intake: `ApplicationIntake`;
- revisão: `ApplicationIntake` + `ApplicationReview`;
- exportação: `ApplicationIntake` + `ApplicationExport`;
- cenários abrangentes: apenas a união expressamente exercitada.

`FeatureKey::cases()` permanece exclusivamente em `FeatureKeyTest`, para validar o catálogo e as dependências do enum.

### Avisos revistos

O gate apresentou oito avisos, todos revistos:

1. Quatro `assertSame` removidos em `AuditAccessRoutesCommandTest` foram substituídos por `assertSame` com os novos contadores reais.
2. `RbacCharacterizationTest` alterou uma recusa por fixed-role para sucesso com permission explícita, que é o requisito funcional da 45B, e acrescentou validação dos guards.
3. Duas wildcard permissions são o objeto expresso de testes dedicados à semântica wildcard; não são fixtures de conveniência.

Não foram encontrados:

- testes ignorados;
- middleware desativado em testes funcionais;
- HTTP 500 aceite;
- Gate, Policy ou `User::hasPermission()` mockados;
- mocks de entitlement/scope em testes end-to-end;
- asserções tautológicas;
- helpers de ativação global.

## Gate automático

Foi criado `scripts/quality/audit-test-integrity.php`.

Características:

- base por argumento ou `QUALITY_TEST_BASE_REF`;
- validação explícita da ref;
- cálculo de merge-base;
- nomes de ficheiros NUL-safe;
- suporte a ficheiros tracked e untracked;
- análise apenas de testes alterados;
- localização por ficheiro e linha;
- exit code não zero para violações críticas;
- avisos para assertions de segurança removidas e wildcard;
- compatível com macOS, Linux e CI;
- não altera ficheiros.

Comando:

```bash
composer quality:tests:integrity -- <base-ref>
```

Foi criado `TestIntegrityAuditScriptTest` com cobertura de:

- deteção de asserção proibida;
- aviso por remoção de asserção de segurança;
- falha clara para base inexistente.

## Testes e validações

### Testes direcionados

- Segurança, entitlements, documentos, IA documental, UX, Cases, Dashboard e Search:
  - 538 testes;
  - 4 049 assertions;
  - PASS.
- Gate automático:
  - 3 testes;
  - 28 assertions;
  - PASS.

### Suite completa

```text
1 029 testes
7 292 assertions
0 falhas
PASS
```

### UX

```text
129 testes
642 assertions
0 falhas
PASS
```

### Composer e frontend

- `composer validate --strict`: PASS.
- `npm run build`: PASS.
- `git diff --check`: PASS.

### Pint

- Ficheiros alterados: PASS.
- Global herdado: 65 ficheiros antes e 65 depois.
- Delta: 0.
- A dívida global será liquidada na Sprint 46B.

### PHPStan

- Script novo, teste novo e concern: 0 erros.
- Os mesmos 47 testes históricos foram analisados num worktree isolado:
  - HEAD 45D: 76 diagnósticos;
  - HEAD 46A: 76 diagnósticos;
  - delta: 0.
- Global configurado:
  - antes: 30 diagnósticos normalizados em 8 ficheiros;
  - depois: 30 diagnósticos normalizados em 8 ficheiros;
  - wrapper: 156 antes e 156 depois;
  - delta: 0.
- Não foram adicionadas supressões ou baseline.

### Rotas

- Total antes: 1 165.
- Total depois: 1 165.
- Inventário JSON: idêntico.

Auditoria de acesso antes/depois:

- fixed role: 926;
- backoffice fixed role: 706;
- candidate fixed role: 220;
- permission middleware: 195;
- backoffice fixed sem `active.backoffice`: 594;
- backoffice fixed sem `mfa.backoffice`: 594;
- backoffice fixed sem `log.backoffice`: 594.

Não houve alteração de rotas nem de contadores.

## Riscos e backlog

Riscos herdados, sem agravamento:

- 65 ficheiros com dívida Pint, destinados à 46B;
- 30 diagnósticos PHPStan globais normalizados, destinados à 46C;
- 76 diagnósticos de tipagem em testes quando estes são forçados fora do âmbito PHPStan global; a contagem é idêntica no HEAD 45D;
- cobertura multi-Município deve ser alargada a documentos, elegibilidade e restantes sources da pesquisa;
- asserções de ausência de efeitos devem ser uniformizadas em mutações recusadas.

## Classificação final

**PASS**

Justificação:

- suite completa e UX verdes;
- nenhum teste enfraquecido;
- nenhuma feature ativada globalmente nas fixtures normais;
- nenhuma alteração funcional de produção;
- dívida Pint/PHPStan não aumentou;
- inventário e mapa de cobertura completos;
- gate automático operacional;
- rotas e auditoria de acesso inalteradas.
