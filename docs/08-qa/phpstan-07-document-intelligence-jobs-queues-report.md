# Relatório PHPSTAN-07 — Document Intelligence, Jobs, Queues & Integrations Hardening

Data: 2026-06-23

## Objetivo

Remediação incremental e segura de erros PHPStan nos módulos de Document Intelligence, análise/validação documental, uploads documentais, jobs/events/listeners e notificações, sem alterar regras de negócio, workflows administrativos, permissões, migrations ou seeders.

## Resultado Executivo

| Métrica | Antes | Depois | Variação |
|---|---:|---:|---:|
| Erros PHPStan globais | 2513 | 2192 | -321 |
| Ficheiros com erros PHPStan | 452 | 393 | -59 |
| Erros novos exatos | - | 0 | 0 |
| PHPUnit | 283 testes / 1775 asserções | OK | Sem regressão |
| Pint --test | OK | OK | Sem pendências |

O objetivo mínimo da sprint (-100 erros) foi ultrapassado. O objetivo máximo (< 2400 erros globais) foi atingido.

## Âmbito Corrigido

Ficaram sem erros PHPStan no âmbito do sprint:

- `app/Services/DocumentIntelligence/*` alterados nesta sprint.
- `app/Services/Documents/DocumentChecklistService.php`
- `app/Services/Documents/DocumentUploadService.php`
- `app/Services/Documents/DocumentReviewService.php`
- `app/Services/Documents/RequiredDocumentEvaluator.php`
- `app/Services/Documents/OfficialDocumentGenerationService.php`
- `app/Services/Notifications/*` alterados nesta sprint.
- Modelos documentais e de comunicação tipados com generics Eloquent.

## Alterações Técnicas

- Adicionados PHPDoc e generics Eloquent em modelos documentais, templates, comunicação e notificações.
- Normalizadas inferências de relações Eloquent com guards locais e `assert()` apenas onde a relação deve existir.
- Tipados arrays de entrada/saída com `array<string, mixed>` quando são payloads públicos vindos de requests.
- Corrigidas comparações de enums inferidas como strings.
- Removidos guards redundantes em listas já tipadas de DTOs/Models.
- Corrigida null-safety em pontos dependentes de relações opcionais.
- Mantida a defesa runtime em casts/configurações que vêm de base de dados ou config.

## Ficheiros Alterados

- `app/Models/AdhesionRegistration.php`
- `app/Models/CommunicationDelivery.php`
- `app/Models/CommunicationLog.php`
- `app/Models/CurrentHousingSituation.php`
- `app/Models/DocumentAiAnalysis.php`
- `app/Models/DocumentAiField.php`
- `app/Models/DocumentReview.php`
- `app/Models/DocumentSubmission.php`
- `app/Models/DocumentTemplate.php`
- `app/Models/DocumentTemplateVersion.php`
- `app/Models/DocumentType.php`
- `app/Models/NotificationEventRule.php`
- `app/Models/NotificationPreference.php`
- `app/Models/NotificationTemplate.php`
- `app/Models/NotificationTemplateVersion.php`
- `app/Models/OfficialNotification.php`
- `app/Models/RequiredDocument.php`
- `app/Policies/CurrentHousingSituationPolicy.php`
- `app/Services/Candidate/RegistrationProgressService.php`
- `app/Services/DocumentIntelligence/CandidateDeclaredDataResolver.php`
- `app/Services/DocumentIntelligence/DocumentAiPipeline.php`
- `app/Services/DocumentIntelligence/DocumentAiScoreCalculator.php`
- `app/Services/DocumentIntelligence/DocumentExtractionPersister.php`
- `app/Services/DocumentIntelligence/DocumentExtractionResultValidator.php`
- `app/Services/DocumentIntelligence/DocumentExtractionSchemaRegistry.php`
- `app/Services/DocumentIntelligence/DocumentExtractionScorer.php`
- `app/Services/DocumentIntelligence/DocumentKeywordClassifier.php`
- `app/Services/DocumentIntelligence/DocumentQualityAnalyzer.php`
- `app/Services/DocumentIntelligence/DocumentRiskFlagDetector.php`
- `app/Services/DocumentIntelligence/DocumentValidationComparator.php`
- `app/Services/DocumentIntelligence/ExtractedDocumentDataResolver.php`
- `app/Services/Documents/DocumentChecklistService.php`
- `app/Services/Documents/DocumentReviewService.php`
- `app/Services/Documents/DocumentTemplateService.php`
- `app/Services/Documents/DocumentTemplateVersionService.php`
- `app/Services/Documents/DocumentUploadService.php`
- `app/Services/Documents/OfficialDocumentGenerationService.php`
- `app/Services/Documents/RequiredDocumentEvaluator.php`
- `app/Services/Notifications/Channels/EmailChannelService.php`
- `app/Services/Notifications/Channels/InAppChannelService.php`
- `app/Services/Notifications/Channels/PostalChannelService.php`
- `app/Services/Notifications/CommunicationDeliveryService.php`
- `app/Services/Notifications/CommunicationLogService.php`
- `app/Services/Notifications/NotificationCenterService.php`
- `app/Services/Notifications/NotificationEventDispatcher.php`
- `app/Services/Notifications/NotificationEventRuleResolver.php`
- `app/Services/Notifications/NotificationEventRuleService.php`
- `app/Services/Notifications/NotificationPreferenceService.php`
- `app/Services/Notifications/NotificationTemplateResolver.php`
- `app/Services/Notifications/NotificationTemplateService.php`
- `app/Services/Notifications/NotificationTemplateVersionService.php`
- `app/Services/Notifications/OfficialNotificationService.php`
- `app/Services/Notifications/RecipientResolver.php`
- `app/Services/Notifications/TemplateRenderingService.php`

## Ficheiros Criados

- `docs/qa/phpstan-07-document-intelligence-jobs-queues-report.md`
- `storage/phpstan/phpstan-07-before.txt`
- `storage/phpstan/phpstan-07-after-document-intelligence.txt`
- `storage/phpstan/phpstan-07-after-jobs.txt`
- `storage/phpstan/phpstan-07-after-events.txt`
- `storage/phpstan/phpstan-07-after-integrations.txt`
- `storage/phpstan/phpstan-07-final-focused.txt`
- `storage/phpstan/phpstan-07-final-focused-v2.txt`
- `storage/phpstan/phpstan-07-final-focused-v3.txt`
- `storage/phpstan/phpstan-07-final-focused-v4.txt`
- `storage/phpstan/phpstan-07-final.txt`
- `storage/phpstan/phpstan-07-pint.txt`
- `storage/phpstan/phpstan-07-pint-test.txt`
- `storage/phpstan/phpstan-07-pint-test-final.txt`
- `storage/phpstan/phpstan-07-phpunit.txt`
- `storage/phpstan/phpstan-07-phpunit-final.txt`
- `storage/phpstan/phpstan-07-optimize-clear-before.txt`
- `storage/phpstan/phpstan-07-optimize-clear-final.txt`

## Comandos Executados

| Comando | Resultado |
|---|---|
| `php artisan optimize:clear` | OK |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` antes | 2513 erros |
| PHPStan focado no âmbito da sprint | OK no final |
| `./vendor/bin/pint` | OK, aplicou formatação em 4 ficheiros |
| `./vendor/bin/pint --test` | OK no estado final |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK no estado final, 283 testes / 1775 asserções |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` final | 2192 erros legados, 0 novos |

## Riscos e Pendências

- PHPStan global ainda falha por dívida legada: 2192 erros remanescentes.
- Os maiores focos restantes estão em `Application`, `Contract`, `User`, `Scoring`, `Eligibility`, `Contest`, `Program`, `HousingUnit`, `Allocation` e workflows administrativos.
- Não foram alteradas migrations, seeders, permissões, workflows críticos, elegibilidade, pontuação, contratos, rendas, RGPD ou auditoria crítica.
- Não foram instaladas dependências.

## Recomendação

Avançar para a próxima sprint PHPStan com foco em Models Core & Eloquent Relations Hardening, começando por `Application`, `User`, `Contract`, `Contest`, `Program` e `HousingUnit`.
