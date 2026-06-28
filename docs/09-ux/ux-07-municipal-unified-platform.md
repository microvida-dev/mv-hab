# UX/UI-07 — Plataforma Municipal Unificada

## Resumo da Sprint

A UX/UI-07 iniciou a Fase 2 de otimização visual e linguística, reforçando a perceção da MV HAB como uma plataforma municipal única. A intervenção focou textos visíveis, componentes de módulo, ecrãs herdados prioritários e testes de regressão, sem alterar regras de negócio, permissões, workflows, policies ou rotas públicas.

## Ficheiros Alterados

- `app/Services/UX/TerminologyService.php`
- `app/Services/UX/MunicipalLanguageService.php`
- `app/Services/UX/VisibleTextAuditService.php`
- `app/View/Components/UX/ModuleCard.php`
- `app/View/Components/UX/LegacyPageShell.php`
- `resources/views/components/ux/*`
- `resources/views/dashboard.blade.php`
- `resources/views/navigation/workspace.blade.php`
- `resources/views/components/navigation/*`
- `resources/views/components/productivity/*`
- `resources/views/components/search/universal-search.blade.php`
- `resources/views/cases/application/show.blade.php`
- ecrãs legados de tarefas, relatórios, processos, segurança, acessos e comunicações.

## Componentes Reutilizados

- `x-ui.page-header`
- `x-ui.section-header`
- `x-ui.card`
- `x-ui.status-badge`
- `x-ui.action-button`
- `x-ui.empty-state`
- componentes de navegação, dashboard, produtividade, pesquisa e case workspace criados nas UX-01 a UX-06.

## Componentes Criados

- `x-ux.module-card`
- `x-ux.module-grid`
- `x-ux.page-shell`
- `x-ux.legacy-table-shell`
- `x-ux.empty-state`

## Ecrãs Normalizados

- Painel Principal;
- Espaços de Trabalho;
- produtividade: O Meu Trabalho e Caixa de Entrada Municipal;
- Case Workspace da candidatura;
- cronologias de candidatura/processos;
- painel de tarefas;
- painéis operacional e executivo;
- segurança/auditoria;
- perfis e permissões;
- modelos de comunicação;
- configuração de painéis.

## Termos Corrigidos

Ver `docs/09-ux/ux-07-terminology-audit.md`.

## Termos Preservados por Razão Técnica

Foram preservados nomes internos de classes, rotas, permissões, enums, chaves persistidas e identificadores como `workspace`, `dashboard`, `work_task`, `role`, `permission`, `SLA`, `KPI`, `RBAC` e `RGPD`.

## Segurança/RGPD

- Não foram adicionadas fontes de dados pessoais.
- Os componentes novos recebem apenas payload já autorizado.
- Não houve alteração de policies, gates, middleware ou RBAC.
- Os testes reforçam que payloads sensíveis de notificações não aparecem no dashboard.

## Performance

- Não foram adicionadas queries em Blade.
- Os componentes são puramente apresentacionais.
- Services criados para UX/terminologia não consultam base de dados.

## Testes Executados

Evidências previstas em `storage/qa`:

- `ux-07-composer.txt`
- `ux-07-optimize-clear.txt`
- `ux-07-pint.txt`
- `ux-07-phpunit.txt`
- `ux-07-ux-tests.txt`
- `ux-07-municipal-platform-tests.txt`
- `ux-07-terminology-tests.txt`
- `ux-07-module-card-tests.txt`
- `ux-07-security-tests.txt`
- `ux-07-rgpd-tests.txt`
- `ux-07-accessibility-tests.txt`
- `ux-07-migrate-status.txt`
- `ux-07-route-list.txt`
- `ux-07-phpstan.txt`
- `ux-07-build.txt`
- `ux-07-diff-check.txt`

## Riscos Conhecidos

- Alguns nomes técnicos continuam visíveis em áreas administrativas profundas quando representam configuração interna; foram preservados para evitar quebrar contratos técnicos.
- A normalização visual foi incremental e deve continuar em UX/UI-08 para formulários extensos e tabelas avançadas.
- A validação visual manual em browser continua recomendada antes de demonstração municipal.

## Decisão Final

PASS_WITH_ACCEPTED_RISKS
