# SPRINT PHPSTAN-11 — Policies, Controllers & Candidate Workflow Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8+  
**PHP:** 8.4  
**Data:** 2026-06-23

---

# Objetivo

Prosseguir a redução sistemática dos erros PHPStan após a conclusão da PHPSTAN-10, focando:

- Policies
- Controllers de candidato
- Controllers administrativos
- Workflows de candidatura
- Gestão documental privada
- Household / agregado familiar
- Habitação atual
- Submissão e reutilização de dados
- Ownership e permissões
- RGPD e acesso a documentos

Meta principal:

- Redução adicional de 150–300 erros PHPStan
- Zero regressões funcionais
- Zero alterações às regras regulamentares

---

# Domínios Prioritários

## Policies

```text
app/Policies/*
```

Objetivos:

- missingType.generics
- argument.type
- method.nonObject
- property.nonObject
- return.type

Validar:

- ownership
- municipality scope
- document access
- application access
- tenant access

---

## Candidate Controllers

```text
app/Http/Controllers/Candidate/*
```

Objetivos:

- Requests tipados
- Collections tipadas
- Guards explícitos
- Nullability segura

Sem alterar:

- submissão
- elegibilidade
- pontuação
- ranking
- workflow

---

## Administrative Controllers

```text
app/Http/Controllers/Admin/*
```

Objetivos:

- reduzir argument.type
- reduzir property.nonObject
- reduzir method.nonObject
- reduzir return.type

---

## Household Domain

```text
Household
HouseholdMember
Income
CurrentHousing
```

Validar:

- relações
- coleções
- enum casts
- datas

---

## Document Domain

```text
Document
DocumentSubmission
RequiredDocument
DocumentReview
```

Validar:

- acesso privado
- ownership
- auditoria
- RGPD

---

# Regras Absolutas

Não alterar:

- Regulamento
- Elegibilidade
- Pontuação
- Ranking
- Sorteios
- Listas
- Contratos
- Rendas
- Audiência prévia
- Reclamações
- Notificações legais

Proibido:

- baseline
- ignoreErrors
- phpstan-ignore
- mixed para ocultar erro

---

# Testes Obrigatórios

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php -d memory_limit=-1 ./vendor/bin/phpunit

./vendor/bin/phpstan analyse --memory-limit=1G
```

Testes dirigidos:

```bash
--filter Candidate
--filter Household
--filter Document
--filter Application
--filter Policy
```

---

# Critérios de Sucesso

## Mínimo

```text
-150 erros PHPStan
```

## Esperado

```text
-250 erros PHPStan
```

## Ambicioso

```text
< 1000 erros globais
```

---

# Artefactos

```text
docs/qa/phpstan-11-policies-controllers-candidate-workflow-report.md

storage/phpstan/phpstan-11-before.txt
storage/phpstan/phpstan-11-after-policies.txt
storage/phpstan/phpstan-11-after-controllers.txt
storage/phpstan/phpstan-11-after-household.txt
storage/phpstan/phpstan-11-after-documents.txt
storage/phpstan/phpstan-11-final.txt
```

---

# Próxima Sprint Prevista

## PHPSTAN-12 — Finance, Tenant Area & Maintenance Hardening

Foco:

- Contracts
- Payments
- Rents
- Maintenance
- Inspections
- Tenant Portal
- Notifications
- Reporting
