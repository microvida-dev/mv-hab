# Relatório PHPSTAN-13 — Public Portal, Reports e Integrações

## Resumo executivo

A sprint PHPSTAN-13 foi executada com foco em Portal Público, relatórios/dashboards, timeline processual e Document Intelligence.

Resultado final:

| Métrica | Antes | Depois |
| --- | ---: | ---: |
| Erros PHPStan totais | 872 | 708 |
| Ficheiros com erros | 267 | 233 |
| Erros removidos por assinatura exata | 0 | 163 |
| Erros novos por assinatura exata | 0 | 0 |

Os domínios-alvo diretos ficaram sem erros PHPStan por nome de ficheiro/namespace relevante:

| Domínio-alvo | Erros finais |
| --- | ---: |
| PublicPortal | 0 |
| Report | 0 |
| Dashboard | 0 |
| Timeline | 0 |
| DocumentAi | 0 |

O comando PHPStan continua a devolver código 1 porque permanecem 708 erros legados fora do âmbito desta sprint.

## Âmbito executado

Foram aplicadas correções estáticas de baixo risco:

- PHPDoc generics em relações Eloquent.
- Tipagem de scopes Eloquent.
- Documentação de casts enum/datetime usados por services.
- Remoção de guards redundantes após `firstOrFail`.
- Normalização defensiva de valores enum/datetime em status público.
- Ajuste de query agregada para hidratação base em dashboard.
- PHPDoc de collections de relatórios.

Não foram alteradas regras funcionais de elegibilidade, pontuação, candidaturas, contratos, rendas, RGPD ou auditoria.

## Ficheiros alterados

### Portal público

- `app/Services/PublicPortal/PublicContestService.php`
- `app/Services/PublicPortal/PublicHousingMapService.php`
- `app/Services/PublicPortal/PublicHousingSearchService.php`
- `app/Services/PublicPortal/PublicPortalSeoService.php`
- `app/Services/PublicPortal/PublicPortalSettingsService.php`
- `app/Models/PublicPortalLink.php`

### Relatórios, dashboards e vistorias

- `app/Models/AllocationReport.php`
- `app/Models/BackofficeDashboardSnapshot.php`
- `app/Models/LandlordDashboardSnapshot.php`
- `app/Models/PropertyInspectionReport.php`
- `app/Models/ReportAccessLog.php`
- `app/Models/ReportDefinition.php`
- `app/Models/ReportDownloadLog.php`
- `app/Services/BackofficeDashboard/DashboardMetricAggregator.php`
- `app/Services/Maintenance/PropertyCostReportService.php`

### Timeline, processo administrativo e listas

- `app/Http/Controllers/Backoffice/DocumentAiExtractionController.php`
- `app/Http/Controllers/Candidate/CorrectionResponseController.php`
- `app/Http/Controllers/Candidate/ProcessTimelineController.php`
- `app/Models/AdministrativeProcess.php`
- `app/Models/ApplicationStatusHistory.php`
- `app/Models/Complaint.php`
- `app/Models/CorrectionRequest.php`
- `app/Models/CorrectionResponse.php`
- `app/Models/Hearing.php`
- `app/Models/HearingSubmission.php`
- `app/Models/ListChangeLog.php`
- `app/Models/ListPublication.php`
- `app/Models/ProcessTimelineEvent.php`
- `app/Models/ReserveList.php`
- `app/Models/ReserveListEntry.php`
- `app/Services/Administrative/CorrectionResponseService.php`
- `app/Services/ApplicationActions/CorrectionRequestResponseService.php`
- `app/Services/ApplicationActions/PreliminaryHearingSubmissionService.php`
- `app/Services/ProcessTracking/ApplicationPublicStatusService.php`
- `app/Services/ProcessTracking/ProcessHistoryFormatter.php`

### Apoio ao candidato

- `app/Services/CandidateExperience/CandidateSupportDashboardService.php`

## Artefactos gerados

- `storage/phpstan/phpstan-13-before.txt`
- `storage/phpstan/phpstan-13-after-portal.txt`
- `storage/phpstan/phpstan-13-after-reports.txt`
- `storage/phpstan/phpstan-13-after-document-ai.txt`
- `storage/phpstan/phpstan-13-final.txt`
- `storage/phpstan/phpstan-13-summary.txt`
- `storage/phpstan/phpstan-13-phpunit.txt`
- `storage/phpstan/phpstan-13-directed-tests.txt`
- `storage/phpstan/phpstan-13-pint-final.txt`
- `storage/phpstan/phpstan-13-route-list.txt`

## Comandos executados

| Comando | Resultado |
| --- | --- |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | OK — 283 testes, 1775 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Public` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contest` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Housing` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Report` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Timeline` | OK |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DocumentAi` | OK |
| `php artisan route:list` | OK — 1086 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json` | Falha esperada — 708 erros legados remanescentes |

## Erros PHPStan remanescentes

Principais identificadores ainda presentes:

| Identificador | Quantidade |
| --- | ---: |
| `missingType.generics` | 331 |
| `missingType.iterableValue` | 94 |
| `argument.type` | 46 |
| `nullsafe.neverNull` | 30 |
| `method.nonObject` | 28 |
| `property.notFound` | 28 |
| `property.nonObject` | 24 |
| `return.type` | 21 |
| `notIdentical.alwaysTrue` | 17 |
| `identical.alwaysFalse` | 13 |

Ficheiros com maior concentração residual:

| Ficheiro | Erros |
| --- | ---: |
| `app/Services/Scoring/RankingService.php` | 15 |
| `app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php` | 13 |
| `app/Models/LotteryRun.php` | 12 |
| `app/Models/ApplicationScore.php` | 11 |
| `app/Models/ScoringRuleSet.php` | 10 |
| `app/Services/Contests/ContestService.php` | 10 |
| `app/Services/Eligibility/EligibilityEngine.php` | 10 |
| `app/Models/EligibilityCheck.php` | 9 |
| `app/Services/ProcedureTemplates/TemplateVariableResolver.php` | 9 |
| `app/Services/Scoring/ApplicationScoreService.php` | 9 |
| `app/Services/Scoring/TieBreakerService.php` | 9 |

## Riscos residuais

- PHPStan ainda não está limpo globalmente.
- Os erros remanescentes concentram-se em scoring, elegibilidade, lottery, contests e services de domínio.
- Esses domínios devem ser tratados em sprint dedicada, com testes funcionais específicos, porque podem conter lógica administrativa sensível.

## Recomendação

Avançar para a próxima sprint PHPStan focada em scoring/elegibilidade/lottery apenas com abordagem conservadora:

1. Corrigir primeiro PHPDoc e generics de modelos.
2. Evitar alterações de regras de cálculo.
3. Exigir testes antes e depois em elegibilidade, ranking, scoring e sorteios.
4. Manter a política de zero erros novos por assinatura exata.
