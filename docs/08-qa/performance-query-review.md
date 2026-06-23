# Revisão de Performance e Queries

Data: 16/06/2026.

Esta revisão é estática e complementada pelo smoke test `tests/Feature/Performance/BasicLoadSmokeTest.php`. Não substitui teste de carga real.

| Área obrigatória | Rotas/zonas analisadas | Queries críticas identificadas | Possíveis N+1 | Indexes recomendados/verificados | Paginação/filtros | Riscos pendentes |
| --- | --- | --- | --- | --- | --- | --- |
| Listagem de candidaturas | `backoffice.applications.index`, `candidate.applications.index` | Candidaturas com programa, concurso, candidato e estado | Médio se faltarem `with()` nos detalhes | `applications.status`, `contest_id`, `user_id` | Manter paginação sempre | Volumetria real municipal |
| Detalhe de candidatura | `backoffice.applications.show`, `candidate.applications.show` | Snapshots, documentos, histórico, eligibility checks | Médio por relações profundas | Índices por `application_id` em históricos/documentos | Sem filtros pesados | Cuidado com snapshots grandes |
| Checklist documental | `candidate.documents.checklist` | Required documents por contexto e submissions | Médio por membros/rendimentos | `document_submissions.user_id`, `adhesion_registration_id`, `status` | Sem paginação, dataset por candidato | Checklist pode crescer com regras condicionais |
| Ranking | `backoffice.scoring.ranking-snapshots.*` | Scores, ranking entries e desempates | Médio se carregar detalhes por entrada | `ranking_entries.ranking_snapshot_id`, `rank_position` | Paginação obrigatória | Recalcular ranking em lotes se volume subir |
| Listas provisórias/definitivas | `public.results.*`, `backoffice.lists.*` | Entries e publications | Baixo/médio | `list_publications.status`, `publication_type`; entries por list id | Paginação pública recomendada | Publicações muito grandes |
| Relatórios/exportações | `backoffice.reports.*` | Agregações por módulo | Alto em exports nominais | Índices nos campos de estado/data por módulo | Exportações devem usar queue | Dataset real pode exigir materialização |
| Dashboard executivo | `backoffice.reports.executive` | Indicadores agregados | Médio | Índices por datas/status | Cache de indicadores recomendado | Recalcular em background |
| Pagamentos em atraso | `backoffice.finance.arrears.index`, contas financeiras | Prestação, mora, conta, contrato | Médio | `rent_installments.status/due_date`, `arrears.status` | Paginação e filtros por estado/data | Crescimento mensal contínuo |
| Pedidos de manutenção | `backoffice.maintenance.requests.index` | Pedidos, categorias, habitação, contrato | Médio | `maintenance_requests.status`, `housing_unit_id`, `lease_contract_id` | Paginação por estado/urgência | Anexos e histórico técnico podem pesar |
| Logs de auditoria | `backoffice.security.audit.*` | Audit events, access logs e sensitive logs | Alto por volume | `audit_events.event_code`, `occurred_at`; access logs por user/type | Paginação obrigatória | Retenção e arquivo em produção |

## Smoke query budgets Sprint 19

`BasicLoadSmokeTest` define orçamentos conservadores:

- portal concursos: até 80 queries;
- portal programas: até 80 queries;
- dashboard do candidato: até 160 queries;
- checklist documental: até 180 queries;
- relatórios backoffice: até 220 queries.

## Recomendações

- Aplicar eager loading em listagens com candidato, programa, concurso, estado e contadores.
- Evitar exportações síncronas para datasets nominais.
- Adicionar cache/materialização para dashboards executivos.
- Rever índices reais com `EXPLAIN` após dados de staging.
- Definir retenção/arquivo para logs de auditoria e access logs antes de produção.
