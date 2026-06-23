# Bug Fix Report

Data: 16/06/2026.

Não foram detetados bugs `Critical` ou `High` de produção durante a Sprint 19. Foram corrigidos problemas encontrados nos novos artefactos de QA antes da suite Sprint 19 ficar verde.

| ID | Severidade | Módulo | Descrição | Como reproduzir | Correção aplicada | Ficheiros alterados | Teste criado | Estado |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| S19-QA-001 | Low | Testes unitários | Factories em Unit tentavam persistir relações sem migrations carregadas | Executar novos Unit de elegibilidade/pontuação isolados | Usar models em memória e relação `rules` vazia | `tests/Unit/Eligibility/EligibilityCalculationDeterministicTest.php`, `tests/Unit/Scoring/ScoringCalculationDeterministicTest.php` | Sim | Closed |
| S19-QA-002 | Low | Seeder de teste | Seeder integrado usava coluna inexistente `complaints.decided_at` | Executar `IntegratedWorkflowTestSeeder` em SQLite | Usar `review_completed_at`, coluna existente | `database/seeders/Testing/IntegratedWorkflowTestSeeder.php` | Sim | Closed |
| S19-QA-003 | Low | Teste de renda | Expectativa assumia renda nula com rendimento zero, mas regra aplica renda mínima e marca revisão manual | Executar `RentCalculationDeterministicTest` | Alinhar teste ao comportamento real: estado `requires_manual_review`, renda mínima e caução calculada | `tests/Unit/Contracts/RentCalculationDeterministicTest.php` | Sim | Closed |
| S19-QA-004 | Low | Teste documental | Teste procurava download em `audit_logs`, mas Sprint 18 regista evento avançado em `audit_events` | Executar `DocumentSecurityFlowTest` | Validar `audit_events.event_code = document.downloaded` | `tests/Feature/Documents/DocumentSecurityFlowTest.php` | Sim | Closed |

## Bugs críticos abertos

Nenhum bug crítico de produção identificado nesta sprint.

## Riscos não classificados como bug

- Falta CI.
- Falta PHPStan/Psalm.
- Regras jurídicas demo ainda carecem de validação final.
- Testes de carga são básicos e não substituem ensaio volumétrico.
