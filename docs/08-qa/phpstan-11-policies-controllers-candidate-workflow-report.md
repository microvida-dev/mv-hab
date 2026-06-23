# Relatório PHPSTAN-11 — Policies, Controllers & Candidate Workflow Hardening

Data: 2026-06-23  
Âmbito: Policies, controllers de candidato, agregado/rendimentos, documentos privados e workflows de candidato.

## Resumo executivo

A sprint reduziu o total PHPStan de 1317 para 1147 erros globais.

Resultado:

- Redução total: 170 erros.
- Critério mínimo: cumprido (`-150`).
- Erros novos exatos face à baseline da sprint: 0.
- Baseline/ignoreErrors/phpstan-ignore: não alterados.
- Regras de negócio de elegibilidade, pontuação, ranking, listas, contratos, rendas, reclamações, notificações legais e sorteios: não alteradas.

O PHPStan continua a falhar por dívida técnica legada remanescente, mas não houve aumento de erros nem regressão detetada nos testes executados.

## Métricas PHPStan

| Artefacto | Erros | Ficheiros |
| --- | ---: | ---: |
| `storage/phpstan/phpstan-11-before.txt` | 1317 | 344 |
| `storage/phpstan/phpstan-11-after-policies.txt` | 1282 | 327 |
| `storage/phpstan/phpstan-11-after-household.txt` | 1235 | 320 |
| `storage/phpstan/phpstan-11-after-documents.txt` | 1177 | 314 |
| `storage/phpstan/phpstan-11-after-controllers.txt` | 1147 | 307 |
| `storage/phpstan/phpstan-11-final.txt` | 1147 | 307 |

Comparação final:

- `exact_new`: 0.
- `exact_removed`: 166.

## Alterações realizadas

### Policies

Foram adicionados guards explícitos, validação de relações e normalização de leitura de enums/atributos sem expandir permissões.

Ficheiros:

- `app/Policies/AdministrativeDecisionPolicy.php`
- `app/Policies/CommunicationReceiptPolicy.php`
- `app/Policies/ComplaintDecisionPolicy.php`
- `app/Policies/ComplaintPolicy.php`
- `app/Policies/DefinitiveListPolicy.php`
- `app/Policies/ProvisionalListPolicy.php`
- `app/Policies/HouseholdMemberPolicy.php`
- `app/Policies/HouseholdPolicy.php`
- `app/Policies/IncomeRecordPolicy.php`
- `app/Policies/IncomeChangeDeclarationPolicy.php`
- `app/Policies/LeaseContractDocumentPolicy.php`
- `app/Policies/ProcessTimelineEventPolicy.php`
- `app/Policies/MaintenanceAttachmentPolicy.php`
- `app/Policies/MaintenanceInterventionPolicy.php`
- `app/Policies/PropertyHistoryEventPolicy.php`
- `app/Policies/PropertyInspectionPolicy.php`
- `app/Policies/MaintenanceRequestPolicy.php`

### Household, rendimentos e modelos documentais

Foram adicionados generics Eloquent em relações e removidas ambiguidades de nullability.

Ficheiros:

- `app/Models/Household.php`
- `app/Models/HouseholdMember.php`
- `app/Models/IncomeRecord.php`
- `app/Models/IncomeSource.php`
- `app/Models/Document.php`
- `app/Models/DocumentAccessLog.php`
- `app/Models/DocumentDossier.php`
- `app/Models/DocumentDossierItem.php`

### Services

Foram adicionados guards conservadores para relações obrigatórias, normalização de arrays de entrada e retorno seguro quando `fresh()` devolve `null`.

Ficheiros:

- `app/Services/Candidate/HouseholdService.php`
- `app/Services/Candidate/HouseholdMemberService.php`
- `app/Services/Candidate/IncomeService.php`
- `app/Services/DocumentIntelligence/CandidateDeclaredDataResolver.php`
- `app/Services/DocumentStandardization/DocumentDossierExportService.php`

### Controllers de candidato

Foram removidos guards tautológicos, adicionados casts escalares antes de `findOrFail()` e tipadas coleções de checklist/timeline.

Ficheiros:

- `app/Http/Controllers/Candidate/CurrentHousingSituationController.php`
- `app/Http/Controllers/Candidate/DocumentController.php`
- `app/Http/Controllers/Candidate/FutureApplicationDataReuseController.php`
- `app/Http/Controllers/Candidate/HouseholdController.php`
- `app/Http/Controllers/Candidate/IncomeRecordController.php`
- `app/Http/Controllers/Candidate/ProcessDashboardController.php`
- `app/Http/Controllers/Candidate/ProcessTimelineController.php`
- `app/Http/Controllers/Candidate/PublishedListController.php`

## Validação executada

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit` | OK: 283 testes, 1775 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Candidate` | OK: 76 testes, 513 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Household` | OK: 12 testes, 104 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Document` | OK: 92 testes, 581 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Application` | OK: 23 testes, 166 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --filter Policy` | Sem testes encontrados; exit code 1 sem falha funcional |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada por erros legados: 1147 erros remanescentes |

Artefactos:

- `storage/phpstan/phpstan-11-optimize-clear-final.txt`
- `storage/phpstan/phpstan-11-pint-final.txt`
- `storage/phpstan/phpstan-11-phpunit.txt`
- `storage/phpstan/phpstan-11-directed-tests.txt`
- `storage/phpstan/phpstan-11-final.txt`

## Riscos controlados

- Policies foram endurecidas sem alargar permissões.
- Controllers mantiveram as mesmas autorizações `Gate::authorize`.
- Documentos continuam a passar por services existentes de upload, acesso e auditoria.
- Candidato continua limitado aos seus próprios dados por queries existentes e policies.
- Não foram alteradas migrations, seeders, `.env`, configuração PHPStan ou baseline.

## Riscos residuais

- Existem 1147 erros PHPStan legados em 307 ficheiros.
- O filtro PHPUnit `Policy` não encontra testes dedicados; a cobertura de policies depende de testes funcionais existentes.
- Persistem erros em domínios fora do foco desta sprint, nomeadamente finance/rents, tenant area, maintenance, reports e workflows posteriores.

## Recomendação

Avançar para PHPSTAN-12 com foco em Finance, Tenant Area & Maintenance Hardening.

Antes dessa sprint, recomenda-se criar testes explícitos para policies críticas, porque o filtro `Policy` não encontrou testes dedicados nesta execução.
