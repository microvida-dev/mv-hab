# Relatório PHPSTAN-15 — Security, RGPD, Audit & Policies Final Hardening

## Resumo executivo

A sprint PHPSTAN-15 foi executada com foco conservador em segurança, RGPD, auditoria, MFA, privacidade e models diretamente associados.

Resultado final:

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros PHPStan globais reportados pelo wrapper | 544 | 487 | -57 |
| Erros normalizados por assinatura | 542 | 485 | -57 |
| Ficheiros com erros normalizados | 208 | 181 | -27 |
| Erros novos por assinatura exata | 0 | 0 | 0 |
| Erros remanescentes no perímetro Security/RGPD/Audit/Policies | 57 | 0 | -57 |
| `missingType.generics` | 255 | 234 | -21 |
| `missingType.iterableValue` | 89 | 69 | -20 |
| `argument.type` | 24 | 23 | -1 |
| `property.nonObject` | 17 | 15 | -2 |
| `instanceof.alwaysTrue` | 4 | 3 | -1 |

O PHPStan continua a devolver código 1 porque permanecem 487 erros legados fora do perímetro desta sprint. A meta quantitativa de -100 erros não foi forçada porque o conjunto seguro e diretamente associado ao âmbito continha 57 erros; todos foram removidos com `exact_new = 0`.

## Ficheiros alterados por domínio

### Models de segurança, RGPD e auditoria

- `app/Models/AccessLog.php`
- `app/Models/AuditEvent.php`
- `app/Models/SensitiveDataAccessLog.php`
- `app/Models/MfaDevice.php`
- `app/Models/ConsentPurpose.php`
- `app/Models/RetentionPolicy.php`
- `app/Models/RetentionExecution.php`
- `app/Models/SecurityAlert.php`
- `app/Models/SecurityAlertRule.php`
- `app/Models/AnonymizationRequest.php`

### Serviços de auditoria

- `app/Services/Audit/AuditLogger.php`
- `app/Services/Audit/AuditTrailService.php`
- `app/Services/Audit/AuditEventFormatter.php`
- `app/Services/Audit/AuditRetentionService.php`

### Serviços de segurança

- `app/Services/Security/AccessLogService.php`
- `app/Services/Security/SensitiveDataAccessService.php`
- `app/Services/Security/SecurityAlertService.php`
- `app/Services/Security/BackupReviewService.php`
- `app/Services/Security/DocumentStorageSecurityReviewService.php`
- `app/Services/Security/PasswordPolicyService.php`
- `app/Services/Security/SensitiveFieldEncryptionReviewService.php`

### Serviços RGPD

- `app/Services/Rgpd/AnonymizationService.php`
- `app/Services/Rgpd/DataSubjectRequestWorkflowService.php`
- `app/Services/Rgpd/ConsentPurposeService.php`
- `app/Services/Rgpd/RetentionPolicyService.php`
- `app/Services/Rgpd/RetentionExecutionService.php`

### Controllers e Form Requests

- `app/Http/Controllers/Backoffice/Security/MfaController.php`
- `app/Http/Controllers/Backoffice/Security/PrivacyController.php`
- `app/Http/Requests/StoreAnonymizationRequestRequest.php`

## Tipo de correções

- Generics Eloquent em relações `BelongsTo`, `HasMany` e `MorphTo`.
- PHPDoc de casts enum já existentes para `AccessLogType`, `SecurityAlertSeverity`, `RetentionAction`, `RetentionExecutionStatus` e `AnonymizationStatus`.
- Shapes de arrays para payloads RGPD, metadados de auditoria, relatórios de segurança e recomendações operacionais.
- Remoção de guards redundantes tornados impossíveis pela tipagem, sem reduzir autorização.
- Guards explícitos contra relação RGPD obrigatória ausente em execução de retenção.
- Alinhamento do `StoreAnonymizationRequestRequest` com payload tipado e validação de `scope.*`.
- Correção explícita de `diffInDays()` do Carbon 3 para retorno inteiro em `DataSubjectRequestWorkflowService`.

Não foram alteradas regras de permissões, policies, MFA, auditoria, RGPD, documentos privados, elegibilidade, classificação, concursos, candidaturas, contratos, rendas, manutenção ou workflows administrativos.

## Artefactos gerados

- `storage/phpstan/phpstan-15-before.txt`
- `storage/phpstan/phpstan-15-after-models.txt`
- `storage/phpstan/phpstan-15-after-policies.txt`
- `storage/phpstan/phpstan-15-after-security.txt`
- `storage/phpstan/phpstan-15-after-rgpd.txt`
- `storage/phpstan/phpstan-15-after-audit.txt`
- `storage/phpstan/phpstan-15-final.txt`
- `storage/phpstan/phpstan-15-summary.txt`
- `storage/phpstan/phpstan-15-directed-tests.txt`
- `storage/phpstan/phpstan-15-phpunit.txt`
- `storage/phpstan/phpstan-15-pint-final.txt`
- `storage/phpstan/phpstan-15-route-list.txt`
- `storage/phpstan/phpstan-15-optimize-clear-before.txt`
- `storage/phpstan/phpstan-15-optimize-clear-final.txt`

## Testes e comandos

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'Policy|Candidate|Document|Security|Mfa|AccessLog|Rgpd|Privacy|Consent|Export|Audit|Access|Sensitive'` | OK — 185 testes, 1178 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK — 283 testes, 1775 asserções |
| `php artisan route:list --except-vendor` | OK — 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada — 487 erros legados remanescentes |

## Bugs reais encontrados

Não foi confirmado bug funcional em produção nesta sprint.

Foram reforçados contratos que podiam esconder problemas de integridade:

- execução de retenção sem política associada passa a falhar com erro explícito antes de avaliar aprovação;
- payload de anonimização passou a validar cada item de `scope` como string;
- `remainingDays()` passou a refletir explicitamente o contrato inteiro declarado.

## Falsos positivos tratados

- Casts enum vistos pelo PHPStan como `string`.
- Relações Eloquent vistas como `Model|null`.
- `MorphTo` sem generics.
- Arrays de metadados, recomendações e relatórios sem value type.
- `Request` resolvido via container visto como `mixed`.
- Closure de MFA com `instanceof` redundante após coleção tipada.

## Correções adiadas

Permanecem erros fora do perímetro desta sprint, sobretudo em:

- models com relações Eloquent ainda sem generics;
- services de Tenant Portal, billing, visits e maintenance;
- seeders/factories com array shapes pendentes;
- erros de `method.nonObject`, `return.type` e `property.nonObject` em domínios não Security/RGPD/Audit.

## Riscos residuais

- PHPStan global ainda falha com 487 erros legados.
- O projeto continua sem repositório Git disponível no diretório atual; não foi possível obter `git diff` ou `git status`.
- A redução de -100 erros não foi atingida porque implicaria avançar para domínios fora do âmbito seguro desta sprint.
- CI/CD deve continuar a aplicar `exact_new = 0` e impedir regressões estáticas novas.

## Recomendação

Considerar a PHPSTAN-15 concluída para Security/RGPD/Audit/Policies.

Próxima etapa recomendada: continuar a remediação PHPStan por domínios restantes, começando por models com `missingType.generics` e depois services com `method.nonObject`/`return.type`, mantendo a mesma política:

1. `exact_new = 0`.
2. Sem baseline nem suppressions.
3. Sem alterações funcionais oportunistas.
4. Testes dirigidos antes de tocar em lógica administrativa crítica.
