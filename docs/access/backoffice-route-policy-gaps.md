# Lacunas de Policies nas rotas backoffice

Foram detetadas nove rotas com Model route-bound inferido e sem Policy
descoberta pela convenção Laravel ou pelo Gate.

| Rota | Model | Sprint |
| --- | --- | --- |
| `backoffice.security.checklist-items.update` | `App\Models\SecurityChecklistItem` | 47A |
| `backoffice.teams.create` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.edit` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.index` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.members.remove` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.members.store` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.show` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.store` | `App\Models\MunicipalTeam` | 47A |
| `backoffice.teams.update` | `App\Models\MunicipalTeam` | 47A |

Antes de remover a role fixa deve confirmar-se se existe uma Policy com nome
não convencional ou autorização delegada noutro artefacto. Se não existir, a
47A deve criar/reforçar a Policy e abilities específicas, mantendo candidate
bloqueado, auditor read-only e scope municipal fail-closed.

O achado é estático e não substitui a leitura integral de controller, request,
service e testes na sprint de destino.
