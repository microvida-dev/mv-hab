# Decisões de Permissions — Sprint 47G

## Referências

- Manifesto: `docs/access/manifests/sprint-47g-route-manifest.json`
- Commit de origem: `8bf949b965f3ee64d29c894a29148a3c34f0afd2`
- Gerado a partir do manifesto em: `2026-07-25T14:49:15.301348+00:00`
- Estado: decisão aceite para implementação

## Resumo

| Indicador | Valor |
|---|---:|
| Rotas reconciliadas | 96 |
| Permissions finais únicas | 68 |
| Permissions existentes | 12 |
| Permissions novas | 56 |
| Leituras | 43 |
| Mutações | 53 |
| Manutenção | 51 |
| Vistorias | 26 |
| Visitas | 18 |
| Agenda | 1 |

## Princípios

```text
permission
&& Policy
&& scope municipal
&& estado ou transição válida
&& MFA quando exigido
&& auditoria quando exigida
```

Roles atribuem permissions, mas não substituem as Policies.

Não são introduzidos novos `FeatureKey` na Sprint 47G.

## Permissions existentes reutilizadas

- `housing_units.view`
- `inspections.create`
- `inspections.update`
- `inspections.view`
- `maintenance_requests.create`
- `maintenance_requests.delete`
- `maintenance_requests.reject`
- `maintenance_requests.update`
- `maintenance_requests.view`
- `reports.view_maintenance`
- `visits.reject`
- `visits.view`

## Permissions novas

- `agenda.view`
- `inspections.attachments.create`
- `inspections.attachments.download`
- `inspections.cancel`
- `inspections.close`
- `inspections.complete`
- `inspections.items.create`
- `inspections.items.update`
- `inspections.reports.cancel`
- `inspections.reports.download`
- `inspections.reports.generate`
- `inspections.reports.validate`
- `inspections.reports.view`
- `inspections.start`
- `inspections.templates.create`
- `inspections.templates.update`
- `inspections.templates.view`
- `inspections.validate`
- `maintenance.assignments.cancel`
- `maintenance.assignments.create`
- `maintenance.attachments.create`
- `maintenance.attachments.download`
- `maintenance.categories.create`
- `maintenance.categories.delete`
- `maintenance.categories.update`
- `maintenance.categories.view`
- `maintenance.costs.approve`
- `maintenance.costs.create`
- `maintenance.costs.reject`
- `maintenance.costs.view`
- `maintenance.interventions.cancel`
- `maintenance.interventions.complete`
- `maintenance.interventions.create`
- `maintenance.interventions.start`
- `maintenance.interventions.view`
- `maintenance.suppliers.create`
- `maintenance.suppliers.update`
- `maintenance.suppliers.view`
- `maintenance_requests.cancel`
- `maintenance_requests.close`
- `maintenance_requests.resolve`
- `maintenance_requests.review`
- `maintenance_requests.schedule`
- `maintenance_requests.start`
- `visits.availabilities.create`
- `visits.availabilities.delete`
- `visits.availabilities.generate_slots`
- `visits.availabilities.update`
- `visits.availabilities.view`
- `visits.cancel`
- `visits.complete`
- `visits.confirm`
- `visits.mark_no_show`
- `visits.slots.block`
- `visits.slots.unblock`
- `visits.slots.view`

## Utilização por permission

### `agenda.view` — new

- `backoffice.agenda.index` → `viewBackoffice`

### `housing_units.view` — existing

- `backoffice.cases.housing-units.show` → `viewBackoffice`
- `backoffice.properties.technical-history` → `viewBackoffice`

### `inspections.attachments.create` — new

- `backoffice.inspections.attachments.store` → `createBackoffice`

### `inspections.attachments.download` — new

- `backoffice.inspections.attachments.download` → `downloadBackoffice`

### `inspections.cancel` — new

- `backoffice.inspections.cancel` → `cancelBackoffice`

### `inspections.close` — new

- `backoffice.inspections.close` → `closeBackoffice`

### `inspections.complete` — new

- `backoffice.inspections.complete` → `completeBackoffice`

### `inspections.create` — existing

- `backoffice.inspections.create` → `createBackoffice`
- `backoffice.inspections.store` → `createBackoffice`

### `inspections.items.create` — new

- `backoffice.inspections.items.store` → `createBackoffice`

### `inspections.items.update` — new

- `backoffice.inspections.items.update` → `updateBackoffice`

### `inspections.reports.cancel` — new

- `backoffice.inspections.reports.cancel` → `cancelBackoffice`

### `inspections.reports.download` — new

- `backoffice.inspections.reports.download` → `downloadBackoffice`

### `inspections.reports.generate` — new

- `backoffice.inspections.reports.generate` → `generateBackoffice`

### `inspections.reports.validate` — new

- `backoffice.inspections.reports.validate` → `validateBackoffice`

### `inspections.reports.view` — new

- `backoffice.inspections.reports.show` → `viewBackoffice`

### `inspections.start` — new

- `backoffice.inspections.start` → `startBackoffice`

### `inspections.templates.create` — new

- `backoffice.inspections.templates.create` → `createBackoffice`
- `backoffice.inspections.templates.store` → `createBackoffice`

### `inspections.templates.update` — new

- `backoffice.inspections.templates.edit` → `updateBackoffice`
- `backoffice.inspections.templates.update` → `updateBackoffice`

### `inspections.templates.view` — new

- `backoffice.inspections.templates.index` → `viewAnyBackoffice`

### `inspections.update` — existing

- `backoffice.inspections.edit` → `updateBackoffice`
- `backoffice.inspections.update` → `updateBackoffice`

### `inspections.validate` — new

- `backoffice.inspections.validate` → `validateBackoffice`

### `inspections.view` — existing

- `backoffice.cases.inspections.show` → `viewBackoffice`
- `backoffice.inspections.index` → `viewAnyBackoffice`
- `backoffice.inspections.show` → `viewBackoffice`

### `maintenance.assignments.cancel` — new

- `backoffice.maintenance.assignments.cancel` → `cancelBackoffice`

### `maintenance.assignments.create` — new

- `backoffice.maintenance.assignments.store` → `createBackoffice`

### `maintenance.attachments.create` — new

- `backoffice.maintenance.attachments.store` → `createBackoffice`

### `maintenance.attachments.download` — new

- `backoffice.maintenance.attachments.download` → `downloadBackoffice`

### `maintenance.categories.create` — new

- `backoffice.maintenance.categories.create` → `createBackoffice`
- `backoffice.maintenance.categories.store` → `createBackoffice`

### `maintenance.categories.delete` — new

- `backoffice.maintenance.categories.destroy` → `deleteBackoffice`

### `maintenance.categories.update` — new

- `backoffice.maintenance.categories.edit` → `updateBackoffice`
- `backoffice.maintenance.categories.update` → `updateBackoffice`

### `maintenance.categories.view` — new

- `backoffice.maintenance.categories.index` → `viewAnyBackoffice`

### `maintenance.costs.approve` — new

- `backoffice.maintenance.costs.approve` → `approveBackoffice`

### `maintenance.costs.create` — new

- `backoffice.maintenance.costs.store` → `createBackoffice`

### `maintenance.costs.reject` — new

- `backoffice.maintenance.costs.reject` → `rejectBackoffice`

### `maintenance.costs.view` — new

- `backoffice.maintenance.costs.index` → `viewAnyBackoffice`

### `maintenance.interventions.cancel` — new

- `backoffice.maintenance.interventions.cancel` → `cancelBackoffice`

### `maintenance.interventions.complete` — new

- `backoffice.maintenance.interventions.complete` → `completeBackoffice`

### `maintenance.interventions.create` — new

- `backoffice.maintenance.interventions.store` → `createBackoffice`

### `maintenance.interventions.start` — new

- `backoffice.maintenance.interventions.start` → `startBackoffice`

### `maintenance.interventions.view` — new

- `backoffice.maintenance.interventions.show` → `viewBackoffice`

### `maintenance.suppliers.create` — new

- `backoffice.maintenance.suppliers.create` → `createBackoffice`
- `backoffice.maintenance.suppliers.store` → `createBackoffice`

### `maintenance.suppliers.update` — new

- `backoffice.maintenance.suppliers.edit` → `updateBackoffice`
- `backoffice.maintenance.suppliers.update` → `updateBackoffice`

### `maintenance.suppliers.view` — new

- `backoffice.maintenance.suppliers.index` → `viewAnyBackoffice`
- `backoffice.maintenance.suppliers.show` → `viewBackoffice`

### `maintenance_requests.cancel` — new

- `backoffice.maintenance.requests.cancel` → `cancelBackoffice`

### `maintenance_requests.close` — new

- `backoffice.maintenance.requests.close` → `closeBackoffice`

### `maintenance_requests.create` — existing

- `backoffice.maintenance.requests.create` → `createBackoffice`
- `backoffice.maintenance.requests.store` → `createBackoffice`
- `maintenance-requests.create` → `createBackoffice`
- `maintenance-requests.store` → `createBackoffice`

### `maintenance_requests.delete` — existing

- `maintenance-requests.destroy` → `deleteBackoffice`

### `maintenance_requests.reject` — existing

- `backoffice.maintenance.requests.reject` → `rejectBackoffice`

### `maintenance_requests.resolve` — new

- `backoffice.maintenance.requests.resolve` → `resolveBackoffice`

### `maintenance_requests.review` — new

- `backoffice.maintenance.requests.review` → `reviewBackoffice`

### `maintenance_requests.schedule` — new

- `backoffice.maintenance.requests.schedule` → `scheduleBackoffice`

### `maintenance_requests.start` — new

- `backoffice.maintenance.requests.start` → `startBackoffice`

### `maintenance_requests.update` — existing

- `backoffice.maintenance.requests.edit` → `updateBackoffice`
- `backoffice.maintenance.requests.update` → `updateBackoffice`
- `maintenance-requests.edit` → `updateBackoffice`
- `maintenance-requests.update` → `updateBackoffice`

### `maintenance_requests.view` — existing

- `backoffice.cases.maintenance.show` → `viewBackoffice`
- `backoffice.maintenance.dashboard` → `viewAnyBackoffice`
- `backoffice.maintenance.index` → `viewAnyBackoffice`
- `backoffice.maintenance.requests.index` → `viewAnyBackoffice`
- `backoffice.maintenance.requests.show` → `viewBackoffice`
- `maintenance-requests.index` → `viewAnyBackoffice`
- `maintenance-requests.show` → `viewBackoffice`

### `reports.view_maintenance` — existing

- `backoffice.maintenance.cost-reports.index` → `viewMaintenanceReportBackoffice`

### `visits.availabilities.create` — new

- `backoffice.visit-availabilities.create` → `createBackoffice`
- `backoffice.visit-availabilities.store` → `createBackoffice`

### `visits.availabilities.delete` — new

- `backoffice.visit-availabilities.destroy` → `deleteBackoffice`

### `visits.availabilities.generate_slots` — new

- `backoffice.visit-availabilities.slots.generate` → `generateSlotsBackoffice`

### `visits.availabilities.update` — new

- `backoffice.visit-availabilities.edit` → `updateBackoffice`
- `backoffice.visit-availabilities.update` → `updateBackoffice`

### `visits.availabilities.view` — new

- `backoffice.visit-availabilities.index` → `viewAnyBackoffice`
- `backoffice.visit-availabilities.show` → `viewBackoffice`

### `visits.cancel` — new

- `backoffice.housing-visits.cancel` → `cancelBackoffice`

### `visits.complete` — new

- `backoffice.housing-visits.complete` → `completeBackoffice`

### `visits.confirm` — new

- `backoffice.housing-visits.confirm` → `confirmBackoffice`

### `visits.mark_no_show` — new

- `backoffice.housing-visits.no-show` → `markNoShowBackoffice`

### `visits.reject` — existing

- `backoffice.housing-visits.reject` → `rejectBackoffice`

### `visits.slots.block` — new

- `backoffice.visit-slots.block` → `blockBackoffice`

### `visits.slots.unblock` — new

- `backoffice.visit-slots.unblock` → `unblockBackoffice`

### `visits.slots.view` — new

- `backoffice.visit-slots.index` → `viewAnyBackoffice`

### `visits.view` — existing

- `backoffice.housing-visits.index` → `viewAnyBackoffice`
- `backoffice.housing-visits.show` → `viewBackoffice`

## Matriz completa de rotas

| Contexto | Rota | Tipo | Permission | Origem | Policy | Ability | Privado | Auditoria | MFA |
|---|---|---|---|---|---|---|---:|---|---:|
| agenda | `backoffice.agenda.index` | read | `agenda.view` | new | `App\Policies\AgendaPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.cases.inspections.show` | read | `inspections.view` | existing | `App\Policies\PropertyInspectionPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.attachments.download` | read | `inspections.attachments.download` | new | `App\Policies\PropertyInspectionAttachmentPolicy` | `downloadBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.attachments.store` | mutation | `inspections.attachments.create` | new | `App\Policies\PropertyInspectionAttachmentPolicy` | `createBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.cancel` | mutation | `inspections.cancel` | new | `App\Policies\PropertyInspectionPolicy` | `cancelBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.close` | mutation | `inspections.close` | new | `App\Policies\PropertyInspectionPolicy` | `closeBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.complete` | mutation | `inspections.complete` | new | `App\Policies\PropertyInspectionPolicy` | `completeBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.create` | read | `inspections.create` | existing | `App\Policies\PropertyInspectionPolicy` | `createBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.edit` | read | `inspections.update` | existing | `App\Policies\PropertyInspectionPolicy` | `updateBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.index` | read | `inspections.view` | existing | `App\Policies\PropertyInspectionPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.items.store` | mutation | `inspections.items.create` | new | `App\Policies\PropertyInspectionItemPolicy` | `createBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.items.update` | mutation | `inspections.items.update` | new | `App\Policies\PropertyInspectionItemPolicy` | `updateBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.reports.cancel` | mutation | `inspections.reports.cancel` | new | `App\Policies\PropertyInspectionReportPolicy` | `cancelBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.reports.download` | read | `inspections.reports.download` | new | `App\Policies\PropertyInspectionReportPolicy` | `downloadBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.reports.generate` | mutation | `inspections.reports.generate` | new | `App\Policies\PropertyInspectionReportPolicy` | `generateBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.reports.show` | read | `inspections.reports.view` | new | `App\Policies\PropertyInspectionReportPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.reports.validate` | mutation | `inspections.reports.validate` | new | `App\Policies\PropertyInspectionReportPolicy` | `validateBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.show` | read | `inspections.view` | existing | `App\Policies\PropertyInspectionPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| inspections | `backoffice.inspections.start` | mutation | `inspections.start` | new | `App\Policies\PropertyInspectionPolicy` | `startBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.store` | mutation | `inspections.create` | existing | `App\Policies\PropertyInspectionPolicy` | `createBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.templates.create` | read | `inspections.templates.create` | new | `App\Policies\InspectionChecklistTemplatePolicy` | `createBackoffice` | Não | recommended | Sim |
| inspections | `backoffice.inspections.templates.edit` | read | `inspections.templates.update` | new | `App\Policies\InspectionChecklistTemplatePolicy` | `updateBackoffice` | Não | recommended | Sim |
| inspections | `backoffice.inspections.templates.index` | read | `inspections.templates.view` | new | `App\Policies\InspectionChecklistTemplatePolicy` | `viewAnyBackoffice` | Não | recommended | Sim |
| inspections | `backoffice.inspections.templates.store` | mutation | `inspections.templates.create` | new | `App\Policies\InspectionChecklistTemplatePolicy` | `createBackoffice` | Não | required | Sim |
| inspections | `backoffice.inspections.templates.update` | mutation | `inspections.templates.update` | new | `App\Policies\InspectionChecklistTemplatePolicy` | `updateBackoffice` | Não | required | Sim |
| inspections | `backoffice.inspections.update` | mutation | `inspections.update` | existing | `App\Policies\PropertyInspectionPolicy` | `updateBackoffice` | Sim | required | Sim |
| inspections | `backoffice.inspections.validate` | mutation | `inspections.validate` | new | `App\Policies\PropertyInspectionPolicy` | `validateBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.cases.housing-units.show` | read | `housing_units.view` | existing | `App\Policies\HousingUnitPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.cases.maintenance.show` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.assignments.cancel` | mutation | `maintenance.assignments.cancel` | new | `App\Policies\MaintenanceAssignmentPolicy` | `cancelBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.assignments.store` | mutation | `maintenance.assignments.create` | new | `App\Policies\MaintenanceAssignmentPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.attachments.download` | read | `maintenance.attachments.download` | new | `App\Policies\MaintenanceAttachmentPolicy` | `downloadBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.attachments.store` | mutation | `maintenance.attachments.create` | new | `App\Policies\MaintenanceAttachmentPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.categories.create` | read | `maintenance.categories.create` | new | `App\Policies\MaintenanceCategoryPolicy` | `createBackoffice` | Não | recommended | Sim |
| maintenance | `backoffice.maintenance.categories.destroy` | mutation | `maintenance.categories.delete` | new | `App\Policies\MaintenanceCategoryPolicy` | `deleteBackoffice` | Não | required | Sim |
| maintenance | `backoffice.maintenance.categories.edit` | read | `maintenance.categories.update` | new | `App\Policies\MaintenanceCategoryPolicy` | `updateBackoffice` | Não | recommended | Sim |
| maintenance | `backoffice.maintenance.categories.index` | read | `maintenance.categories.view` | new | `App\Policies\MaintenanceCategoryPolicy` | `viewAnyBackoffice` | Não | recommended | Sim |
| maintenance | `backoffice.maintenance.categories.store` | mutation | `maintenance.categories.create` | new | `App\Policies\MaintenanceCategoryPolicy` | `createBackoffice` | Não | required | Sim |
| maintenance | `backoffice.maintenance.categories.update` | mutation | `maintenance.categories.update` | new | `App\Policies\MaintenanceCategoryPolicy` | `updateBackoffice` | Não | required | Sim |
| maintenance | `backoffice.maintenance.cost-reports.index` | read | `reports.view_maintenance` | existing | `App\Policies\MaintenanceCostPolicy` | `viewMaintenanceReportBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.costs.approve` | mutation | `maintenance.costs.approve` | new | `App\Policies\MaintenanceCostPolicy` | `approveBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.costs.index` | read | `maintenance.costs.view` | new | `App\Policies\MaintenanceCostPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.costs.reject` | mutation | `maintenance.costs.reject` | new | `App\Policies\MaintenanceCostPolicy` | `rejectBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.costs.store` | mutation | `maintenance.costs.create` | new | `App\Policies\MaintenanceCostPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.dashboard` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.index` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.interventions.cancel` | mutation | `maintenance.interventions.cancel` | new | `App\Policies\MaintenanceInterventionPolicy` | `cancelBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.interventions.complete` | mutation | `maintenance.interventions.complete` | new | `App\Policies\MaintenanceInterventionPolicy` | `completeBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.interventions.show` | read | `maintenance.interventions.view` | new | `App\Policies\MaintenanceInterventionPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.interventions.start` | mutation | `maintenance.interventions.start` | new | `App\Policies\MaintenanceInterventionPolicy` | `startBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.interventions.store` | mutation | `maintenance.interventions.create` | new | `App\Policies\MaintenanceInterventionPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.cancel` | mutation | `maintenance_requests.cancel` | new | `App\Policies\MaintenanceRequestPolicy` | `cancelBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.close` | mutation | `maintenance_requests.close` | new | `App\Policies\MaintenanceRequestPolicy` | `closeBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.create` | read | `maintenance_requests.create` | existing | `App\Policies\MaintenanceRequestPolicy` | `createBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.requests.edit` | read | `maintenance_requests.update` | existing | `App\Policies\MaintenanceRequestPolicy` | `updateBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.requests.index` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.requests.reject` | mutation | `maintenance_requests.reject` | existing | `App\Policies\MaintenanceRequestPolicy` | `rejectBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.resolve` | mutation | `maintenance_requests.resolve` | new | `App\Policies\MaintenanceRequestPolicy` | `resolveBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.review` | mutation | `maintenance_requests.review` | new | `App\Policies\MaintenanceRequestPolicy` | `reviewBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.schedule` | mutation | `maintenance_requests.schedule` | new | `App\Policies\MaintenanceRequestPolicy` | `scheduleBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.show` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.requests.start` | mutation | `maintenance_requests.start` | new | `App\Policies\MaintenanceRequestPolicy` | `startBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.store` | mutation | `maintenance_requests.create` | existing | `App\Policies\MaintenanceRequestPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.requests.update` | mutation | `maintenance_requests.update` | existing | `App\Policies\MaintenanceRequestPolicy` | `updateBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.suppliers.create` | read | `maintenance.suppliers.create` | new | `App\Policies\MaintenanceSupplierPolicy` | `createBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.suppliers.edit` | read | `maintenance.suppliers.update` | new | `App\Policies\MaintenanceSupplierPolicy` | `updateBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.suppliers.index` | read | `maintenance.suppliers.view` | new | `App\Policies\MaintenanceSupplierPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.suppliers.show` | read | `maintenance.suppliers.view` | new | `App\Policies\MaintenanceSupplierPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `backoffice.maintenance.suppliers.store` | mutation | `maintenance.suppliers.create` | new | `App\Policies\MaintenanceSupplierPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.maintenance.suppliers.update` | mutation | `maintenance.suppliers.update` | new | `App\Policies\MaintenanceSupplierPolicy` | `updateBackoffice` | Sim | required | Sim |
| maintenance | `backoffice.properties.technical-history` | read | `housing_units.view` | existing | `App\Policies\HousingUnitPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `maintenance-requests.create` | read | `maintenance_requests.create` | existing | `App\Policies\MaintenanceRequestPolicy` | `createBackoffice` | Sim | recommended | Sim |
| maintenance | `maintenance-requests.destroy` | mutation | `maintenance_requests.delete` | existing | `App\Policies\MaintenanceRequestPolicy` | `deleteBackoffice` | Sim | required | Sim |
| maintenance | `maintenance-requests.edit` | read | `maintenance_requests.update` | existing | `App\Policies\MaintenanceRequestPolicy` | `updateBackoffice` | Sim | recommended | Sim |
| maintenance | `maintenance-requests.index` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| maintenance | `maintenance-requests.show` | read | `maintenance_requests.view` | existing | `App\Policies\MaintenanceRequestPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| maintenance | `maintenance-requests.store` | mutation | `maintenance_requests.create` | existing | `App\Policies\MaintenanceRequestPolicy` | `createBackoffice` | Sim | required | Sim |
| maintenance | `maintenance-requests.update` | mutation | `maintenance_requests.update` | existing | `App\Policies\MaintenanceRequestPolicy` | `updateBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.cancel` | mutation | `visits.cancel` | new | `App\Policies\HousingVisitPolicy` | `cancelBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.complete` | mutation | `visits.complete` | new | `App\Policies\HousingVisitPolicy` | `completeBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.confirm` | mutation | `visits.confirm` | new | `App\Policies\HousingVisitPolicy` | `confirmBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.index` | read | `visits.view` | existing | `App\Policies\HousingVisitPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.housing-visits.no-show` | mutation | `visits.mark_no_show` | new | `App\Policies\HousingVisitPolicy` | `markNoShowBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.reject` | mutation | `visits.reject` | existing | `App\Policies\HousingVisitPolicy` | `rejectBackoffice` | Sim | required | Sim |
| visits | `backoffice.housing-visits.show` | read | `visits.view` | existing | `App\Policies\HousingVisitPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-availabilities.create` | read | `visits.availabilities.create` | new | `App\Policies\VisitAvailabilityPolicy` | `createBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-availabilities.destroy` | mutation | `visits.availabilities.delete` | new | `App\Policies\VisitAvailabilityPolicy` | `deleteBackoffice` | Sim | required | Sim |
| visits | `backoffice.visit-availabilities.edit` | read | `visits.availabilities.update` | new | `App\Policies\VisitAvailabilityPolicy` | `updateBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-availabilities.index` | read | `visits.availabilities.view` | new | `App\Policies\VisitAvailabilityPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-availabilities.show` | read | `visits.availabilities.view` | new | `App\Policies\VisitAvailabilityPolicy` | `viewBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-availabilities.slots.generate` | mutation | `visits.availabilities.generate_slots` | new | `App\Policies\VisitAvailabilityPolicy` | `generateSlotsBackoffice` | Sim | required | Sim |
| visits | `backoffice.visit-availabilities.store` | mutation | `visits.availabilities.create` | new | `App\Policies\VisitAvailabilityPolicy` | `createBackoffice` | Sim | required | Sim |
| visits | `backoffice.visit-availabilities.update` | mutation | `visits.availabilities.update` | new | `App\Policies\VisitAvailabilityPolicy` | `updateBackoffice` | Sim | required | Sim |
| visits | `backoffice.visit-slots.block` | mutation | `visits.slots.block` | new | `App\Policies\VisitSlotPolicy` | `blockBackoffice` | Sim | required | Sim |
| visits | `backoffice.visit-slots.index` | read | `visits.slots.view` | new | `App\Policies\VisitSlotPolicy` | `viewAnyBackoffice` | Sim | recommended | Sim |
| visits | `backoffice.visit-slots.unblock` | mutation | `visits.slots.unblock` | new | `App\Policies\VisitSlotPolicy` | `unblockBackoffice` | Sim | required | Sim |

## Downloads privados críticos

- `backoffice.inspections.attachments.download` → `inspections.attachments.download` → `downloadBackoffice`
- `backoffice.inspections.reports.download` → `inspections.reports.download` → `downloadBackoffice`
- `backoffice.maintenance.attachments.download` → `maintenance.attachments.download` → `downloadBackoffice`

Todos exigem scope municipal, MFA, storage privado e auditoria.

## Agenda

A Agenda utiliza:

```text
agenda.view
App\Policies\AgendaPolicy
viewBackoffice
```

Cada provider deve ainda validar a permission e o scope do respetivo domínio.

## Testes obrigatórios

```text
tests/Feature/Security/MaintenanceInspectionsVisitsPermissionRoutesTest.php
tests/Feature/Security/MaintenanceInspectionsVisitsMunicipalBoundaryTest.php
tests/Feature/Security/MaintenanceInspectionVisitWorkflowIntegrityTest.php
```

## Controlo de alterações

O conjunto das 96 rotas está congelado pelo manifesto.

Qualquer divergência deve ser documentada através de reconciliação explícita; não podem ser removidas, substituídas ou acrescentadas rotas silenciosamente.
