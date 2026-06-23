# SPRINT PHPSTAN-03 — RETURN TYPES, ARRAYS, COLLECTIONS, NULLABILITY E TRIAGEM DE BUGS REAIS

Data de preparação: 2026-06-23  
Base documental:
- `phpstan-01-enterprise-audit-report.md`
- `phpstan-02-eloquent-generics-remediation-report.md`
- `deep-research-report.md`

---

## 1. Objetivo

Executar uma remediação controlada dos erros PHPStan remanescentes após a Sprint PHPSTAN-02, com foco prioritário em:

- `missingType.iterableValue`
- `missingType.return`
- `missingType.parameter`
- `argument.templateType`
- `method.unresolvableReturnType`
- `return.type` de baixo risco
- `nullsafe.neverNull` de baixo risco
- arrays e collections sem tipagem explícita
- Form Requests com `rules()` não tipado
- Form Requests com `authorize()` a devolver `bool|null`

Este sprint deve reduzir ruído estático sem tocar em regras de negócio.  
A descoberta de bugs reais deve ser documentada, não corrigida automaticamente.

---

## 2. Contexto Atual

A auditoria PHPSTAN-01 inventariou:

| Métrica | Valor |
| --- | ---: |
| Total inicial de erros PHPStan | 2897 |
| Ficheiros afetados inicialmente | 561 |
| Nível PHPStan | 8 |

A Sprint PHPSTAN-02 reduziu:

| Métrica | Antes | Depois | Diferença |
| --- | ---: | ---: | ---: |
| Total PHPStan | 2897 | 2828 | -69 |
| `missingType.generics` | 1160 | 1093 | -67 |
| Ficheiros afetados | 561 | 529 | -32 |

A Sprint PHPSTAN-02 confirmou que as correções devem continuar por ondas pequenas, porque a tipagem de relações em modelos core pode fazer emergir erros fora do alvo.

---

## 3. Âmbito Prioritário do Sprint

### 3.1 Corrigir nesta sprint

Corrigir apenas erros de baixo risco, sobretudo em:

- Form Requests
- Seeders
- Factories remanescentes simples
- Helpers simples
- Services sem lógica crítica
- Services de reporting/indicators de baixo risco
- Public Portal de baixo risco
- DTOs, arrays e collections de suporte

### 3.2 Não corrigir nesta sprint

Ficam fora de âmbito:

- `method.notFound`
- `property.nonObject`
- `method.nonObject`
- `property.notFound` em modelos ou relações core
- `booleanAnd.alwaysFalse`
- `booleanOr.alwaysTrue`
- `identical.alwaysFalse`
- `identical.alwaysTrue`
- `match.alwaysFalse`
- `function.impossibleType`
- `deadCode.unreachable`
- `offsetAccess.nonOffsetAccessible`
- qualquer erro em RGPD, Security, Audit, Eligibility, Scoring, Allocation, Contracts ou Finance sem teste dirigido

Estes erros devem ser analisados e inventariados para a Sprint PHPSTAN-04, mas não corrigidos automaticamente.

---

## 4. Regras Absolutas

Não alterar:

- algoritmos de elegibilidade;
- algoritmos de pontuação;
- critérios de classificação;
- regras dos concursos;
- workflows de candidatura;
- workflows de audiência prévia;
- workflows de listas provisórias/definitivas;
- workflows de atribuição;
- contratos;
- rendas;
- permissões;
- policies;
- auditoria;
- RGPD;
- segurança;
- migrations;
- seeders com impacto funcional;
- queries de negócio;
- estrutura de base de dados.

Não usar:

- `@phpstan-ignore`;
- baseline para esconder dívida;
- widening artificial de tipos;
- `mixed` como solução genérica sem justificação;
- remoção de validações;
- remoção de código defensivo sem teste dirigido.

---

## 5. Prioridade Técnica

### P1 — Form Requests

Corrigir `rules()` sem tipo de retorno de array.

Padrão recomendado:

```php
/**
 * @return array<string, mixed>
 */
public function rules(): array
{
    return [
        // ...
    ];
}
```

Quando possível, usar shape mais preciso:

```php
/**
 * @return array<string, string|array<int, string>>
 */
public function rules(): array
{
    return [
        'status' => ['required', 'string'],
    ];
}
```

Corrigir `authorize()` que devolve `bool|null`.

Antes:

```php
public function authorize(): bool
{
    return $this->user()?->can('action');
}
```

Depois:

```php
public function authorize(): bool
{
    return $this->user()?->can('action') === true;
}
```

ou:

```php
public function authorize(): bool
{
    $user = $this->user();

    return $user !== null && $user->can('action');
}
```

---

### P2 — Arrays e Collections em Services de baixo risco

Corrigir métodos com arrays sem tipo:

Antes:

```php
public function summary(): array
{
    return [];
}
```

Depois:

```php
/**
 * @return array<string, mixed>
 */
public function summary(): array
{
    return [];
}
```

Collections:

Antes:

```php
public function group(Collection $items): Collection
```

Depois:

```php
/**
 * @param Collection<int, Application> $items
 * @return Collection<string, Collection<int, Application>>
 */
public function group(Collection $items): Collection
```

---

### P3 — `argument.templateType`

Corrigir chamadas a `collect()` quando o PHPStan não consegue inferir `TKey` e `TValue`.

Antes:

```php
$items = collect($data);
```

Depois:

```php
/** @var Collection<int, array<string, mixed>> $items */
$items = collect($data);
```

ou preferencialmente tipar a origem:

```php
/**
 * @param array<int, array<string, mixed>> $data
 */
public function handle(array $data): void
{
    $items = collect($data);
}
```

---

### P4 — `return.type` simples

Corrigir apenas quando a alteração for inequivocamente de tipagem.

Exemplo `int|null` vs `float|null`:

```php
public function remainingDays(): ?int
{
    $days = now()->diffInDays($this->deadline, false);

    return is_float($days) ? (int) $days : $days;
}
```

Não aplicar casts em domínios críticos sem teste dirigido.

---

### P5 — `nullsafe.neverNull` de baixo risco

Corrigir apenas quando o objeto é comprovadamente não nulo pelo contrato do método.

Antes:

```php
$user?->id
```

Depois:

```php
$user->id
```

Não corrigir em RGPD, Security, Audit ou Policies neste sprint sem teste dirigido.

---

## 6. Categorias de Trabalho

### Grupo A — Correção permitida

Pode corrigir nesta sprint:

| Identificador | Condição |
| --- | --- |
| `missingType.iterableValue` | Form Requests, helpers, indicators e services simples |
| `missingType.return` | métodos simples sem impacto de domínio |
| `missingType.parameter` | parâmetros array de baixo risco |
| `argument.templateType` | chamadas `collect()` com origem clara |
| `method.unresolvableReturnType` | apenas quando resolvido por PHPDoc local |
| `return.type` | apenas retorno simples e comprovável |
| `nullsafe.neverNull` | apenas fora de domínios sensíveis |

### Grupo B — Só inventariar

Não corrigir automaticamente:

| Identificador | Motivo |
| --- | --- |
| `method.nonObject` | pode esconder bug real |
| `property.nonObject` | pode esconder null-safety quebrada |
| `property.notFound` | pode indicar relação/modelo mal tipado |
| `method.notFound` | pode indicar scope em falta ou bug real |
| `booleanAnd.alwaysFalse` | pode indicar lógica impossível |
| `identical.alwaysFalse` | pode indicar enum/string mismatch |
| `match.alwaysFalse` | pode indicar bug em enum |
| `deadCode.unreachable` | pode indicar fluxo nunca executado |
| `offsetAccess.nonOffsetAccessible` | pode indicar parsing inseguro |

---

## 7. Plano de Execução

### Fase 1 — Preparação

Executar:

```bash
php artisan optimize:clear
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-before.txt
```

Extrair métricas:

- total de erros;
- erros por identificador;
- erros por ficheiro;
- erros por domínio.

---

### Fase 2 — Lote 1: Form Requests

Corrigir:

- `rules()` sem PHPDoc;
- `authorize()` que devolve `bool|null`;
- arrays simples em request classes.

Validação após lote:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote1-requests.txt
vendor/bin/pint --test
```

Critério de bloqueio:

- se surgirem novos erros fora do alvo, reverter lote;
- se houver alteração de autorização com impacto permissivo, reverter lote.

---

### Fase 3 — Lote 2: Arrays e Collections de baixo risco

Corrigir:

- services auxiliares sem domínio crítico;
- indicators/report helpers simples;
- public portal services simples;
- métodos `summary()`, `filters()`, `options()`, `payload()` sem lógica de decisão.

Validação:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote2-arrays-collections.txt
vendor/bin/pint --test
```

---

### Fase 4 — Lote 3: `argument.templateType`

Corrigir:

- chamadas `collect()` com arrays claramente tipáveis;
- callbacks `map()`, `filter()`, `values()` com PHPDoc local;
- collections simples usadas para apresentação, indicadores ou payloads.

Validação:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote3-template-type.txt
vendor/bin/pint --test
```

---

### Fase 5 — Lote 4: `return.type` e `nullsafe.neverNull` seguros

Corrigir apenas casos fora de domínios sensíveis.

Validação:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-after-lote4-return-nullsafe.txt
vendor/bin/pint --test
```

---

### Fase 6 — Inventário de bugs reais prováveis

Criar relatório com todos os erros de alto risco encontrados, sem corrigir automaticamente.

O relatório deve agrupar por:

- `method.notFound`;
- `property.nonObject`;
- `method.nonObject`;
- `property.notFound`;
- condições impossíveis;
- enum/string mismatch;
- dead code;
- offsets inseguros.

Classificação obrigatória:

| Código | Significado |
| --- | --- |
| FP | Falso positivo provável |
| CD | Código morto provável |
| BR | Bug real provável |
| DL | Dívida técnica |
| PD | Programação defensiva |
| CR | Correção requer teste dirigido |

---

## 8. Domínios Sensíveis Bloqueados

Não corrigir nesta sprint sem autorização expressa e teste dirigido:

| Domínio | Motivo |
| --- | --- |
| RGPD | risco legal e exposição de dados |
| Security | risco de autorização e MFA |
| Audit | risco de rastreabilidade |
| Eligibility | risco de exclusão indevida |
| Scoring | risco de classificação incorreta |
| Allocation | risco de atribuição indevida |
| Contracts | risco jurídico |
| Finance/Rents | risco financeiro |
| Documents privados | risco de dados pessoais |
| Policies | risco de alargar permissões |
| AdministrativeProcess core | Sprint 02 mostrou que tipagem pode expor erros fora do alvo |

---

## 9. Testes Obrigatórios

### Durante a sprint

Após cada lote:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v
vendor/bin/pint --test
```

### No final

Executar:

```bash
vendor/bin/phpstan analyse --memory-limit=1G -v > storage/phpstan/phpstan-03-final.txt
vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Nota operacional: no ambiente da Sprint PHPSTAN-02, `php artisan test` não foi fiável por limite efetivo de memória de 128 MB. O comando validado foi:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Executar `npm run build` apenas se houver alteração frontend, o que não é esperado nesta sprint.

---

## 10. Testes de Regressão Dirigidos

Mesmo sem alteração de lógica, validar que a suite cobre pelo menos:

### Candidaturas

- criação;
- submissão;
- desistência;
- dashboard do candidato.

### Documentos

- upload;
- substituição;
- validação;
- rejeição;
- pedidos de aperfeiçoamento.

### Elegibilidade

- simulação;
- cálculo persistido;
- impedimentos.

### Pontuação

- cálculo;
- ranking;
- listas.

### Backoffice

- consulta de candidatura;
- análise documental;
- emissão de lista;
- notificações.

### Contratos e Inquilino

- criação;
- consulta;
- renda/fatura;
- manutenção.

Se não existir cobertura suficiente, não criar alterações de domínio para “aproveitar” a sprint. Apenas documentar lacuna.

---

## 11. Entregáveis

Criar relatório:

```text
docs/qa/phpstan-03-return-types-nullability-remediation-report.md
```

O relatório deve conter:

1. Resumo executivo.
2. Métricas antes/depois.
3. Erros corrigidos por identificador.
4. Ficheiros alterados.
5. Lotes executados.
6. Alterações revertidas, se existirem.
7. Bugs reais prováveis encontrados.
8. Código morto provável.
9. Falsos positivos prováveis.
10. Correções adiadas.
11. Riscos residuais.
12. Comandos executados.
13. Resultado do PHPStan.
14. Resultado do Pint.
15. Resultado dos testes.
16. Recomendação para Sprint PHPSTAN-04.

---

## 12. Métricas Esperadas

Objetivo razoável:

| Métrica | Antes esperado | Depois esperado |
| --- | ---: | ---: |
| Total PHPStan | 2828 | reduzir sem novos identificadores |
| `missingType.iterableValue` | 364 | redução significativa |
| `return.type` | 72 | redução parcial |
| `missingType.return` | 13 | redução parcial |
| `missingType.parameter` | 9 | redução parcial |
| `argument.templateType` | 15 | redução parcial |
| `method.unresolvableReturnType` | 9 | redução parcial |

Não usar a redução total como único critério.  
A prioridade é reduzir ruído sem criar regressões nem mascarar bugs.

---

## 13. Critérios de Bloqueio

Parar e reverter lote se:

- PHPStan aumentar o total global;
- surgirem novos erros fora do alvo;
- Pint falhar após correção;
- testes falharem por motivo relacionado com o lote;
- for necessário alterar regra de negócio;
- for necessário alterar query funcional;
- for necessário alterar policy;
- for necessário alterar migration;
- houver dúvida sobre permissões, RGPD ou auditoria;
- a correção exigir interpretação jurídica ou regulamentar.

---

## 14. Critério de Sucesso

A sprint é considerada concluída apenas se:

- houver redução mensurável de erros de baixo risco;
- não existirem regressões funcionais;
- `vendor/bin/pint --test` passar;
- a suite PHPUnit passar com memória adequada;
- PHPStan não introduzir novos identificadores;
- nenhuma regra de negócio for modificada;
- todos os erros de alto risco forem documentados;
- o relatório final for criado em `docs/qa`;
- for produzida recomendação clara para PHPSTAN-04.

---

## 15. Recomendação para PHPSTAN-04

A Sprint PHPSTAN-04 deve focar exclusivamente bugs reais prováveis e lógica de domínio:

- `method.notFound`;
- `property.nonObject`;
- `method.nonObject`;
- enum/string mismatch;
- estados impossíveis;
- `deadCode.unreachable`;
- `offsetAccess.nonOffsetAccessible`;
- `match.alwaysFalse`;
- `booleanAnd.alwaysFalse`;
- `identical.alwaysFalse`.

Cada correção da Sprint 04 deve ter teste dirigido, sobretudo em:

- RGPD;
- Security;
- Audit;
- Eligibility;
- Scoring;
- Allocation;
- Contracts;
- Finance/Rents;
- Documents;
- Policies.
