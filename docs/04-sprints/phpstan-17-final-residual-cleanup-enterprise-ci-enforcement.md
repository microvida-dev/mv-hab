# SPRINT PHPSTAN-17 — Final Residual Cleanup & Enterprise CI Enforcement

Objetivo: reduzir os 198 erros PHPStan normalizados remanescentes após a PHPSTAN-16, mantendo exact_new=0, PHPUnit verde, Pint verde e sem alterações funcionais.

## Estado Inicial
- 198 assinaturas PHPStan
- 86 ficheiros com erros
- exact_new = 0
- PHPUnit OK
- Pint OK
- Route List OK

## Prioridades
1. Tenant Billing
2. Relações Polimórficas
3. Nullability e Return Types
4. Services Legados
5. Enterprise CI Enforcement

## Regras
- Sem baseline
- Sem suppressions
- Sem alterações funcionais
- Sem alterar elegibilidade, scoring, concursos, contratos, rendas, RGPD ou auditoria
- exact_new = 0

## Meta
- Mínimo: <150 erros
- Esperado: <100 erros
- Excelente: <50 erros

## Artefactos
- docs/qa/phpstan-17-final-residual-cleanup-report.md
- storage/phpstan/phpstan-17-before.txt
- storage/phpstan/phpstan-17-final.txt
- storage/phpstan/phpstan-17-summary.txt

## Critério de Fecho
- PHPUnit verde
- Pint verde
- Route List verde
- exact_new = 0
- Sem regressões
- Relatório final produzido
