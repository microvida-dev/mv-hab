# Relatórios de Manutenção Pós-Atribuição

## Objetivo

Permitir consulta operacional dos pedidos de manutenção em aberto na fase de exploração habitacional.

## Implementado

- Rota `backoffice.tenant-operations.maintenance-reports.index`.
- Serviço `TenantMaintenance\MaintenanceReportService`.
- Listagem dos últimos pedidos em aberto com habitação, contrato e categoria.

## Decisão técnica

A Sprint 26 não duplica o módulo de manutenção; apenas acrescenta uma vista agregada orientada à operação pós-atribuição.

## Pendências

- Exportação PDF/CSV se aprovada.
- Métricas SLA e custos acumulados por contrato.
