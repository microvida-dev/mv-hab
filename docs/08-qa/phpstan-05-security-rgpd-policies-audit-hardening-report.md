# Relatório PHPSTAN-05 — Security, RGPD, Policies & Audit Hardening

Data de execução: 2026-06-23  
Objetivo: reduzir erros PHPStan em domínios de segurança, RGPD, policies e auditoria, sem alterar regras funcionais de candidatura, elegibilidade, classificação, documentos, contratos ou rendas.

## 1. Resumo Executivo

O sprint foi executado de forma incremental e conservadora. Foram corrigidos erros PHPStan em caminhos críticos de MFA, exportação RGPD, inventário RGPD, consentimentos e controller RGPD de backoffice.

As alterações reforçam validação explícita de titulares `User`, evitam passagem de `Model|null` para serviços de auditoria/acesso sensível, garantem serialização JSON antes de criar pacotes RGPD e tipam relações Eloquent diretamente usadas pelos fluxos RGPD corrigidos.

Não foram alargadas permissões, não foram alteradas policies de domínio, não foram alteradas migrations, seeders, regras de elegibilidade, pontuação, classificação, listas, atribuição, contratos, rendas, manutenção ou vistorias.

## 2. PHPStan Antes/Depois

| Momento | Total de erros | Ficheiros afetados |
| --- | ---: | ---: |
| Antes | 2751 | 489 |
| Depois | 2706 | 482 |

Redução líquida: 45 erros.  
Novos erros introduzidos: 0, comparando por ficheiro, identificador e mensagem.  
Os 4 “novos” detetados quando a linha é considerada são deslocações de linha no `User.php` após inserção de PHPDoc.

## 3. Distribuição dos Erros Removidos

| Identificador | Removidos |
| --- | ---: |
| `argument.type` | 14 |
| `missingType.generics` | 12 |
| `property.notFound` | 6 |
| `missingType.iterableValue` | 3 |
| `property.nonObject` | 2 |
| `argument.unresolvableType` | 1 |
| `instanceof.alwaysTrue` | 1 |
| `nullsafe.neverNull` | 1 |
| `method.unresolvableReturnType` | 1 |
| `offsetAccess.nonOffsetAccessible` | 1 |
| `missingType.return` | 1 |

## 4. Correções Executadas

| Área | Ficheiros | Correção |
| --- | --- | --- |
| MFA | `app/Services/Security/MfaDeviceService.php` | Validação explícita do titular do dispositivo MFA, retorno `list<string>` em recovery codes, leitura segura de hash e guarda contra `unpack()` inválido. |
| RGPD export | `app/Services/Rgpd/DataExportService.php` | Guarda explícita para titular `User`, falha controlada se JSON não for serializado e auditoria/download com sujeito validado. |
| RGPD inventory | `app/Services/Rgpd/DataInventoryService.php` | Retorno `array<string, mixed>`, callback tipado para consentimentos e normalização segura de `ConsentStatus`. |
| RGPD requests | `app/Services/Rgpd/DataSubjectRequestService.php` | Tipagem do payload, performer explícito e auditoria com sujeito validado no fecho. |
| Consentimentos | `app/Services/Rgpd/UserConsentService.php` | Guarda para finalidade e titular ausentes antes de revogação/auditoria. |
| Backoffice RGPD | `app/Http/Controllers/Backoffice/Security/PrivacyController.php` | Retorno tipado de utilizadores atribuíveis e helper `findUserOrFail()` para evitar `User|Collection` em criação/atribuição de pedidos. |
| Relações Eloquent RGPD | `app/Models/DataExportPackage.php`, `app/Models/DataSubjectRequest.php`, `app/Models/User.php`, `app/Models/UserConsent.php` | PHPDoc generics nas relações diretamente usadas pelos fluxos RGPD corrigidos. |
| Testes | `tests/Feature/Sprint18RgpdSecurityAuditTest.php` | Reforço de MFA/RGPD export e novo fluxo backoffice de criação/atribuição de pedido RGPD. |

## 5. Segurança e RGPD

- MFA passa a validar explicitamente que o dispositivo pertence a um `User` antes de auditar confirmação/desativação.
- Recovery codes continuam one-time e com hash; a comparação ignora hashes inválidos em vez de falhar.
- Exportações RGPD passam a abortar se o pedido ou pacote não tiver titular válido.
- O conteúdo JSON da exportação é validado antes de gravar ficheiro, checksum e auditoria.
- Downloads RGPD continuam a passar por controller autorizado e são registados em `access_logs`, `sensitive_data_access_logs` e `audit_events`.
- Consentimentos opcionais só podem ser revogados se existir finalidade RGPD associada e titular válido.

## 6. Testes Criados ou Atualizados

Ficheiro: `tests/Feature/Sprint18RgpdSecurityAuditTest.php`

- `test_mfa_secret_is_encrypted_and_recovery_codes_are_single_use`
  - reforçado com código de recuperação inválido e TOTP malformado.
- `test_candidate_can_create_own_rgpd_request_and_export_but_cannot_access_other_candidate_request`
  - reforçado com validação de checksum, conteúdo JSON e auditoria de exportação.
- `test_backoffice_can_create_and_assign_rgpd_request_to_existing_user`
  - novo teste para criação e atribuição de pedido RGPD por backoffice.

## 7. Comandos Executados

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-05-before.txt` | Falhou com 2751 erros esperados |
| `./vendor/bin/pint --test` | OK no baseline |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK no baseline: 282 testes, 1763 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'mfa_secret_is_encrypted_and_recovery_codes_are_single_use\|candidate_can_create_own_rgpd_request_and_export_but_cannot_access_other_candidate_request'` | OK: 2 testes, 21 asserções |
| `./vendor/bin/phpstan analyse ...ficheiros tocados... --error-format=json` | OK após correções finais |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter 'mfa_secret_is_encrypted_and_recovery_codes_are_single_use\|candidate_can_create_own_rgpd_request_and_export_but_cannot_access_other_candidate_request\|backoffice_can_create_and_assign_rgpd_request_to_existing_user'` | OK: 3 testes, 28 asserções |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-05-final-v2.txt` | Falhou com 2706 erros remanescentes |
| `./vendor/bin/pint --test` | OK final |
| `php artisan route:list --except-vendor` | OK: 1083 rotas |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK final: 283 testes, 1775 asserções |

## 8. Ficheiros Criados

- `docs/qa/phpstan-05-security-rgpd-policies-audit-hardening-report.md`
- `storage/phpstan/phpstan-05-before.txt`
- `storage/phpstan/phpstan-05-after-rgpd.txt`
- `storage/phpstan/phpstan-05-after-rgpd-full.txt`
- `storage/phpstan/phpstan-05-final.txt`
- `storage/phpstan/phpstan-05-final-v2.txt`
- `storage/phpstan/phpstan-05-target-files.txt`
- `storage/phpstan/phpstan-05-target-files-v2.txt`
- `storage/phpstan/phpstan-05-target-files-v3.txt`
- `storage/phpstan/phpstan-05-target-files-v4.txt`

## 9. Ficheiros Alterados

- `app/Http/Controllers/Backoffice/Security/PrivacyController.php`
- `app/Models/DataExportPackage.php`
- `app/Models/DataSubjectRequest.php`
- `app/Models/User.php`
- `app/Models/UserConsent.php`
- `app/Services/Rgpd/DataExportService.php`
- `app/Services/Rgpd/DataInventoryService.php`
- `app/Services/Rgpd/DataSubjectRequestService.php`
- `app/Services/Rgpd/UserConsentService.php`
- `app/Services/Security/MfaDeviceService.php`
- `tests/Feature/Sprint18RgpdSecurityAuditTest.php`

## 10. Migrations, Seeders e Funcionalidade

- Migrations criadas: nenhuma.
- Seeders alterados: nenhum.
- Controllers funcionais alterados: apenas `Backoffice\Security\PrivacyController`, com tipagem e helper de resolução de utilizador.
- Policies alteradas: nenhuma.
- Permissões alteradas: nenhuma.
- Regras de domínio alteradas: nenhuma.
- Frontend alterado: não.

## 11. Riscos Residuais

- PHPStan mantém 2706 erros legados fora do lote corrigido.
- Persistem erros em policies e domínios críticos que exigem sprints dedicadas com testes próprios.
- `User.php` continua com múltiplas relações sem generics fora do subconjunto RGPD tocado.
- Existem erros remanescentes em `SecurityAlertService`, `AccessLogService`, retention RGPD, reporting, documents, contracts/finance e allocation.
- A análise estática global ainda não pode ser usada como quality gate bloqueante sem baseline ou plano progressivo.

## 12. Recomendação

Avançar para PHPSTAN-06 com foco em performance, reports, exports e dashboards, ou executar uma sub-sprint intermédia PHPSTAN-05B dedicada a policies críticas se a prioridade imediata for endurecimento de autorização.
