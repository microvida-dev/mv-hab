# SPRINT PHPSTAN-19 — Final Static Analysis Closure & CI Lockdown

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Fecho dos 14 erros PHPStan residuais, validação final, quality gate CI e preparação para PHPStan zero.

---

## 1. Objetivo

Executar a sprint final de encerramento PHPStan, focada exclusivamente nos **14 erros residuais** remanescentes após a PHPSTAN-18.

A PHPSTAN-18 reduziu a dívida de `96` para `14` erros wrapper, com `new=0`, PHPUnit verde, Pint verde e route list verde.

Esta sprint deve:

- remover os 14 erros finais;
- distinguir `nullsafe.neverNull` realmente redundante de código defensivo útil;
- eliminar limpeza estática residual de baixo risco;
- consolidar quality gate CI;
- preparar a política de PHPStan zero;
- manter regressão funcional zero.

---

## 2. Estado de Partida

Após PHPSTAN-18:

| Métrica | Valor |
| --- | ---: |
| Erros wrapper | 14 |
| Assinaturas normalizadas | 14 |
| Ficheiros afetados | 11 |
| Erros novos | 0 |
| PHPUnit direto | OK — 283 testes / 1775 asserções |
| Pint | OK |
| Route list | OK — 1083 rotas |

Distribuição residual:

| Identificador | Quantidade |
| --- | ---: |
| `nullsafe.neverNull` | 12 |
| `instanceof.alwaysTrue` | 1 |
| `nullCoalesce.offset` | 1 |

---

## 3. Ficheiros Residuais

Atuar apenas sobre estes ficheiros, salvo correção adjacente absolutamente necessária:

```text
app/Services/Applications/ApplicationReceiptService.php
app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php
app/Services/Contracts/ContractPlaceholderService.php
app/Services/DocumentStandardization/DocumentDossierBuilder.php
app/Services/ProcedureMinutes/ProcedureMinuteService.php
app/Services/ProcedureTemplates/GeneratedProcedureDocumentService.php
app/Services/ProcedureTemplates/TemplateRenderingService.php
app/Services/ProcessConfirmations/ProcessConfirmationService.php
app/Services/ProcessConfirmations/ProcessNumberGenerator.php
app/Services/Scoring/RankingSnapshotService.php
app/Services/Scoring/ScoringMessageService.php
```

---

## 4. Regras Absolutas

Não alterar:

- elegibilidade;
- pontuação;
- ranking;
- scoring;
- concursos;
- contratos;
- rendas;
- pagamentos;
- RGPD;
- auditoria;
- policies;
- permissões;
- workflows administrativos;
- placeholders legais;
- minutas;
- templates processuais.

Proibido:

- baseline PHPStan;
- `ignoreErrors`;
- `@phpstan-ignore-line`;
- `@phpstan-ignore-next-line`;
- `mixed` para ocultar erro;
- alterações funcionais oportunistas;
- alteração de migrations, seeders, controllers ou rotas.

Obrigatório:

```text
exact_new = 0
```

---

## 5. Fase 1 — Baseline Final

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-19-before.txt

php scripts/phpstan-count-errors.php storage/phpstan/phpstan-19-before.txt
```

Guardar lista exata dos 14 erros antes de qualquer alteração.

---

## 6. Fase 2 — `nullsafe.neverNull`

### Objetivo

Resolver os 12 erros `nullsafe.neverNull`.

### Estratégia

Para cada ocorrência:

1. Confirmar se o objeto é obrigatoriamente não nulo.
2. Confirmar se há cast/relação/guard anterior que garante não-null.
3. Se for redundante, substituir `?->` por `->`.
4. Se representar defesa contra dados históricos incompletos, não remover sem criar guard explícito.
5. Documentar a decisão no relatório.

### Permitido

```php
$model->relation->property
```

apenas quando a relação ou valor está provadamente presente.

### Preferível em caso defensivo

```php
if (! $model->relation) {
    throw new DomainException('...');
}
```

ou retornar comportamento já esperado pelo fluxo.

---

## 7. Fase 3 — `instanceof.alwaysTrue`

### Objetivo

Resolver a única ocorrência `instanceof.alwaysTrue`.

### Estratégia

- Remover `instanceof` redundante se o tipo já for garantido.
- Manter guard alternativo apenas se existirem dados históricos ou fronteira externa.
- Não substituir por `mixed`.

---

## 8. Fase 4 — `nullCoalesce.offset`

### Objetivo

Resolver a única ocorrência `nullCoalesce.offset`.

### Estratégia

- Confirmar shape do array.
- Se a chave existir sempre, remover `??`.
- Se a chave puder faltar, declarar array shape correto ou usar `array_key_exists`.
- Não mascarar com `mixed`.

---

## 9. Fase 5 — Quality Gate CI Final

Validar e, se necessário, atualizar:

```text
docs/qa/phpstan-quality-gate.md
scripts/phpstan-count-errors.php
scripts/phpstan-baseline-compare.php
```

O quality gate deve passar com:

```text
current_normalized_errors = 0
new = 0
status = passed
```

Se PHPStan ficar a zero, documentar a nova política:

```text
Nenhum PR pode introduzir novo erro PHPStan.
PHPStan deve passar globalmente.
Baseline continua proibida.
Suppressions continuam proibidas.
```

---

## 10. Validação por Lote

Após cada fase:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-19-after-<fase>.txt

php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-19-before.txt storage/phpstan/phpstan-19-after-<fase>.txt

./vendor/bin/pint --test
```

Fases:

```text
nullsafe
instanceof
null-coalesce
ci-gate
```

---

## 11. Validação Final

Executar obrigatoriamente:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-19-final.txt

php scripts/phpstan-count-errors.php storage/phpstan/phpstan-19-final.txt

php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-19-before.txt storage/phpstan/phpstan-19-final.txt
```

Resultado esperado:

```text
PHPUnit OK
Pint OK
Route list OK
PHPStan 0 erros
exact_new = 0
Sem baseline
Sem suppressions
```

---

## 12. Artefactos Obrigatórios

Criar:

```text
docs/qa/phpstan-19-final-static-analysis-closure-report.md

storage/phpstan/phpstan-19-before.txt
storage/phpstan/phpstan-19-after-nullsafe.txt
storage/phpstan/phpstan-19-after-instanceof.txt
storage/phpstan/phpstan-19-after-null-coalesce.txt
storage/phpstan/phpstan-19-after-ci-gate.txt
storage/phpstan/phpstan-19-final.txt
storage/phpstan/phpstan-19-summary.txt
storage/phpstan/phpstan-19-count-final.txt
storage/phpstan/phpstan-19-baseline-compare-final.txt
storage/phpstan/phpstan-19-phpunit.txt
storage/phpstan/phpstan-19-pint.txt
storage/phpstan/phpstan-19-route-list.txt
```

---

## 13. Relatório Final Obrigatório

O relatório deve conter:

## 13.1. Resumo executivo

- total antes;
- total depois;
- erros removidos;
- erros novos;
- estado final PHPStan;
- confirmação de zero suppressions.

## 13.2. Tabela de fecho

| Métrica | Antes | Depois | Variação |
| --- | ---: | ---: | ---: |
| Erros wrapper | 14 | 0 | -14 |
| Assinaturas normalizadas | 14 | 0 | -14 |
| Ficheiros afetados | 11 | 0 | -11 |
| Erros novos | 0 | 0 | 0 |

## 13.3. Erros finais corrigidos

Agrupar por:

- `nullsafe.neverNull`;
- `instanceof.alwaysTrue`;
- `nullCoalesce.offset`.

## 13.4. Decisões defensivas

Para cada nullsafe removido, indicar:

- motivo;
- prova de não-null;
- teste associado.

## 13.5. Testes

Listar:

- comandos;
- resultado;
- testes;
- asserções.

## 13.6. Política CI final

Confirmar:

- PHPStan obrigatório no CI;
- Pint obrigatório;
- PHPUnit obrigatório;
- route list obrigatório;
- baseline proibida;
- suppressions proibidas;
- regressões PHPStan bloqueadas.

---

## 14. Meta da Sprint

### Mínimo

```text
14 → 0 erros PHPStan
```

### Obrigatório

```text
exact_new = 0
```

### Resultado esperado

```text
PHPStan global verde
```

---

## 15. Critério de Fecho

A sprint só pode ser encerrada se:

- PHPStan chegar a zero ou todos os resíduos forem formalmente justificados;
- PHPUnit direto passar;
- Pint passar;
- route list passar;
- `exact_new = 0`;
- não existir baseline;
- não existir suppression;
- relatório final for produzido;
- quality gate CI estiver documentado.

---

## 16. Próxima Sprint Prevista

## PHPSTAN-20 — Enterprise Quality Assurance & Architectural Guardrails

Foco:

- cobertura de testes nos fluxos críticos;
- arquitetura de domínio;
- contracts de services;
- policies de CI/CD;
- mutation testing opcional;
- performance baseline;
- readiness para produção municipal/multi-tenant;
- documentação técnica final.
