# Cobranças Automáticas Operacionais

## Objetivo

Permitir execuções internas de geração de valores a cobrar para contratos ativos.

## Implementado

- `tenant_charge_runs` e `tenant_charge_run_items`.
- Serviço `TenantChargeRunService`.
- Comando `php artisan tenants:generate-charges`.
- Backoffice em `backoffice/tenant-operations/charge-runs`.

## Texto obrigatório

As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.

## Salvaguardas

- Execução idempotente por ano, mês e tipo de cobrança.
- Faturas existentes são marcadas como ignoradas na execução.
- Não é iniciado qualquer movimento bancário externo.
