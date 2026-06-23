# Sprint 2 — Fundacao UX/UI e design system

Estado: executada como fundacao UX/UI inicial.

## Objetivo

Definir a fundacao de experiencia, interface e design system para a plataforma MV HAB, respeitando o contexto municipal, a densidade informacional do backoffice e a clareza necessaria para candidatos.

## Pre-condicoes obrigatorias

- Sprint 1 concluida ou validada.
- Roles e modulos base clarificados.
- Mapa de navegacao aprovado.
- Principais fluxos de backoffice e candidato priorizados.
- Decisao sobre manter Blade/Tailwind/Alpine ou introduzir outra stack.

## Escopo candidato

- Definir layout de backoffice municipal.
- Definir layout de area do candidato.
- Definir navegacao por perfis.
- Definir componentes base: tabelas, filtros, estados, badges, formularios, cards operacionais, timelines e modais.
- Definir tokens visuais: cor, tipografia, espacamento e estados.
- Definir padroes de acessibilidade.
- Definir linguagem de estados processuais.
- Definir padroes para erros, alertas e confirmacoes.

## Fora de escopo

- Implementar portal publico completo.
- Implementar candidatura.
- Implementar elegibilidade.
- Implementar dashboards executivos finais.
- Reescrever a app frontend sem decisao tecnica.

## Principios UX

- Backoffice deve ser denso, claro e orientado a tarefas.
- Candidato deve ter linguagem simples e estados transparentes.
- Dados sensiveis nao devem aparecer em ecras sem necessidade.
- Acoes destrutivas devem exigir confirmacao e permissao.
- Estados processuais devem ser consistentes em toda a plataforma.

## Criterios de saida propostos

- Mapa de navegacao por role.
- Inventario de componentes.
- Design system inicial.
- Wireframes dos principais fluxos.
- Guia de linguagem.
- Checklist de acessibilidade.
- Plano de implementacao para Sprints 3 a 5.

## Implementado nesta execucao

- Design tokens iniciais em Tailwind:
  - paleta `civic`;
  - paleta `ink`;
  - paleta `signal`;
  - sombra `surface`.
- Camada CSS de componentes:
  - `mv-surface`;
  - `mv-button-primary`;
  - `mv-button-secondary`;
  - `mv-button-danger`;
  - `mv-table`.
- Base de foco acessivel para links, botoes e campos.
- Suporte `x-cloak` para Alpine.
- Componente `ui-icon` com icones SVG internos para uso operacional.
- Navegacao lateral responsiva organizada por grupos:
  - Operacao;
  - Atendimento;
  - Patrimonio.
- Shell principal atualizado para layout de backoffice municipal.
- Componentes existentes alinhados com o design system:
  - `nav-link`;
  - `responsive-nav-link`;
  - `primary-button`;
  - `secondary-button`;
  - `danger-button`;
  - `text-input`;
  - `input-label`;
  - `flash-message`;
  - `stat-card`.
- Dashboard reformulado com:
  - cabecalho operacional;
  - metricas com icones;
  - acoes rapidas;
  - filas de trabalho;
  - nota de estado da fundacao tecnica.

## Nao implementado nesta execucao

- Nenhum portal publico novo.
- Nenhuma area pessoal do candidato.
- Nenhum fluxo de candidatura novo.
- Nenhuma elegibilidade.
- Nenhuma classificacao.
- Nenhuma atribuicao.
- Nenhuma alteracao de controllers, rotas ou regras de negocio.
- Nenhuma nova dependencia npm ou Composer.

## Validacao executada

- `npm run build`: passou.
- `php artisan test`: passou, 29 testes, 75 assertions.
- Browser interno aberto em `http://127.0.0.1:8001/login`.
- Login validado com utilizador local de demonstracao.
- Dashboard validado por DOM no browser interno:
  - marca `MV HAB` visivel;
  - navegacao lateral visivel;
  - links principais visiveis;
  - metricas visiveis;
  - acoes rapidas visiveis;
  - nota de fundacao tecnica visivel.

## Limitacao registada

A captura de screenshot no browser interno expirou em `Page.captureScreenshot`. A validacao visual foi complementada por build, testes e verificacao DOM do dashboard renderizado.

## Pendencias antes da Sprint 3

- Decidir se o portal publico reutiliza Blade/Tailwind ou recebe layout proprio.
- Definir paginas publicas de programas e concursos.
- Confirmar linguagem publica com o municipio.
- Confirmar acessibilidade minima por viewport apos estabilizar servidor local.
- Rever todos os ecras CRUD para substituir estilos inline por componentes do design system.
