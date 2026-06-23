# SPRINT PHPSTAN-07 — Document Intelligence, Jobs, Queues & Integrations Hardening

**Projeto:** CRM MV HAB  
**Framework:** Laravel 13.8  
**PHP:** 8.4  
**Data:** 2026-06-23

---

# Objetivo

Executar uma remediação incremental e segura dos erros PHPStan concentrados nos módulos de:

- Document Intelligence
- Document Analysis
- Document Validation
- Jobs
- Queues
- Events
- Listeners
- Notifications
- Integrações externas
- Uploads documentais
- OCR
- Processamento assíncrono

Sem alterar regras de negócio, workflows administrativos ou permissões da plataforma.

---

# Estado Atual

## Resultado após PHPSTAN-06

| Métrica | Valor |
|----------|----------:|
| Total PHPStan | 2513 |
| Ficheiros afetados | 452 |
| Testes | 283 |
| Asserções | 1775 |
| Pint | OK |

---

# Regras Obrigatórias

## NÃO ALTERAR

Sob nenhuma circunstância alterar:

- Elegibilidade
- Pontuação
- Classificação
- Sorteios
- Allocation
- Contratos
- Rendas
- RGPD
- Policies
- MFA
- Segurança
- Finance
- Maintenance
- Vistorias
- Auditoria crítica

## NÃO CRIAR

Proibido:

- Baseline PHPStan
- IgnoreErrors
- SuppressWarnings
- @phpstan-ignore-next-line
- @phpstan-ignore-line
- Alterações de permissões
- Alterações de migrations
- Alterações de seeders
- Alterações de workflows

---

# Âmbito da Sprint

## BLOCO 1 — Document Intelligence

### Diretórios

app/Services/DocumentIntelligence/*
app/Services/DocumentAnalysis/*
app/Services/DocumentValidation/*

### Corrigir

- property.nonObject
- method.nonObject
- argument.type
- return.type
- missingType.iterableValue
- missingType.return

### Permitido

- PHPDoc
- Array Shapes
- Return Types
- Null Guards
- Defensive Programming

### Proibido

- Alterar regras de validação documental
- Alterar scoring documental
- Alterar estados documentais

---

## BLOCO 2 — Jobs

### Diretórios

app/Jobs/*

### Corrigir

- Payloads mal tipados
- Arrays sem shape
- Nullable incorretos
- Serialização insegura

### Verificar

- ShouldQueue
- Queueable
- Batchable
- Dispatchable
- SerializesModels

### Objetivos

- Garantir payloads determinísticos
- Garantir tipos explícitos
- Garantir serialização segura

---

## BLOCO 3 — Queues

### Analisar

app/Jobs/*
app/Listeners/*

### Validar

- Retry logic
- Failures
- Backoff
- Timeout
- Dead jobs

---

## BLOCO 4 — Events & Listeners

### Diretórios

app/Events/*
app/Listeners/*

### Garantir

- Payloads tipados
- Eventos consistentes
- Listeners resilientes

---

## BLOCO 5 — Notifications

### Diretórios

app/Notifications/*

### Garantir

Compatibilidade com:

- mail
- database
- broadcast

---

## BLOCO 6 — Integrações

### Diretórios

app/Services/Integrations/*
app/Services/External/*
app/Services/Notifications/*

### Garantir

- Respostas defensivas
- Null safety
- Tipagem explícita

---

## BLOCO 7 — Uploads Documentais

### Analisar

- DocumentSubmission
- DocumentVersion
- DocumentReview
- DocumentChecklist
- DocumentAiValidation

### NÃO alterar

- Permissões
- Workflow documental
- Estados
- Regras de aprovação

---

# Critérios de Bloqueio

Parar imediatamente e reverter alterações se forem detetados impactos em:

- Elegibilidade
- Pontuação
- Classificação
- Contratos
- Rendas
- RGPD
- Policies
- Segurança
- Sorteios
- Allocation

---

# Testes Obrigatórios

```bash
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
./vendor/bin/phpstan analyse --memory-limit=1G -v
```

---

# Entregáveis

- docs/qa/phpstan-07-document-intelligence-jobs-queues-report.md
- storage/phpstan/phpstan-07-before.txt
- storage/phpstan/phpstan-07-after-document-intelligence.txt
- storage/phpstan/phpstan-07-after-jobs.txt
- storage/phpstan/phpstan-07-after-events.txt
- storage/phpstan/phpstan-07-after-integrations.txt
- storage/phpstan/phpstan-07-final.txt

---

# Meta da Sprint

## Objetivo mínimo

-100 erros PHPStan

## Objetivo esperado

-150 a -250 erros PHPStan

## Objetivo máximo

< 2400 erros PHPStan

Sem regressões funcionais.

---

# Próxima Sprint Prevista

## PHPSTAN-08 — Models Core & Eloquent Relations Hardening

Foco:

- User
- Application
- Contest
- Program
- HousingUnit
- Allocation
- Contract
- Eligibility
- Scoring
