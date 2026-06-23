# SPRINT PHPSTAN-06 — PERFORMANCE, REPORTS, EXPORTS & DASHBOARDS HARDENING

## Objetivo

Executar a próxima fase de remediação PHPStan e hardening técnico da plataforma MV HAB, com foco nos módulos de **Reporting, Exports, Dashboards, Indicadores, Logs de Auditoria e Consultas Operacionais**, reduzindo erros estáticos sem degradar desempenho, segurança, RGPD ou comportamento funcional.

Esta sprint deve transformar a camada de relatórios numa base mais previsível, tipada, auditável e preparada para escala municipal/multi-município.

---

## Contexto Atual

Após as sprints anteriores:

| Sprint | Resultado |
| --- | --- |
| PHPSTAN-01 | Inventário inicial de 2897 erros em 561 ficheiros |
| PHPSTAN-02 | Redução para 2828 erros, com foco em generics Eloquent seguros |
| PHPSTAN-03 | Redução para 2755 erros, com foco em Form Requests, arrays, reporting e factories |
| PHPSTAN-04 | Redução para 2751 erros, com correção de 3 bugs reais |
| PHPSTAN-05 | Redução para 2706 erros, com foco em Security, RGPD, MFA, audit e exportações RGPD |

O relatório PHPSTAN-05 recomenda avançar para **PHPSTAN-06 — Performance, Reports, Exports e Dashboards**, mantendo uma eventual sub-sprint PHPSTAN-05B para policies críticas caso a prioridade imediata seja autorização.

---

## Âmbito Principal

Atuar sobre:

- `app/Services/Reporting/*`
- `app/Services/OperationalReports/*`
- `app/Services/Reporting/Exporters/*`
- `app/Http/Controllers/Backoffice/Reporting/*`
- dashboards executivos, operacionais e financeiros
- indicadores, snapshots, report runs, report exports e report downloads
- report definitions, filters e audit/access logs associados a relatórios

---

## Fora de Âmbito

Não alterar:

- elegibilidade, pontuação, classificação ou regras de concursos
- candidaturas, documentos privados, contratos/rendas core
- allocation/lottery
- RGPD subject rights logic
- MFA
- policies/permissões
- migrations/seeders
- frontend visual

Qualquer alteração que toque permissões, RGPD, segurança ou documentos privados deve ser recusada ou isolada para sprint própria.

---

## Regras Absolutas

1. Não usar `@phpstan-ignore`, baseline ou suppressions para esconder dívida.
2. Não alargar tipos artificialmente para silenciar PHPStan.
3. Não substituir bugs por `mixed`.
4. Não alterar queries sem provar impacto e regressão.
5. Não remover filtros de autorização.
6. Não alterar regras de acesso a relatórios.
7. Não expor dados pessoais adicionais em exports.
8. Não alterar o formato público dos relatórios sem teste.
9. Não gerar migrations.
10. Toda a correção funcional deve ter teste dirigido.

---

## Fase 1 — Baseline da Sprint

Executar:

```bash
php artisan optimize:clear

./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-06-before.txt

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Registar:

| Métrica | Valor |
| --- | ---: |
| Total PHPStan antes | 2706 |
| Ficheiros afetados antes | 482 |
| Testes antes | 283 |
| Asserções antes | 1775 |

Atualizar estes números com a execução real.

---

## Fase 2 — Inventário Alvo

Inventariar erros PHPStan nos domínios Reporting, Reports/Exports, Dashboard, Indicators, OperationalReports, Finance dashboards e Audit logs associados a relatórios.

Classificar por identificador:

- `property.notFound`
- `argument.type`
- `missingType.iterableValue`
- `missingType.generics`
- `return.type`
- `method.unresolvableReturnType`
- `argument.templateType`
- `property.nonObject`
- `method.nonObject`
- `match.alwaysFalse`
- `deadCode.unreachable`

Criar tabela:

| Ficheiro | Linha | Identificador | Risco | Decisão |
| --- | ---: | --- | --- | --- |

---

## Fase 3 — Tipagem Segura de Reporting

Corrigir apenas tipagem segura em:

- arrays de filtros
- arrays de parâmetros
- arrays de linhas exportáveis
- collections de indicadores
- DTO-like arrays
- retorno de exporters
- payloads de dashboards

Preferir:

```php
/**
 * @return array<string, mixed>
 */
```

ou, quando possível:

```php
/**
 * @return array<int, array<string, mixed>>
 */
```

Para collections:

```php
/**
 * @return Collection<int, ReportDefinition>
 */
```

Não usar `array` genérico quando o shape é conhecido.

---

## Fase 4 — Query Builders e Eloquent Models

Corrigir erros em query builders apenas quando o tipo real é inequívoco.

Exemplo aceitável:

```php
/**
 * @param Builder<ReportDefinition> $query
 * @return Builder<ReportDefinition>
 */
```

Evitar correções que introduzam ruído `literal-string` em queries dinâmicas.

Se uma correção gerar novos erros fora do domínio de reporting, reverter o lote.

---

## Fase 5 — Exports Seguros

Validar exporters:

- CSV
- HTML
- PDF fallback
- XLSX fallback
- downloads
- logs de exportação
- proteção contra CSV formula injection
- serialização JSON
- normalização de valores
- memória usada em exports

Garantir:

- nenhuma célula inicia fórmula perigosa sem escape;
- valores complexos são serializados de forma previsível;
- falhas de `fopen`, `json_encode`, `Storage::put` e downloads têm guarda;
- exports grandes não fazem carregamentos desnecessários.

---

## Fase 6 — Performance e Memória

Analisar os riscos já identificados:

| Área | Risco | Ação esperada |
| --- | --- | --- |
| Reporting dashboards | queries agregadas repetidas | propor cache/materialização sem implementar se for estrutural |
| Exports | arrays completos em memória | identificar candidatos a chunking |
| Audit logs | filtros por módulo/ação/data | confirmar índices necessários sem migration |
| Candidate dashboard | relações agregadas | identificar eager loading/counters |
| Finance reports | pagamentos/rendas por contrato | identificar índices/filtros críticos |

Implementar apenas melhorias de baixo risco e sem alteração funcional. Melhorias estruturais ficam em backlog técnico.

---

## Fase 7 — Auditoria e Segurança dos Relatórios

Validar:

- downloads continuam auditados;
- report access logs continuam registados;
- permissões não foram alargadas;
- filtros sensíveis não expõem dados pessoais indevidos;
- exports de relatórios sensíveis mantêm controlo de acesso;
- relatórios financeiros não expõem dados de outro candidato/inquilino.

Não alterar policies nesta sprint. Se for detetado risco de policy, documentar para PHPSTAN-05B.

---

## Fase 8 — Testes Dirigidos

Criar ou reforçar testes apenas para fluxos tocados:

- geração de relatório
- execução de report run
- download/export
- CSV/HTML fallback
- logs de acesso a relatório
- filtros de dashboard
- indicadores executivos/operacionais
- proteção contra valores inválidos em export

Comandos mínimos:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Report
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Dashboard
```

Se estes filtros forem demasiado amplos ou insuficientes, executar testes específicos dos ficheiros alterados.

---

## Fase 9 — Validação Final

Executar:

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json > storage/phpstan/phpstan-06-final.txt

./vendor/bin/pint --test

php artisan route:list --except-vendor

php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Opcional se houver alteração frontend:

```bash
npm run build
```

---

## Critérios de Bloqueio

Reverter o lote se:

- aumentar erros PHPStan globais;
- surgir erro novo em Security/RGPD/Audit/Policies;
- algum teste funcional falhar;
- houver alteração de permissões;
- houver alteração de regras de negócio;
- algum export perder auditoria;
- algum relatório passar a expor mais dados pessoais;
- uma correção de generics gerar cascata de erros fora de reporting.

---

## Entregável Final

Criar relatório:

`docs/qa/phpstan-06-performance-reports-exports-dashboards-report.md`

Com estrutura:

1. Resumo executivo
2. PHPStan antes/depois
3. Distribuição dos erros removidos
4. Ficheiros alterados
5. Correções executadas
6. Riscos de performance identificados
7. Riscos de segurança/RGPD identificados
8. Testes criados/atualizados
9. Comandos executados
10. Migrations/seeders/dependências
11. Riscos residuais
12. Recomendação para PHPSTAN-07

---

## Métricas Esperadas

| Métrica | Esperado |
| --- | ---: |
| Redução PHPStan | 30 a 80 erros |
| Novos erros | 0 |
| Testes finais | verdes |
| Pint | verde |
| Migrations | 0 |
| Alterações frontend | 0 |

---

## Recomendação para a Sprint Seguinte

Após PHPSTAN-06, avançar para:

# PHPSTAN-07 — DOCUMENT INTELLIGENCE, JOBS, QUEUES & INTEGRATIONS HARDENING

Foco:

- pipeline IA documental local;
- jobs e queues;
- retries;
- falhas parciais;
- logs de processamento;
- privacidade dos documentos;
- tipagem de DTOs de extração;
- performance de OCR/IA;
- ausência de chamadas externas obrigatórias.
