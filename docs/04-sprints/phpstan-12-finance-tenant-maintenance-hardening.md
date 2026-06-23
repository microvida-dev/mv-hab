# SPRINT PHPSTAN-12 — Finance, Tenant Area & Maintenance Hardening

**Projeto:** CRM MV HAB
**Framework:** Laravel 13.8+
**PHP:** 8.4
**Data:** 2026-06-23

---

# Objetivo

Prosseguir a remediação estruturada do PHPStan nos domínios com maior impacto operacional após conclusão das sprints anteriores.

Foco principal:

- Contratos
- Rendas
- Pagamentos
- Área do Inquilino
- Manutenção
- Vistorias
- Faturação
- Cobranças
- Comunicações ao inquilino
- Relatórios financeiros

Meta:

- Redução adicional de 150–250 erros PHPStan
- Correção de bugs reais identificados na auditoria
- Preservação integral da lógica de negócio

---

# Domínios Prioritários

## Contracts

```text
app/Models/Contract.php
app/Services/Contracts/*
```

Objetivos:

- missingType.generics
- property.nonObject
- method.nonObject
- return.type
- argument.type

Validar:

- assinatura
- ativação
- renovação
- cessação
- histórico

---

## Finance

```text
app/Services/Finance/*
app/Http/Controllers/*Finance*
```

Validar:

- contas correntes
- recibos
- rendas
- revisões
- pagamentos

Corrigir:

- argument.type
- property.nonObject
- deadCode.unreachable

---

## Tenant Billing

```text
app/Services/TenantBilling/*
```

Validar:

- emissão de faturas
- pagamentos
- reconciliação
- histórico

---

## Tenant Area

```text
Tenant Portal
Tenant Communications
Tenant Dashboard
```

Validar:

- permissões
- contratos
- pagamentos
- manutenção
- documentos

---

## Maintenance

```text
MaintenanceRequest
MaintenanceIntervention
MaintenanceCategory
MaintenanceAttachment
```

Objetivos:

- generics Eloquent
- null safety
- tipagem de coleções

Validar:

- pedidos
- estados
- anexos
- intervenções

---

## Inspections

```text
Inspection
InspectionReport
InspectionChecklist
```

Validar:

- agendamento
- execução
- relatório
- anexos

---

# Bugs Prioritários

## PaymentReceiptController

```text
FilesystemAdapter::download()
string|null
```

Garantir:

```php
abort_unless($path !== null, 404);
```

antes do download.

---

## TenantFinancialAccountController

```text
argument.type
```

Validar:

- Contract real
- findOrFail()
- route model binding

---

## TenantInvoiceController

```text
argument.type
```

Garantir:

- contrato válido
- tenant válido
- invoice válida

---

## TenantPaymentController

```text
argument.type
```

Garantir:

- invoice nunca null
- pagamentos auditados

---

# Regras

Não alterar:

- cálculo de renda
- contratos ativos
- histórico financeiro
- auditoria
- RGPD
- notificações legais

Proibido:

- ignoreErrors
- baseline
- mixed para esconder erros

---

# Testes Obrigatórios

```bash
php artisan optimize:clear

./vendor/bin/pint --test

php artisan test

./vendor/bin/phpstan analyse --memory-limit=1G
```

Testes dirigidos:

```bash
--filter Contract
--filter Finance
--filter Rent
--filter Tenant
--filter Maintenance
--filter Inspection
```

---

# Critérios de Sucesso

## Mínimo

```text
-150 erros
```

## Esperado

```text
-250 erros
```

## Ambicioso

```text
< 750 erros globais
```

---

# Artefactos

```text
docs/qa/phpstan-12-finance-tenant-maintenance-report.md

storage/phpstan/phpstan-12-before.txt
storage/phpstan/phpstan-12-after-contracts.txt
storage/phpstan/phpstan-12-after-finance.txt
storage/phpstan/phpstan-12-after-tenant.txt
storage/phpstan/phpstan-12-after-maintenance.txt
storage/phpstan/phpstan-12-final.txt
```

---

# Próxima Sprint

## PHPSTAN-13 — Public Portal, Reports & Integrations

Foco:

- Portal público
- Concursos
- Habitações
- Relatórios
- Exports
- Dashboard executivo
- Dashboard operacional
- Integrações externas
- Document Intelligence
