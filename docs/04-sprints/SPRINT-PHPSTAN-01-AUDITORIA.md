# SPRINT PHPSTAN-01 — AUDITORIA ESTRATÉGICA DE REMEDIAÇÃO ENTERPRISE

## Objetivo

Executar uma auditoria exaustiva, não destrutiva e orientada a produto sobre todos os erros PHPStan existentes na plataforma MV HAB, criando uma estratégia segura de remediação progressiva sem comprometer funcionalidades existentes.

Este sprint é exclusivamente de análise.

Não corrigir código.

Não criar commits.

Não alterar ficheiros.

---

## Contexto Técnico

A plataforma MV HAB é um sistema crítico de Habitação Pública municipal, desenvolvido em:

- Laravel 13.8+
- PHP 8.4
- MySQL/MariaDB
- Blade
- Tailwind CSS
- Alpine.js
- Vite
- Eloquent ORM
- Form Requests
- Policies
- Services
- Jobs
- Auditoria
- RGPD
- Gestão documental privada
- Document Intelligence local

O relatório técnico identifica uma plataforma funcionalmente avançada, mas ainda com dívida técnica relevante, incluindo milhares de erros PHPStan legados.

O objetivo é transformar PHPStan num instrumento de estabilização enterprise, deteção de bugs reais e preparação da plataforma para escala SaaS/GovTech.

---

## Regras Absolutas

Não alterar:

- Elegibilidade
- Pontuação
- Classificação
- Concursos
- Candidaturas
- Documentos
- Auditoria
- RGPD
- Policies
- Contratos
- Rendas
- Manutenção
- Vistorias
- Notificações
- Workflows administrativos

Não executar correções automáticas.

Não remover código.

Não alterar migrations.

Não alterar seeders.

Não alterar configuração de produção ou local.

---

## Tarefa Principal

Executar:

```bash
vendor/bin/phpstan analyse --error-format=json
```

Se necessário, executar também:

```bash
vendor/bin/phpstan analyse
```

Guardar os resultados em relatório técnico.

---

## Fase 1 — Inventário Completo

Inventariar todos os erros PHPStan encontrados.

Para cada erro identificar:

- ficheiro
- linha
- mensagem
- identificador PHPStan
- categoria técnica
- domínio funcional afetado
- severidade
- risco funcional
- causa provável
- recomendação inicial

---

## Fase 2 — Agrupamento Técnico

Agrupar os erros por identificador:

- missingType.generics
- relation.generics
- return.type
- property.notFound
- method.nonObject
- booleanAnd.alwaysFalse
- identical.alwaysFalse
- nullsafe.neverNull
- argument.type
- offsetAccess
- arrayShape
- deadCode
- generic.objectType
- phpDoc.parseError
- outros

Criar tabela:

| Identificador | Quantidade | Severidade | Risco | Prioridade |

---

## Fase 3 — Agrupamento por Domínio

Classificar cada erro por domínio:

- Models
- Relations
- Controllers
- Form Requests
- Services
- Policies
- Jobs
- Events
- Reports
- Exports
- Documents
- Eligibility
- Scoring
- Contests
- Applications
- Lists
- Allocation
- Lottery
- Contracts
- Finance/Rents
- Maintenance
- Inspections
- Tenant Area
- Public Portal
- RGPD
- Audit
- Security
- Document Intelligence

Criar tabela:

| Domínio | Quantidade | Risco | Prioridade | Observações |

---

## Fase 4 — Classificação de Risco

Classificar cada erro numa das categorias:

### A — Cosmético

Erro de PHPDoc, formatação ou anotação sem impacto funcional.

### B — Tipagem segura

Generics, arrays, collections, relações Eloquent e tipos estáticos corrigíveis sem alterar comportamento.

### C — Qualidade de código

Código ambíguo, duplicado, pouco explícito ou difícil de analisar.

### D — Potencial bug

Erro que pode esconder comportamento incorreto.

### E — Bug real provável

Erro que sugere falha funcional concreta.

### F — Segurança

Erro relacionado com permissões, acesso indevido, documentos privados ou autenticação.

### G — RGPD

Erro que pode afetar dados pessoais, retenção, anonimização, exportação ou consentimento.

### H — Performance

Erro que pode indicar queries ineficientes, loops excessivos, coleções grandes ou N+1.

---

## Fase 5 — Identificação de Quick Wins

Identificar erros de baixo risco, corrigíveis em sprints seguintes sem alterar comportamento.

Exemplos:

- Generics Eloquent em relações simples
- PHPDoc de arrays
- PHPDoc de collections
- Tipagem de factories
- Tipagem de policies simples
- Tipagem de Form Requests
- Return types documentais sem impacto runtime

Criar tabela:

| Ficheiro | Erro | Correção sugerida | Risco | Sprint recomendado |

---

## Fase 6 — Identificação de Alto Risco

Identificar erros que não devem ser corrigidos automaticamente.

Prioridade máxima para:

- Eligibility Services
- Scoring Services
- Contest Workflows
- Application Submission
- Document Validation
- List Publication
- Allocation
- Lottery
- Lease/Contract Workflows
- Rent/Finance
- RGPD
- Audit
- Security Policies

Criar tabela:

| Ficheiro | Linha | Erro | Risco funcional | Recomendação |

---

## Fase 7 — Deteção de Bugs Reais

Sinalizar erros que possam representar:

- estado impossível
- condição sempre falsa
- condição sempre verdadeira
- acesso a método em null
- acesso a propriedade inexistente
- array offset inválido
- relação Eloquent incorreta
- policy não aplicável
- workflow impossível
- código morto

Não corrigir.

Apenas documentar.

---

## Fase 8 — Plano de Remediação por Ondas

Criar plano de remediação seguro:

### Sprint PHPSTAN-02

Generics Eloquent e relações simples.

### Sprint PHPSTAN-03

Return types, arrays, collections e nullability de baixo risco.

### Sprint PHPSTAN-04

Bugs reais, estados impossíveis e lógica de domínio.

### Sprint PHPSTAN-05

Policies, segurança, RGPD e auditoria.

### Sprint PHPSTAN-06

Performance, reports, exports e dashboards.

### Sprint PHPSTAN-07

Document Intelligence, jobs, queues e integrações.

### Sprint PHPSTAN-08

Baseline enterprise, CI/CD e quality gates.

---

## Fase 9 — Quality Gates Recomendados

Propor estratégia de CI/CD futura com:

```bash
composer validate
```

```bash
vendor/bin/pint --test
```

```bash
php artisan test
```

```bash
vendor/bin/phpstan analyse
```

```bash
npm run build
```

Definir política de redução progressiva:

- não aumentar erros PHPStan
- novos ficheiros devem estar limpos
- PRs não podem introduzir erros novos
- erros legados devem ser reduzidos por sprint
- código crítico deve ter prioridade sobre cosmético

---

## Entregável Final

Criar relatório Markdown com:

# Relatório PHPSTAN-01 — Auditoria Estratégica

Deve conter:

1. Resumo executivo
2. Total de erros encontrados
3. Distribuição por identificador
4. Distribuição por domínio
5. Ranking por severidade
6. Ranking por risco funcional
7. Quick wins
8. Alto risco
9. Bugs reais prováveis
10. Falsos positivos prováveis
11. Código morto provável
12. Domínios mais frágeis
13. Plano de remediação por sprints
14. Quality gates recomendados
15. Riscos residuais
16. Recomendação final

---

## Critério de Sucesso

O sprint só está concluído se:

- 100% dos erros forem inventariados
- os erros estiverem agrupados por categoria e domínio
- os riscos estiverem classificados
- não houver alterações de código
- não houver commits
- existir plano de remediação por ondas
- os domínios críticos estiverem protegidos
- o relatório permitir avançar para correções seguras no Sprint PHPSTAN-02

Resultado esperado:

Criar uma estratégia objetiva, segura e tecnicamente fundamentada para eliminar progressivamente a dívida PHPStan da plataforma MV HAB, transformando a auditoria estática num mecanismo de hardening enterprise sem danificar a plataforma atual.
