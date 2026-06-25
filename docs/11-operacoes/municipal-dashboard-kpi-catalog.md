# Municipal Dashboard KPI Catalog

## Objetivo

Catalogo operacional para piloto municipal controlado com utilizadores reais. Os KPIs sao agregados, minimizados e destinados a decisao municipal, acompanhamento de SLA e supervisao de processos.

## Guardrails

- dashboards exigem autenticacao backoffice;
- candidato e inquilino nao acedem a dashboards municipais;
- exports exigem permissao e auditoria;
- documentos privados nunca sao expostos;
- NIF, documentos, emails pessoais, moradas privadas e rendimentos individuais nao entram em KPIs;
- dados financeiros aparecem apenas agregados para perfis autorizados;
- filtros devem usar queries paginadas, agregacoes SQL ou cache curta.

## KPIs minimos

| KPI | Fonte | Sensibilidade | Filtros | Observacoes |
| --- | --- | --- | --- | --- |
| Concursos ativos/publicados | contests | Interno | programa, periodo | Estado `published`. |
| Candidaturas por estado | applications | Restrito | concurso, programa, estado, periodo | Agregado por estado. |
| Candidaturas por tipologia | applications/current_housing_situations | Agregado | concurso, programa, tipologia | Sem identificadores pessoais. |
| Candidaturas por freguesia | applications/current_housing_situations | Agregado | concurso, programa, freguesia | Usar freguesia, nao morada. |
| Documentos pendentes | document_submissions | Sensivel agregado | concurso, programa, estado | Sem ficheiros, paths ou nomes. |
| Pedidos de aperfeicoamento | correction_requests | Restrito | concurso, estado, periodo | Acompanhamento processual. |
| Visitas por estado | housing_visits | Agregado | estado, periodo, equipa | Sem contactos pessoais. |
| Tickets por estado | support_tickets | Agregado | categoria, estado, equipa | Notas internas nao expostas. |
| Work Tasks por equipa | work_tasks | Interno | equipa, responsavel, SLA | Carga operacional. |
| Work Tasks por SLA | work_tasks | Interno | equipa, periodo | Vencidas, proximas e ativas com prazo. |
| Listas por tipo | list_publications | Interno | tipo, estado, periodo | Provisorias/definitivas. |
| Contratos ativos | contracts | Restrito | estado, periodo | Sem dados do inquilino. |
| Rendas manuais por estado | rent_installments | Financeiro agregado | estado, periodo | Sem valores individuais em dashboards gerais. |
| Manutencoes por estado | maintenance_requests | Agregado | estado, prioridade, periodo | Sem moradas privadas. |
| Vistorias por estado | property_inspections | Agregado | estado, periodo | Sem relatorios privados. |
| Pedidos RGPD por estado | data_subject_requests | RGPD agregado | estado, periodo | Sem dados do titular. |
| Acoes criticas de auditoria | audit_events | Auditoria agregada | categoria, severidade, periodo | Para auditoria e seguranca. |

## Filtros suportados

- programa;
- concurso;
- estado;
- tipologia;
- freguesia;
- periodo;
- equipa;
- responsavel;
- SLA.

## Performance

- nao usar `all()` em tabelas operacionais;
- usar `count`, `group by`, `withCount`, eager loading e paginacao;
- usar cache curta apenas para dados publicaveis ou agregados internos;
- exports grandes devem usar chunking/job;
- qualquer relatorio nominal deve ser tratado como sensivel.
