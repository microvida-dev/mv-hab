# UX/UI-07 — Auditoria Terminológica

## Objetivo

Registar a normalização de linguagem visível realizada na UX/UI-07 para consolidar a MV HAB como plataforma municipal unificada, em português e com terminologia administrativa coerente.

## Termos Corrigidos

| Termo encontrado | Tradução adotada | Local corrigido |
| --- | --- | --- |
| Dashboard | Painel Principal / Painel | Dashboard principal, navegação histórica, relatórios e tarefas |
| Workspace | Espaço de Trabalho | Dashboard, páginas de workspace, pesquisa e comandos |
| Workspaces | Espaços de Trabalho | Dashboard, navegação e estados vazios |
| My Work | O Meu Trabalho | Componente de produtividade |
| Inbox Municipal | Caixa de Entrada Municipal | Dashboard, produtividade e notifications summary |
| Timeline | Cronologia | Case Workspace, processos administrativos, processos do candidato e relatórios |
| Work Task / Work Tasks | Tarefa / Tarefas | Case Workspace, timeline agregada, navegação, KPIs e produtividade |
| Insights | Indicadores | Simulador e navegação |
| Roles | Perfis | Backoffice de acessos e navegação |
| Audit trail | Auditoria | Backoffice de segurança |
| Templates | Modelos | Comunicações e vistorias |

## Termos Técnicos Preservados

Foram preservados por segurança técnica e compatibilidade:

- `workspace`, `workspace_key`, `work_task`, `dashboard`, `route`, `middleware`, `policy`, `permission`, `role`, `slug`, `uuid`;
- nomes de classes como `Dashboard*`, `Workspace*`, `ProcessTimeline*`;
- nomes de rotas, parâmetros, migrations, enums, permissões e chaves persistidas;
- siglas técnicas ou institucionais: `SLA`, `KPI`, `RBAC`, `RGPD`, `MFA`.

## Racional

Estes termos continuam a existir no código porque fazem parte de contratos internos, rotas, chaves de favoritos/recentes, permissões, testes estruturais ou modelos já persistidos. Alterá-los nesta sprint aumentaria risco sem benefício funcional para o utilizador.

## Riscos Conhecidos

- Alguns ecrãs de configuração técnica ainda usam termos internos em nomes de campos, por exemplo `dashboard_type`; estes não são prioritários por serem identificadores de configuração.
- A normalização textual foi feita nos principais ecrãs e serviços UX, mas a plataforma ainda deve ser auditada visualmente em fluxo real antes da apresentação municipal.
- Próxima sprint deve continuar a revisão de microcopy em formulários longos e páginas administrativas profundas.

## Recomendações UX/UI-08

- Consolidar um glossário de produto para todas as equipas.
- Criar lint documental opcional para termos visíveis críticos.
- Rever páginas de configuração avançada, relatórios e comunicações com equipa municipal.
- Alinhar nomes de módulos antigos com a taxonomia final de Espaços de Trabalho.
