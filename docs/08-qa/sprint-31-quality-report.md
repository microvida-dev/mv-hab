# Relatório de Qualidade — Sprint 31

## Âmbito

Sprint 31 — Score de confiança, indicadores de risco e sugestões de aperfeiçoamento documental.

## Implementado

- Tabelas `document_ai_scores` e `document_ai_suggestions`.
- Extensão de `document_ai_flags` com impacto no score, origem, template e confiança.
- Enums `DocumentAiScoreLabel`, `DocumentAiRiskFlagCode`, `DocumentAiRiskSeverity` e `DocumentAiSuggestionStatus`.
- Pipeline assistivo `DocumentAiAssistantPipeline`.
- Job `CalculateDocumentAiScoreJob`.
- Painel backoffice do Assistente IA.
- Policies, Form Requests, Services, Events, factories, fixtures e testes.

## Testes focados executados

`php -d memory_limit=512M vendor/bin/phpunit tests/Unit/DocumentIntelligence/DocumentAiScoreCalculatorTest.php tests/Unit/DocumentIntelligence/DocumentAiScoreExplainerTest.php tests/Unit/DocumentIntelligence/DocumentRiskFlagDetectorTest.php tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php tests/Unit/DocumentIntelligence/DocumentQualityAnalyzerTest.php tests/Unit/DocumentIntelligence/DocumentSuggestionGeneratorTest.php tests/Unit/DocumentIntelligence/DocumentSuggestionTemplateRegistryTest.php tests/Unit/DocumentIntelligence/DocumentAiAssistantPipelineTest.php tests/Feature/DocumentIntelligence/DocumentAiAssistantIntegrationTest.php tests/Feature/Backoffice/DocumentAiAssistantDashboardTest.php`

Resultado: 14 testes / 91 asserções OK.

## Riscos residuais

- A precisão real depende de dataset municipal anonimizado.
- As heurísticas de documento cortado e documento expirado devem ser calibradas com amostras reais.
- PHPStan global mantém dívida pré-existente quando a análise estática é ignorada por decisão operacional.

## Validação final

| Comando | Resultado |
| --- | --- |
| `php artisan migrate` | OK, migration `2026_06_22_000031_create_document_ai_assistant_tables` aplicada |
| `php artisan route:list` | OK, 1085 rotas |
| `php artisan route:list --name=document-ai.assistant` | OK, 7 rotas do assistente |
| `php artisan test` | Falhou por limite de memória PHP de 128 MB após 161 testes / 956 asserções OK |
| `php -d memory_limit=512M vendor/bin/phpunit` | OK, 278 testes / 1731 asserções |
| `./vendor/bin/pint` | OK |
| `./vendor/bin/pint --test` | OK |
| `npm run build` | OK |
| `php artisan view:cache` | OK |
| `php artisan view:clear` | OK |
| `composer validate` | OK |
| `php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint31-before-publish.json` | Falhou com 2897 erros globais; filtro por ficheiros Sprint 31: 0 ficheiros com erros |

## Interpretação

O bloqueio de `php artisan test` está associado ao limite de memória do processo Artisan, já observado em sprints anteriores. A suíte completa passa quando executada com 512 MB. O PHPStan continua a reportar dívida global pré-existente, sem incidências detetadas nos ficheiros criados para a Sprint 31.
