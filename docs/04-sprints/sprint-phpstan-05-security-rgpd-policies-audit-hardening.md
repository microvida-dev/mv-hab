# SPRINT PHPSTAN-05 — SECURITY, RGPD, POLICIES & AUDIT HARDENING

## Objetivo

Executar uma sprint de hardening focada nos riscos P0/P1 remanescentes identificados após a validação de lógica de domínio da Sprint PHPSTAN-04.

O objetivo principal não é reduzir volume bruto de erros PHPStan.  
O objetivo é reduzir risco real em:

- autorização;
- isolamento de dados;
- documentos privados;
- RGPD;
- auditoria;
- segurança de backoffice;
- downloads e exportações sensíveis;
- ações administrativas críticas.

Este sprint deve corrigir apenas erros comprovados, com teste dirigido, sem alargar permissões e sem alterar regras de negócio.

---

## Contexto

A sequência anterior produziu o seguinte estado:

| Sprint | Resultado |
| --- | --- |
| PHPSTAN-01 | Inventário inicial de 2897 erros em 561 ficheiros |
| PHPSTAN-02 | Redução para 2828 erros, com remediação segura de generics/factories/relações simples |
| PHPSTAN-03 | Redução para 2755 erros, com return types, arrays, nullability e Form Requests |
| PHPSTAN-04 | Redução para 2751 erros, com 3 bugs reais corrigidos e testes dirigidos |

A Sprint PHPSTAN-04 confirmou que os próximos riscos prioritários estão em:

- Policies;
- Security;
- RGPD;
- Audit;
- Documentos privados;
- Relações nullable em autorização;
- enum/string mismatch em decisões sensíveis;
- subject/actor incorretos em logs e pedidos RGPD;
- exportações e downloads sensíveis.

---

## Princípios Absolutos

Não é permitido:

- alargar permissões para eliminar erros PHPStan;
- substituir validações de autorização por `true`;
- usar `@phpstan-ignore`, baseline ou suppressions;
- alterar regras de elegibilidade;
- alterar regras de pontuação;
- alterar regras de classificação;
- alterar workflows de candidatura;
- alterar regras financeiras;
- alterar retenção RGPD sem prova funcional;
- alterar serialização de dados pessoais sem teste;
- remover auditoria existente;
- reduzir granularidade dos logs;
- expor paths de ficheiros;
- tornar documentos públicos por omissão;
- alterar storage privado;
- alterar middleware de segurança.

Cada correção deve ser mínima, comprovada e acompanhada por teste dirigido.

---

## Âmbito Prioritário

### Grupo A — Policies e autorização

Analisar e corrigir, apenas com prova funcional:

- `AdditionalDocumentSubmissionPolicy`
- `AdhesionRegistrationPolicy`
- `AdministrativeDecisionPolicy`
- `ApplicationPolicy`
- `CommunicationReceiptPolicy`
- `ComplaintDecisionPolicy`
- `ControlledWithdrawalPolicy`
- `CurrentHousingSituationPolicy`
- `HouseholdMemberPolicy`
- `HouseholdPolicy`
- `IncomeChangeDeclarationPolicy`
- `IncomeRecordPolicy`
- `MaintenanceRequestPolicy`
- `ReportExportPolicy`

Erros típicos:

- `property.nonObject`
- `method.nonObject`
- `method.notFound`
- enum/string mismatch
- relação nullable usada como obrigatória
- relação inferida como `Model|null`
- métodos chamados em `string`

Objetivo:

- manter autorização igual ou mais restritiva;
- corrigir null-safety;
- tipar relações críticas;
- adicionar guards explícitos;
- garantir que falhas produzem `false`, nunca autorização indevida.

---

### Grupo B — RGPD

Analisar e corrigir:

- `DataSubjectRequestService`
- `DataExportService`
- `DataInventoryService`
- `AnonymizationService`
- `RetentionExecutionService`
- `RetentionPolicyService`
- `UserConsentService`
- `ConsentPurposeService`
- `DataSubjectRequestWorkflowService`

Erros típicos:

- `argument.type`
- `property.notFound`
- `property.nonObject`
- `return.type`
- `missingType.iterableValue`
- `notIdentical.alwaysTrue`
- `deadCode.unreachable`
- `nullsafe.neverNull`

Objetivo:

- garantir `actor` e `subject` corretos;
- garantir exportações sem dados errados;
- garantir hash de conteúdo apenas com string válida;
- garantir anonimização e retenção sem estados impossíveis;
- garantir auditoria de pedidos RGPD;
- garantir null-safety em relações de consentimento.

---

### Grupo C — Segurança

Analisar e corrigir:

- `AccessLogService`
- `MfaDeviceService`
- `SecurityAlertService`
- `SensitiveDataAccessService`
- `DocumentStorageSecurityReviewService`
- `SensitiveFieldEncryptionReviewService`
- `PasswordPolicyService`
- `BackupReviewService`

Erros típicos:

- parsing inseguro de recovery codes;
- `hash_equals()` com `null`;
- enum/string mismatch em severidade e tipo de acesso;
- `instanceof` sempre verdadeiro/falso;
- `offsetAccess.nonOffsetAccessible`;
- `property.notFound` em códigos MFA;
- metadados sem shape.

Objetivo:

- não enfraquecer MFA;
- não expor recovery codes;
- não reduzir auditoria de acessos sensíveis;
- manter logs úteis e sem PII excessiva;
- garantir parsing defensivo.

---

### Grupo D — Auditoria

Analisar e corrigir:

- `AuditTrailService`
- `AuditLogger`
- `AuditRetentionService`
- todos os pontos onde `AuditTrailService::record()` recebe `actor` ou `subject`.

Objetivo:

- garantir tipos corretos de `actor`;
- garantir tipos corretos de `subject`;
- manter registos de ações críticas;
- evitar registos com modelos errados;
- evitar perda de rastreabilidade administrativa.

---

### Grupo E — Documentos privados e relatórios sensíveis

Analisar e corrigir apenas se houver teste dirigido:

- policies de documentos;
- downloads de documentos;
- logs de acesso documental;
- exportações de relatórios;
- `ReportExportPolicy`;
- `ReportPermissionService`;
- `ReportDownloadService`;
- `ReportRunService`.

Objetivo:

- não permitir acesso transversal entre candidatos;
- não expor documentos privados;
- não permitir exportações sem permissão;
- não alterar permissões para “resolver” PHPStan.

---

## Fase 1 — Baseline Técnico

Executar:

```bash
php artisan optimize:clear
./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-05-before.txt
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Registar:

- total de erros;
- erros por identificador;
- erros por domínio;
- testes existentes;
- falhas de ambiente.

Não iniciar correções antes de ter baseline.

---

## Fase 2 — Inventário P0/P1

Criar tabela dos erros alvo:

| Ficheiro | Linha | Identificador | Área | Risco | Decisão |
| --- | ---: | --- | --- | --- | --- |
| `...` | `...` | `...` | Policy/RGPD/Security/Audit | P0/P1 | corrigir/adiar/FP |

Classificação permitida:

| Código | Significado |
| --- | --- |
| BR | Bug real |
| SR | Segurança real |
| RGPD | Risco RGPD |
| RF | Risco funcional |
| FP | Falso positivo provável |
| DT | Dívida técnica |
| AD | Adiar com justificação |

---

## Fase 3 — Testes Antes da Correção

Antes de alterar código, criar ou identificar teste dirigido.

### Tests mínimos obrigatórios por área

#### Policies

Criar testes para:

- candidato só acede aos seus dados;
- técnico autorizado acede apenas via role/policy válida;
- utilizador sem permissão recebe 403;
- relações ausentes devolvem `false`, não erro 500;
- policies não alargam permissões.

#### RGPD

Criar testes para:

- criar pedido de titular com subject correto;
- atribuir pedido RGPD a utilizador válido;
- exportação de dados com ficheiro válido;
- exportação falha de forma controlada se conteúdo inválido;
- retenção/anonimização respeita estado aprovado;
- ações ficam auditadas.

#### Security/MFA

Criar testes para:

- recovery code inválido não causa warning/erro;
- `hash_equals()` nunca recebe `null`;
- alertas de segurança tratam enum/string corretamente;
- sensitive access logs registam actor/subject corretos.

#### Audit

Criar testes para:

- ações críticas geram audit trail;
- actor nulo só é permitido quando explicitamente esperado;
- subject incorreto não é aceite silenciosamente.

---

## Fase 4 — Correções Permitidas

São permitidas:

- guards explícitos com retorno seguro;
- casts explícitos quando o model já define enum/datetime e o comportamento runtime é confirmado;
- `instanceof` antes de aceder a enum;
- validação de `string` antes de `hash`, `hash_equals`, `Storage::put`, `Storage::download`;
- tipagem PHPDoc de arrays/metadados;
- estreitamento de tipos antes de chamar services;
- `abort(404)` ou `false` em branches inseguras;
- retorno `false` em policies quando a relação obrigatória está ausente;
- testes de regressão.

Exemplo seguro em policy:

```php
if (! $application instanceof Application) {
    return false;
}
```

Exemplo seguro em RGPD:

```php
if (! is_string($contents)) {
    throw new RuntimeException('Unable to generate data export contents.');
}
```

Exemplo seguro em enum:

```php
if (! $status instanceof ApplicationStatus) {
    return false;
}
```

---

## Fase 5 — Correções Proibidas

São proibidas:

```php
return true;
```

em policies apenas para calar PHPStan.

São proibidas:

```php
/** @phpstan-ignore-next-line */
```

São proibidas alterações como:

```php
public function canView(User $user, mixed $subject): bool
```

se isso esconder erro de domínio.

São proibidos casts em massa sem teste.

São proibidas alterações em migrations para corrigir PHPStan.

São proibidas alterações em enums sem validar dados existentes.

---

## Fase 6 — Lotes de Execução

Executar por lotes pequenos.

### Lote 1 — Policies de baixo risco

Foco:

- relações nullable;
- retorno `false` seguro;
- enum guards;
- testes de 403/200.

Executar validação após lote.

### Lote 2 — Security/MFA

Foco:

- `MfaDeviceService`;
- `SecurityAlertService`;
- `AccessLogService`;
- `SensitiveDataAccessService`.

Executar validação após lote.

### Lote 3 — RGPD

Foco:

- `DataSubjectRequestService`;
- `DataExportService`;
- `DataInventoryService`;
- `RetentionExecutionService`;
- `UserConsentService`.

Executar validação após lote.

### Lote 4 — Audit e Reports sensíveis

Foco:

- `AuditTrailService`;
- `AuditLogger`;
- `ReportExportPolicy`;
- `ReportPermissionService`;
- `ReportDownloadService`.

Executar validação após lote.

---

## Fase 7 — Validação Após Cada Lote

Após cada lote executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-05-after-loteX.txt
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Critérios:

- 0 novos identificadores PHPStan;
- nenhuma falha de testes;
- nenhuma permissão alargada;
- nenhum documento privado tornado público;
- nenhuma auditoria removida;
- nenhum erro novo em RGPD/Security/Audit.

---

## Fase 8 — Relatório Final

Criar:

```text
docs/qa/phpstan-05-security-rgpd-policies-audit-hardening-report.md
```

O relatório deve conter:

1. resumo executivo;
2. métricas antes/depois;
3. erros P0/P1 analisados;
4. bugs reais corrigidos;
5. riscos RGPD mitigados;
6. riscos de segurança mitigados;
7. policies corrigidas;
8. testes criados;
9. testes executados;
10. permissões verificadas;
11. erros adiados e justificação;
12. riscos residuais;
13. recomendação para PHPSTAN-06.

---

## Critérios de Sucesso

A sprint só é concluída se:

- todos os erros P0/P1 de Policies/Security/RGPD/Audit forem classificados;
- pelo menos os bugs reais comprovados forem corrigidos;
- todas as correções tiverem teste dirigido;
- `pint --test` estiver verde;
- PHPUnit completo estiver verde;
- PHPStan não tiver novos identificadores;
- não houver widening de permissões;
- não houver perda de auditoria;
- não houver exposição de documentos privados;
- o relatório final documentar riscos residuais.

---

## Resultado Esperado

No final da Sprint PHPSTAN-05, a plataforma deve estar mais segura e juridicamente defensável, mesmo que o número total de erros PHPStan reduza pouco.

A prioridade é transformar a dívida PHPStan remanescente em hardening real de produto, protegendo os domínios que mais podem afetar confiança institucional: autorização, RGPD, documentos privados, auditoria e segurança.
