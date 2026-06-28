# UX/UI-09 — Workspaces Enterprise & Normalização Profunda de Ecrãs

## Resumo da Sprint

A UX/UI-09 generalizou o padrão de Case Workspace iniciado na UX-03 para os principais domínios operacionais da MV HAB, mantendo a implementação como camada de apresentação e composição read-only.

Não foram alteradas regras de negócio, estados oficiais, workflows, policies, permissões, scoring, elegibilidade, listas, contratos, rendas, pagamentos, auditoria ou RGPD.

## Workspaces Criados

Foram criadas rotas e apresentação Enterprise Case Workspace para:

- Concursos: `backoffice.cases.contests.show`
- Contratos: `backoffice.cases.contracts.show`
- Manutenção: `backoffice.cases.maintenance.show`
- Vistorias: `backoffice.cases.inspections.show`
- Reclamações: `backoffice.cases.complaints.show`
- Tickets/Apoio: `backoffice.cases.tickets.show`
- Fogos/Imóveis: `backoffice.cases.housing-units.show`
- Documentos: `backoffice.cases.documents.show`
- RGPD: `backoffice.cases.rgpd.show`
- Auditoria: `backoffice.cases.audit.show`

Cada workspace apresenta, quando aplicável, resumo, estado, prioridade, responsável, equipa, prazo/SLA, próxima ação sugestiva, cronologia, checklist, documentos, tarefas, comunicações, histórico, relações autorizadas e pesquisa contextual.

## Workspaces Reforçados

O Case Workspace de candidatura existente foi preservado e continua como referência UX-03.

A integração com UX-01/UX-02/UX-05 foi reforçada através de:

- resolução de workspace contextual para rotas `backoffice.cases.*`;
- breadcrumbs com o nó “Processo”;
- pesquisa universal a apontar para Case Workspaces enterprise quando a rota existe;
- preservação de favoritos/recentes e rotas legadas como links autorizados.

## Ecrãs Legados Normalizados

Os ecrãs tocados pela sprint foram normalizados através de uma view única:

- `resources/views/cases/enterprise/show.blade.php`
- componentes `resources/views/components/cases/enterprise/*`

As páginas usam o Design System Municipal da UX-04, componentes `x-ui.*`, badges, cards, estados vazios, tabs, foco acessível e linguagem municipal em português.

As rotas legadas não foram removidas. Quando autorizado, o workspace mostra ligação “Detalhe legado”.

## Services Criados

Foram adicionados services de composição read-only:

- `EnterpriseCaseWorkspaceService`
- `CaseTypeRegistry`
- `CaseResolver`
- `CaseTimelineAggregator`
- `CaseChecklistAggregator`
- `CaseRelationsService`
- `CaseDocumentSummaryService`
- `CaseTaskSummaryService`
- `CaseCommunicationSummaryService`
- `CaseNextActionResolver`
- `CaseSearchService`

`CaseAuthorizationService` e `CaseWorkspaceResolver` foram reforçados para suportar múltiplos tipos de caso sem duplicar a arquitetura UX-03.

## Componentes Criados

Foram criados componentes Blade enterprise:

- layout;
- header;
- summary panel;
- timeline;
- checklist;
- relations;
- documents;
- tasks;
- communications;
- history;
- next action;
- sidebar;
- tabs;
- empty state.

Foram também criados DTOs em `app/Data/Cases` para manter tipagem forte e componentes previsíveis.

## Relações Entre Casos

As relações são apresentadas apenas se o utilizador tiver permissão:

- Concurso → candidaturas, fogos, listas;
- Contrato → candidatura, fogo, concurso, vistorias, manutenção, financeiro autorizado;
- Fogo → contratos, concursos, manutenção, vistorias, visitas;
- Ticket/Reclamação → candidatura e relações operacionais autorizadas;
- Documento → candidatura, contrato, revisões e versões privadas;
- RGPD/Auditoria → agregações seguras e apenas leitura.

Relações não autorizadas são omitidas.

## Decisões de Segurança

- `candidate` não acede aos Case Workspaces de backoffice.
- Cada tipo de caso valida permissão configurada e policy `view` quando aplicável.
- Auditoria e RGPD são apresentados em modo consultivo.
- As ações são links autorizados para rotas existentes; nenhuma ação altera dados.
- A próxima ação é apenas sugestiva.
- Pesquisa universal ignora rotas inexistentes e respeita autorização.

## Decisões RGPD

- Documentos continuam privados por defeito.
- Não são expostos paths internos.
- NIFs e identificadores sensíveis são sanitizados nas descrições agregadas.
- Tickets e reclamações não mostram assunto/fundamento livre no cabeçalho.
- Valores financeiros de contrato só aparecem para perfis com permissão financeira.
- Eventos de auditoria não expõem payload bruto.

## Decisões de Performance

- Não foi usado `Model::all()`.
- Services usam `select()` explícito onde aplicável.
- As secções pesadas têm limites.
- Timeline limita Work Tasks e Audit Events.
- Relações usam contagens e não carregam anexos/documentos pesados.
- Views recebem DTOs/ViewModels já compostos, sem queries complexas em Blade.

## Decisões de Acessibilidade

- Tabs têm semântica e foco.
- Timeline e checklist têm representação textual.
- Estados vazios são explícitos.
- Botões/links têm texto claro.
- Layout enterprise adapta-se a grelha responsiva sem depender de sidebar global.

## Testes Executados

- `composer validate --strict`: PASS
- `php artisan optimize:clear`: PASS
- `./vendor/bin/pint --test`: PASS
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`: PASS, 628 testes, 3722 assertions
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter UX`: PASS, 120 testes, 610 assertions
- `--filter EnterpriseCase`: PASS
- `--filter CaseWorkspace`: PASS
- `--filter ContestCase`: PASS
- `--filter ContractCase`: PASS
- `--filter MaintenanceCase`: PASS
- `--filter InspectionCase`: PASS
- `--filter ComplaintCase`: PASS
- `--filter SupportTicketCase`: PASS
- `--filter Security`: PASS
- `--filter Rgpd`: PASS
- `--filter Accessibility`: PASS
- `php artisan migrate:status`: PASS
- `php artisan route:list --except-vendor`: PASS
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`: PASS, 0 erros
- `npm run build`: PASS
- `git diff --check`: PASS

## Evidências

Artefactos locais em `storage/qa`:

- `ux-09-composer.txt`
- `ux-09-optimize-clear.txt`
- `ux-09-pint.txt`
- `ux-09-phpunit.txt`
- `ux-09-ux-tests.txt`
- `ux-09-enterprise-case-tests.txt`
- `ux-09-case-workspace-tests.txt`
- `ux-09-contest-case-tests.txt`
- `ux-09-contract-case-tests.txt`
- `ux-09-maintenance-case-tests.txt`
- `ux-09-inspection-case-tests.txt`
- `ux-09-complaint-case-tests.txt`
- `ux-09-support-ticket-case-tests.txt`
- `ux-09-security-tests.txt`
- `ux-09-rgpd-tests.txt`
- `ux-09-accessibility-tests.txt`
- `ux-09-migrate-status.txt`
- `ux-09-route-list.txt`
- `ux-09-phpstan.txt`
- `ux-09-build.txt`
- `ux-09-diff-check.txt`

## Riscos Conhecidos

- A normalização profunda foi aplicada aos ecrãs e rotas tocados pela sprint; páginas legadas não relacionadas continuam a poder necessitar de refinamento visual em UX/UI-10.
- Alguns domínios têm dados parciais; nesses casos o workspace apresenta fallback controlado em vez de inventar informação.
- Ações contextuais continuam dependentes de rotas e permissões já existentes.
- Não foram criadas novas migrations.

## Limitações Aceites

- Reclamações, tickets, RGPD e auditoria são tratados de forma minimizada para evitar exposição de conteúdo sensível.
- Pesquisa contextual é local ao payload autorizado já carregado; não substitui a Pesquisa Universal UX-05.
- Não foi implementada assinatura digital, CMD, gateway de pagamento, reconciliação automática, IA generativa ou qualquer integração externa fora de âmbito.

## Recomendações para UX/UI-10

- Refinar visualmente páginas index dos domínios enterprise com os mesmos padrões do workspace.
- Evoluir ações contextuais para botões mais ricos apenas onde já existam fluxos autorizados.
- Adicionar analytics por tipo de caso com base nos services UX-08.
- Expandir cobertura visual com screenshots ou testes browser quando o ambiente de apresentação municipal estiver estabilizado.

## Decisão Final

PASS
