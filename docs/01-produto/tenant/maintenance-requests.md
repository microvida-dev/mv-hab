# Pedidos de Manutenção do Inquilino

## Objetivo

Permitir que o inquilino submeta e acompanhe pedidos de manutenção ligados ao contrato/habitação próprios.

## Implementado

- Rotas `tenant.maintenance.*`.
- Reutilização do modelo `MaintenanceRequest`.
- Criação via `MaintenanceRequestService::createFromTenant`.
- Anexos continuam no storage privado e via controllers autorizados existentes.

## Texto obrigatório

Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.

## Decisão técnica

Não foi criada uma tabela `tenant_maintenance_requests`, porque o módulo de manutenção existente já tem `user_id`, `lease_contract_id`, `housing_unit_id`, estados, histórico e anexos privados.
