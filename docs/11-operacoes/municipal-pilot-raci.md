# Municipal Pilot RACI

## Objetivo

Definir responsabilidades para piloto municipal controlado com utilizadores reais.

| Area | Responsible | Accountable | Consulted | Informed |
| --- | --- | --- | --- | --- |
| Operacao diaria | Municipio | Responsavel municipal | Equipa tecnica | Candidatos afetados quando aplicavel |
| Backoffice/utilizadores | Municipio | Responsavel municipal | Equipa tecnica | Auditoria |
| Incidentes SEV1 | Equipa tecnica | Responsavel municipal | DPO/Juridico | Executivo municipal |
| Documento privado exposto | Equipa tecnica | Responsavel municipal | DPO/Juridico | Titulares quando aplicavel |
| RGPD pedidos do titular | Municipio/DPO | Responsavel municipal | Equipa tecnica | Titular |
| Exports sensiveis | Municipio | Responsavel municipal | DPO/Juridico | Auditoria |
| Restore/rollback | Equipa tecnica | Responsavel municipal | Municipio | Utilizadores afetados |
| Comunicacao externa | Municipio | Responsavel municipal | DPO/Juridico | Equipa tecnica |
| Dashboards/KPIs | Municipio | Responsavel municipal | Equipa tecnica | Executivo municipal |

## Guardrails

- DPO/Juridico deve validar bases legais e comunicacoes RGPD.
- Equipa tecnica nao decide conteudo administrativo ou juridico.
- Integracoes externas excluidas permanecem `Out of scope by municipal decision`.
