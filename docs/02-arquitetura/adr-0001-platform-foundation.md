# ADR-0001: Fundacao da plataforma MV HAB

Data: 2026-06-10

Estado: Proposto para Sprint 0

## Contexto

O projeto atual e um CRM Laravel para gestao municipal de habitacao publica, com modulos CRUD para munícipes, agregados, habitacoes, candidaturas, contratos, pagamentos, manutencao e documentos. A plataforma alvo MV HAB deve evoluir para uma plataforma processual municipal completa para Arrendamento Acessivel, cobrindo desde o registo de adesao ate ao encerramento do processo, com rastreabilidade, RGPD, controlo de acessos e capacidade de auditoria.

Esta ADR limita-se a fundacao documental e arquitetural. Nao autoriza implementacao aplicacional nesta sprint.

## Decisao

Adotar uma evolucao incremental sobre a base Laravel existente, mantendo o CRM atual operacional enquanto se prepara a arquitetura da plataforma processual final.

A fundacao da plataforma sera orientada por:

- Laravel como backend monolitico modular inicial.
- Blade, Tailwind CSS, Alpine.js e Vite como stack frontend atual.
- Autenticacao por sessao Laravel Breeze como base a consolidar.
- Separacao futura entre modulos administrativos, portal do candidato e area de auditoria.
- Modelo de autorizacao por roles e permissions, com policies por dominio.
- Auditoria obrigatoria para acesso, alteracao, decisao administrativa, publicacao e exportacao.
- RGPD por desenho, incluindo minimizacao, retencao, consentimentos, pedidos dos titulares e registo de finalidades.
- Workflows explicitos por estado para candidaturas, documentacao, elegibilidade, classificacao, reclamacoes, atribuicao, contratos, pagamentos e manutencao.

## Alternativas consideradas

### Reescrita completa

Rejeitada nesta fase. A aplicacao atual ja contem entidades e ecras uteis para CRM municipal. Uma reescrita aumentaria risco, custo e tempo antes de existir validacao funcional do municipio.

### Evolucao sem documentacao formal

Rejeitada. O dominio envolve dados pessoais sensiveis, decisoes administrativas, prazos legais e auditabilidade. Avancar sem fundacao documental criaria risco tecnico, operacional e juridico.

### Microservicos desde o inicio

Rejeitada para a fundacao. O dominio ainda precisa de estabilizacao funcional e processual. A modularizacao logica dentro de Laravel e suficiente para a fase inicial.

## Consequencias

- Sprint 0 produz documentacao de produto, arquitetura, seguranca, backlog e QA.
- Sprint 1 deve tratar apenas fundacao tecnica Laravel validada.
- Sprint 2 deve tratar UX/UI e design system validado.
- Funcionalidades de candidatura, elegibilidade, classificacao, atribuicao, contratos, pagamentos e manutencao avancada ficam fora desta execucao.
- Qualquer alteracao futura em modelos, migrations, routes, controllers, policies ou dependencias deve ter validacao explicita.

## Guardrails

- Nao alterar `.env`.
- Nao introduzir APP_KEY, tokens, passwords reais ou dados pessoais reais.
- Nao executar `migrate`, `migrate:fresh`, `db:seed` ou `npm run build` nesta fase.
- Nao criar novas migrations, models, controllers, routes, views, policies, services, jobs, notifications, factories, seeders ou testes funcionais nesta sprint.
- Restringir esta execucao a `docs/`.

## Criterio de revisao

Esta ADR deve ser revista antes da Sprint 1 para confirmar:

- Branch/repo de trabalho correto.
- Responsavel municipal pelo produto.
- Responsavel tecnico pela arquitetura.
- Lista de dados pessoais e documentos sensiveis.
- Modelo de autorizacao pretendido.
- Estrategia de ambientes e backups.
