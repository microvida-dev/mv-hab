# Critical Flows Test Map

## Objetivo

Mapear cobertura existente e lacunas dos fluxos criticos da plataforma MV HAB apos o fecho PHPStan global.

Este mapa usa a estrutura de testes existente em `tests/Feature` e `tests/Unit` no momento da QA-20.

## Mapa por dominio

| Dominio | Testes existentes | Lacunas | Prioridade |
| --- | --- | --- | --- |
| Candidaturas | `Sprint8ApplicationSubmissionTest`, `ApplicationPrefillTest`, `FullHousingProgramFlowTest` | Cobertura mais profunda de submissao invalida por combinacoes de documentos, snapshots e auditoria por evento. | P0 |
| Elegibilidade | `Sprint7EligibilityEngineTest`, `EligibilityCalculationDeterministicTest`, `CandidateSimulationTest`, `DocumentCandidateValidationPipelineTest` | Mais casos de inelegibilidade por rendimento, tipologia e agregado incompleto com fixtures regulamentares variadas. | P0 |
| Scoring | `Sprint10ScoringRankingTest`, `ScoringCalculationDeterministicTest` | Mais testes de pesos, desempates, exclusao de candidaturas invalidas e estabilidade de ordenacao. | P0 |
| Ranking/listas | `Sprint10ScoringRankingTest`, `Sprint11ListsComplaintsHearingTest`, `LotteryClosureFlowTest` | Regressao especifica para republicacao, snapshots historicos e alteracoes apos reclamacao. | P0 |
| Documentos | `Sprint6DocumentManagementTest`, `DocumentSecurityFlowTest`, testes de Document Intelligence | Mais casos de auditoria de download, substituicao, rejeicao e documentos adicionais sem candidatura. | P0 |
| Contratos | `Sprint13ContractsRentDepositTest`, `RentCalculationDeterministicTest` | Mais cobertura para minuta ausente, path de documento ausente, validacao e historico de versoes. | P1 |
| Rendas/financeiro | `Sprint14FinanceTest`, `Sprint26TenantPostAwardTest` | Mais testes de revisao manual, recibos, regularizacao e conta financeira do inquilino. | P1 |
| RGPD | `Sprint18RgpdSecurityAuditTest`, `SimulatorPrivacyTest` | Mais casos de exportacao, anonimizacao, retencao sem politica e acessos sensiveis. | P0 |
| Auditoria | `AuditLoggerTest`, `AuditEventFormatterTest`, `Sprint18RgpdSecurityAuditTest` | Matriz explicita de eventos obrigatorios por fluxo critico. | P0 |
| Permissoes | `FoundationAccessControlTest`, `PermissionMatrixTest`, policies cobertas indiretamente por features | Mais testes negativos por role em backoffice, candidato, auditor, inquilino e gestor financeiro. | P0 |
| Manutencao | `Sprint15MaintenanceInspectionTest`, `Sprint26TenantPostAwardTest` | Mais cobertura de anexos privados, atribuicao a tecnico/fornecedor e estados de intervencao. | P2 |
| Vistorias | `Sprint15MaintenanceInspectionTest`, `Sprint26TenantPostAwardTest` | Mais testes de relatorios, anexos, permissao e transicao pos-atribuicao. | P2 |
| Portal publico | `Sprint3PortalProgramsTest`, `PublicHousingOfferSprint20Test`, `PublicHousingPresentationSprint32Test`, `AdvancedSimulatorTest` | Mais cobertura SEO/OpenGraph/mapa/filtros e estados sem dados. | P2 |
| Document Intelligence | Testes unitarios e feature em `tests/Unit/DocumentIntelligence` e `tests/Feature/DocumentIntelligence` | Mais casos com documentos reais anonimizados e variacoes de OCR local. | P1 |
| Comunicacoes/notificacoes | `Sprint16CommunicationsTest`, `Sprint23ProcessTrackingTest` | Mais cobertura de entregas falhadas, retry e comprovativos. | P1 |
| Relatorios/dashboards | `Sprint17ReportingDashboardTest`, `BasicLoadSmokeTest` | Mais testes de autorizacao de export, filtros pesados e desempenho de dashboards. | P1 |

## Packs de regressao recomendados

### P0 - antes de staging

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Eligibility
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Scoring
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Document
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Audit
```

### P1 - antes de beta municipal

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Tenant
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Reporting
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Communication
```

### P2 - hardening continuo

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Maintenance
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Public
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Performance
```

## Criterio para QA-21

QA-21 deve expandir testes sem alterar regras funcionais. Ordem recomendada:

1. Permissoes negativas e IDOR.
2. Documentos privados e auditoria de download.
3. Elegibilidade e scoring com fixtures deterministicas.
4. Listas, audiencia e reclamacoes.
5. Contratos, rendas e area do inquilino.
