# SPRINT PHPSTAN-02 — REMEDIAÇÃO SEGURA DE GENERICS ELOQUENT

## 1. Objetivo

Eliminar, de forma controlada e incremental, o maior volume possível de erros PHPStan relacionados com **generics Eloquent**, **relações Laravel**, **builders**, **factories** e **collections tipadas**, sem alterar comportamento funcional da plataforma MV HAB.

Este sprint atua sobre a maior bolsa de dívida técnica identificada no PHPSTAN-01: **`missingType.generics` com 1160 ocorrências**, num universo total de **2897 erros PHPStan em 561 ficheiros**.

O objetivo não é “limpar tudo”. O objetivo é reduzir ruído estático de baixo risco, preservar o domínio funcional e preparar os sprints seguintes para atacar erros realmente perigosos com menos interferência.

---

## 2. Enquadramento

A plataforma MV HAB é um sistema crítico de Habitação Pública, com domínio funcional alargado:

- Portal público
- Registo de adesão
- Candidaturas
- Agregado habitacional
- Documentos privados
- Elegibilidade
- Pontuação
- Listas
- Audiência prévia
- Sorteios
- Contratos
- Rendas
- Manutenção
- Vistorias
- RGPD
- Auditoria
- Segurança
- Document Intelligence local

O relatório estratégico identifica a plataforma como uma base avançada de “sistema operativo municipal de habitação”, mas com dívida técnica ainda elevada. A remediação PHPStan deve, por isso, seguir uma estratégia enterprise: **corrigir por ondas, nunca por massa indiferenciada**.

---

## 3. Dados de Base do PHPSTAN-01

### 3.1 Estado global

| Métrica | Valor |
| --- | ---: |
| Total de erros PHPStan | 2897 |
| Ficheiros afetados | 561 |
| Nível PHPStan | 8 |
| Principal identificador alvo deste sprint | `missingType.generics` |
| Ocorrências `missingType.generics` | 1160 |
| Domínio com maior concentração | Relations |
| Erros em Relations | 1004 |
| Erros em Models | 121 |
| Classe de risco dominante | B — Tipagem segura |

### 3.2 Prioridade deste sprint

Este sprint deve atacar apenas erros de baixo risco da classe:

```text
B — Tipagem segura
```

Inclui:

- relações Eloquent simples;
- scopes tipáveis;
- builders tipáveis;
- factories tipáveis;
- collections de Models;
- PHPDoc de relações sem impacto runtime.

Não inclui:

- `method.notFound`;
- `property.nonObject`;
- `method.nonObject`;
- `identical.alwaysFalse`;
- `booleanAnd.alwaysFalse`;
- erros RGPD;
- erros de segurança;
- erros de elegibilidade;
- erros de pontuação;
- erros de regras de negócio.

---

## 4. Regra Principal

Este sprint só pode alterar **PHPDoc, imports, tipos estáticos e anotações auxiliares**.

Não alterar lógica.

Não alterar queries.

Não alterar condições.

Não alterar regras.

Não alterar permissões.

Não alterar migrations.

Não alterar seeders.

Não alterar controllers, services ou policies, exceto se a alteração for exclusivamente PHPDoc/generic e de baixo risco.

---

## 5. Âmbito Permitido

### 5.1 Permitido

Corrigir:

- `missingType.generics` em Models;
- `missingType.generics` em Relations;
- `missingType.generics` em scopes Eloquent;
- `missingType.generics` em factories;
- `missingType.generics` em collections tipadas;
- PHPDoc de relações simples;
- PHPDoc de builders simples;
- imports necessários apenas para PHPDoc;
- `@use HasFactory<...>` quando seguro;
- `@return BelongsTo<RelatedModel, self>`;
- `@return HasMany<RelatedModel, self>`;
- `@return HasOne<RelatedModel, self>`;
- `@return BelongsToMany<RelatedModel, self>`;
- `@param Builder<self> $query`;
- `@return Builder<self>`.

### 5.2 Não permitido

Não corrigir neste sprint:

- relações polimórficas complexas sem leitura manual;
- `MorphTo` ambíguo;
- `MorphMany` com múltiplos targets;
- `MorphToMany`;
- relações dinâmicas;
- scopes que alterem lógica;
- accessors/mutators com lógica;
- casts/enums;
- comparações impossíveis;
- null safety;
- métodos inexistentes;
- propriedades inexistentes;
- permissões;
- RGPD;
- segurança;
- cálculos;
- workflows.

---

## 6. Estratégia de Execução

### Fase 1 — Preparação

Executar:

```bash
php artisan optimize:clear
vendor/bin/phpstan analyse --memory-limit=1G
```

Guardar estado inicial:

```text
Total de erros antes:
missingType.generics antes:
Ficheiros afetados antes:
```

Não avançar se o projeto não estiver em estado limpo no Git.

Executar:

```bash
git status
```

Confirmar que não existem alterações não relacionadas.

---

### Fase 2 — Seleção de Lote Seguro

Começar por Models de menor risco e alta previsibilidade.

Ordem recomendada:

1. Models simples de auditoria/log;
2. Models de configuração;
3. Models de entidades auxiliares;
4. Models administrativos não críticos;
5. Models de Allocation/Applications apenas se a relação for trivial;
6. Factories;
7. Scopes simples.

Evitar na primeira vaga:

- `Application`
- `Contract`
- `User`
- `Contest`
- `Program`
- `HousingUnit`
- `Allocation`
- Models RGPD
- Models Security
- Models Scoring
- Models Eligibility

Estes têm volume elevado, mas devem ser tratados depois de validar o padrão em ficheiros menos sensíveis.

---

## 7. Padrões de Correção

### BelongsTo

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @return BelongsTo<User, self>
 */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

### HasOne

```php
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @return HasOne<LeaseContract, self>
 */
public function leaseContract(): HasOne
{
    return $this->hasOne(LeaseContract::class);
}
```

### HasMany

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @return HasMany<DocumentSubmission, self>
 */
public function documentSubmissions(): HasMany
{
    return $this->hasMany(DocumentSubmission::class);
}
```

### BelongsToMany

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @return BelongsToMany<Role, self>
 */
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

### HasManyThrough

```php
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @return HasManyThrough<DocumentSubmission, Application, self>
 */
public function documentSubmissions(): HasManyThrough
{
    return $this->hasManyThrough(DocumentSubmission::class, Application::class);
}
```

> Se a assinatura exata do generic for duvidosa, não corrigir e listar no relatório.

---

## 8. Factories

Para Models que usam `HasFactory`, aplicar apenas quando a factory é clara e existente.

```php
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @use HasFactory<UserFactory>
 */
use HasFactory;
```

Regras:

- verificar se a factory existe;
- importar a factory correta;
- não criar factories novas;
- não alterar factories;
- não alterar seeders.

Se a factory não existir, não inventar.

---

## 9. Scopes Simples

Para scopes com `Builder` sem generic:

```php
use Illuminate\Database\Eloquent\Builder;

/**
 * @param Builder<self> $query
 * @return Builder<self>
 */
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}
```

Regras:

- não alterar query;
- não alterar nome do scope;
- não alterar condições;
- não alterar retorno;
- não corrigir scopes com lógica complexa neste sprint.

---

## 10. Relações Polimórficas

Relações polimórficas são de validação manual.

### MorphTo

Só corrigir se o target for inequivocamente conhecido.

Caso contrário, adiar.

```php
/**
 * @return MorphTo<Model, self>
 */
public function resource(): MorphTo
{
    return $this->morphTo();
}
```

Preferir adiar se o Larastan continuar a reportar ambiguidade.

### MorphMany

Só corrigir se o related model for claro:

```php
/**
 * @return MorphMany<AuditEvent, self>
 */
public function auditEvents(): MorphMany
{
    return $this->morphMany(AuditEvent::class, 'auditable');
}
```

Se houver dúvida, adiar.

---

## 11. Lotes Recomendados

### Lote 1 — Models auxiliares e logs

Objetivo: validar padrão com risco reduzido.

Exemplos esperados:

- `AccessLog`
- `AdministrativeProcessStatusHistory`
- `AdministrativeProcessNote`
- `AdministrativeTask`
- `AdditionalInformationRequest`
- `AdditionalInformationResponse`

Critério de saída:

```bash
vendor/bin/phpstan analyse --memory-limit=1G
php artisan test
vendor/bin/pint --test
```

### Lote 2 — Models administrativos

Exemplos:

- `AdministrativeDecision`
- `AdministrativeProcess`
- `AdministrativeWorkflowConfig`
- `CorrectionRequest`
- `CorrectionResponse`

Só corrigir relações simples.

Não corrigir condições impossíveis.

### Lote 3 — Allocation com cautela

Exemplos:

- `Allocation`
- `AllocationOffer`
- `AllocationRun`
- `AllocationRuleSet`

Apenas relações simples.

Não corrigir:

- `readyForContract`
- `eligibleForAllocation`
- `method.notFound`
- estados
- regras de allocation.

### Lote 4 — Portal público e entidades de habitação

Exemplos:

- `HousingUnitPublicDocument`
- `HousingUnitPhoto`
- `PublicPortalSetting`
- `ContestHousingUnit`

Apenas relações simples.

Não corrigir visibilidade pública, filtros ou queries.

### Lote 5 — Factories e scopes simples

Aplicar apenas quando o padrão já estiver validado nos lotes anteriores.

---

## 12. Domínios Excluídos Neste Sprint

Mesmo que tenham `missingType.generics`, não corrigir automaticamente sem validação posterior:

- RGPD
- Security
- Audit sensível
- Eligibility
- Scoring
- Finance/Rents
- Contracts críticos
- Document Intelligence complexa
- Policies
- Controllers
- Form Requests

Motivo: o relatório PHPSTAN-01 classificou estes domínios como críticos ou potencialmente perigosos quando associados a erros de tipo, permissões, estados ou dados pessoais.

---

## 13. Validação Obrigatória por Lote

Após cada lote, executar:

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

Depois:

```bash
php artisan test
```

Depois:

```bash
vendor/bin/pint --test
```

Se houver alteração frontend indireta, executar:

```bash
npm run build
```

Normalmente este sprint não deve exigir build frontend.

---

## 14. Critérios de Bloqueio

Parar imediatamente se:

- um teste falhar;
- Pint falhar por alteração inesperada;
- PHPStan aumentar o total de erros;
- surgir erro novo fora de `missingType.generics`;
- for necessário alterar lógica para satisfazer PHPStan;
- houver dúvida sobre a classe relacionada;
- houver risco de permissões, RGPD, scoring, eligibility ou documentos.

---

## 15. Proibições Explícitas

Não usar:

```php
@phpstan-ignore-next-line
```

Não usar:

```php
@phpstan-ignore-line
```

Não usar baseline.

Não usar tipos genéricos falsos.

Não usar `mixed` para esconder erro, exceto em relação polimórfica documentada e justificada.

Não converter propriedades/casts/enums neste sprint.

Não alterar runtime para satisfazer análise estática.

Não remover métodos.

Não alterar assinaturas públicas que possam ser usadas por controllers, policies, services ou views.

---

## 16. Entregável Técnico

Criar relatório final em Markdown:

```text
docs/qa/phpstan-02-eloquent-generics-remediation-report.md
```

O relatório deve conter:

1. Resumo executivo
2. Total de erros antes/depois
3. `missingType.generics` antes/depois
4. Ficheiros alterados
5. Relações corrigidas
6. Factories tipadas
7. Scopes tipados
8. Relações adiadas
9. Riscos encontrados
10. Comandos executados
11. Resultado dos testes
12. Resultado do Pint
13. Resultado do PHPStan
14. Erros remanescentes por domínio
15. Recomendação para PHPSTAN-03

---

## 17. Template de Relatório

```md
# Relatório PHPSTAN-02 — Remediação Segura de Generics Eloquent

## Resumo executivo

## Métricas

| Métrica | Antes | Depois | Diferença |
| --- | ---: | ---: | ---: |
| Total PHPStan | 2897 | X | -Y |
| missingType.generics | 1160 | X | -Y |
| Ficheiros afetados | 561 | X | -Y |

## Ficheiros alterados

## Relações corrigidas

## Factories tipadas

## Scopes tipados

## Relações adiadas

## Riscos e decisões

## Comandos executados

## Resultado final

## Recomendação para PHPSTAN-03
```

---

## 18. Critério de Sucesso

O sprint só está concluído se:

- o número de `missingType.generics` diminuir;
- o total de erros PHPStan não aumentar;
- não forem introduzidos erros novos;
- os testes passarem;
- o Pint passar;
- todas as alterações forem exclusivamente tipagem/PHPDoc/imports;
- nenhum domínio crítico for alterado funcionalmente;
- o relatório final for criado.

---

## 19. Resultado Esperado

Reduzir de forma significativa o ruído estático causado por relações Eloquent sem tocar em regras de negócio.

Este sprint deve preparar a plataforma para o PHPSTAN-03, onde serão tratados:

- arrays;
- collections;
- return types;
- nullability simples;
- Form Requests;
- serviços de baixo risco.

O sucesso deste sprint mede-se pela redução segura de dívida técnica, não pelo número absoluto máximo de erros eliminados.
