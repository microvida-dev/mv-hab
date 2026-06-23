# SPRINT PHPSTAN-18 — Functional Residual Risk Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23  
**Âmbito:** Erros residuais de maior risco funcional após PHPSTAN-17.

---

## 1. Objetivo

Executar uma sprint dedicada aos **96 erros PHPStan residuais** que exigem revisão funcional cuidadosa, priorizando:

1. `method.nonObject`
2. `property.nonObject`
3. comparações impossíveis com enums/estados
4. `deadCode.unreachable`
5. `argument.type` em pontos de domínio

Esta sprint não é uma limpeza mecânica. É uma sprint de **hardening funcional**, orientada à descoberta de bugs reais, estados impossíveis e fragilidade em workflows críticos.

---

## 2. Estado de Partida

Após PHPSTAN-17:

| Métrica | Valor |
| --- | ---: |
| Erros PHPStan wrapper | 96 |
| Assinaturas normalizadas | 85 |
| Ficheiros afetados | 42 |
| Erros novos | 0 |
| PHPUnit direto | OK — 283 testes / 1775 asserções |
| Pint | OK |
| Route list | OK — 1083 rotas |

Distribuição residual:

| Identificador | Quantidade |
| --- | ---: |
| `method.nonObject` | 18 |
| `nullsafe.neverNull` | 18 |
| `property.nonObject` | 11 |
| `argument.type` | 9 |
| `identical.alwaysFalse` | 8 |
| `notIdentical.alwaysTrue` | 7 |
| `deadCode.unreachable` | 5 |
| `function.impossibleType` | 3 |
| `booleanAnd.alwaysFalse` | 2 |
| `property.notFound` | 2 |

---

## 3. Regras Absolutas

Não alterar sem teste dirigido:

- elegibilidade;
- pontuação;
- ranking;
- concursos;
- sorteios;
- contratos;
- rendas;
- pagamentos;
- RGPD;
- auditoria;
- policies;
- permissões;
- workflows administrativos.

Proibido:

- baseline PHPStan;
- `ignoreErrors`;
- `@phpstan-ignore-line`;
- `@phpstan-ignore-next-line`;
- `mixed` para ocultar erro;
- alterações oportunistas de regras de negócio.

Obrigatório:

```text
exact_new = 0
```

---

## 4. Fase 1 — Baseline Funcional

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-18-before.txt

php scripts/phpstan-count-errors.php storage/phpstan/phpstan-18-before.txt
```

Gerar tabela com:

- ficheiro;
- linha;
- identificador;
- domínio;
- risco;
- teste obrigatório;
- decisão: corrigir / adiar / documentar.

---

## 5. Fase 2 — `method.nonObject`

Eliminar chamadas a métodos em valores que podem ser `null` ou tipos não objeto.

Para cada ocorrência:

1. Confirmar se a relação é obrigatória por schema/workflow.
2. Confirmar se já existe teste.
3. Adicionar guard explícito ou assert legítimo.
4. Nunca assumir relação obrigatória sem prova.

Testes dirigidos:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Maintenance
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Administrative
```

---

## 6. Fase 3 — `property.nonObject`

Eliminar acessos a propriedades em valores possivelmente nulos ou mal inferidos.

Regras:

- Se o objeto for obrigatório: usar `firstOrFail()`, `findOrFail()` ou guard explícito, conforme comportamento existente.
- Se o objeto for opcional: tratar ausência sem 500.
- Se a ausência indicar corrupção processual: lançar exceção de domínio clara.

Proibido:

- transformar tudo em nullsafe `?->` sem compreender impacto;
- retornar `null` silenciosamente em fluxos críticos;
- esconder erro com cast artificial.

---

## 7. Fase 4 — Comparações Impossíveis

Alvos:

- `identical.alwaysFalse`
- `notIdentical.alwaysTrue`
- `booleanAnd.alwaysFalse`
- `function.impossibleType`

Objetivo:

- identificar enum/string mismatch;
- casts em falta;
- branches mortos;
- estados impossíveis;
- bugs reais em workflows.

Só corrigir se houver prova por:

- cast no model;
- enum existente;
- teste dirigido;
- workflow documentado.

---

## 8. Fase 5 — Dead Code

Alvo:

```text
deadCode.unreachable
```

Separar:

- código realmente morto;
- código defensivo;
- branch impossível por enum/cast;
- fluxo legado.

Não remover código sem teste dirigido.

---

## 9. Fase 6 — `argument.type`

Corrigir incompatibilidades de argumento em services/controllers residuais.

Permitido:

- estreitar tipo antes da chamada;
- converter ID validado para inteiro;
- usar `findOrFail()` se o comportamento já era abortar;
- tipar collections/arrays.

Proibido:

- alargar assinatura para aceitar `mixed`;
- alterar contrato de service sem validação;
- alterar comportamento HTTP sem teste.

---

## 10. Validação por Lote

Após cada fase:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-18-after-<fase>.txt

php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-18-before.txt storage/phpstan/phpstan-18-after-<fase>.txt

./vendor/bin/pint --test
```

Fases:

- `method-non-object`
- `property-non-object`
- `impossible-comparisons`
- `dead-code`
- `argument-type`

---

## 11. Validação Final

Executar:

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml

php artisan route:list --except-vendor

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-18-final.txt

php scripts/phpstan-baseline-compare.php storage/phpstan/phpstan-18-before.txt storage/phpstan/phpstan-18-final.txt
```

---

## 12. Artefactos

Criar:

```text
docs/qa/phpstan-18-functional-residual-risk-hardening-report.md

storage/phpstan/phpstan-18-before.txt
storage/phpstan/phpstan-18-after-method-non-object.txt
storage/phpstan/phpstan-18-after-property-non-object.txt
storage/phpstan/phpstan-18-after-impossible-comparisons.txt
storage/phpstan/phpstan-18-after-dead-code.txt
storage/phpstan/phpstan-18-after-argument-type.txt
storage/phpstan/phpstan-18-final.txt
storage/phpstan/phpstan-18-summary.txt
storage/phpstan/phpstan-18-phpunit.txt
storage/phpstan/phpstan-18-pint.txt
storage/phpstan/phpstan-18-route-list.txt
```

---

## 13. Relatório Final Obrigatório

O relatório deve conter:

- resumo executivo;
- métricas antes/depois;
- erros corrigidos por identificador;
- erros corrigidos por domínio;
- bugs reais encontrados;
- falsos positivos confirmados;
- código morto removido ou adiado;
- testes executados;
- riscos residuais;
- recomendação para PHPSTAN-19.

Classificação obrigatória:

| Código | Tipo |
| --- | --- |
| BR | Bug real |
| FP | Falso positivo |
| DT | Dívida técnica |
| RF | Risco funcional |
| TS | Tipagem segura |
| CD | Código morto |
| DF | Código defensivo |

---

## 14. Meta da Sprint

### Mínimo

```text
96 → <70 erros wrapper
```

### Esperado

```text
96 → <50 erros wrapper
```

### Excelente

```text
96 → <25 erros wrapper
```

Sem regressões funcionais e com `exact_new = 0`.

---

## 15. Critério de Fecho

A sprint só pode ser encerrada se:

- PHPUnit direto passar;
- Pint passar;
- Route list passar;
- PHPStan final for gerado;
- `exact_new = 0`;
- todos os erros de maior risco forem corrigidos ou documentados;
- nenhuma regra funcional for alterada sem teste;
- relatório final for criado.

---

## 16. Próxima Sprint Prevista

## PHPSTAN-19 — Final Static Analysis Closure & CI Lockdown

Foco:

- últimos erros de baixo risco;
- limpeza final de nullsafe/return types;
- CI/CD com quality gate obrigatório;
- preparação para política “sem novos erros PHPStan”;
- eventual subida controlada para PHPStan mais estrito.
