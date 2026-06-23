# Sprint 0 — Fundacao documental, funcional e arquitetural

Estado: executada documentalmente nesta iteracao.

## Objetivo

Criar a base documental do produto MV HAB antes de qualquer evolucao funcional ou tecnica da aplicacao.

## Escopo permitido

- Documentar visao de produto.
- Documentar diferenca entre CRM atual e plataforma final.
- Documentar arquitetura tecnica atual e alvo.
- Documentar modelo de dados alvo.
- Documentar perfis de utilizador.
- Documentar matriz de permissoes.
- Documentar estrategia RGPD e auditoria.
- Documentar workflows futuros.
- Documentar roadmap Sprints 0 a 21.
- Documentar criterios gerais de aceitacao.
- Documentar estrategia futura de testes.
- Preparar Sprint 1 e Sprint 2 sem as executar.

## Escopo excluido

Nao implementar:

- candidatura;
- elegibilidade;
- documentacao avancada;
- classificacao;
- atribuicao;
- contratos avancados;
- pagamentos avancados;
- manutencao avancada.

Nao criar ou alterar:

- migrations;
- models;
- controllers;
- policies;
- requests;
- views;
- components;
- seeders;
- factories;
- routes;
- middlewares;
- services;
- jobs;
- notifications;
- testes funcionais;
- pacotes Composer;
- pacotes npm.

## Inspecao realizada

Resultados principais:

- Laravel 13.12.0.
- PHP CLI 8.5.6.
- Autenticacao Laravel Breeze com sessao.
- Frontend Blade, Tailwind CSS, Alpine.js e Vite.
- 79 rotas listadas por `php artisan route:list`.
- CRUDs existentes para munícipes, agregados, habitações, candidaturas, contratos, pagamentos, manutencao e documentos.
- Models existentes: `User`, `Citizen`, `Household`, `HousingUnit`, `HousingApplication`, `Contract`, `Payment`, `MaintenanceRequest`, `Document`.
- Policies nao encontradas.
- Tests existentes focados em Breeze/auth/profile e exemplos.
- `.env`, `.env.example`, `.npmrc` e `database/database.sqlite` presentes.
- Branch nao confirmavel porque o diretorio atual nao e reconhecido como repositorio Git.

## Entregaveis

- `docs/architecture/adr-0001-platform-foundation.md`
- `docs/architecture/technical-architecture.md`
- `docs/architecture/data-model-overview.md`
- `docs/product/product-vision.md`
- `docs/product/functional-requirements.md`
- `docs/product/user-roles.md`
- `docs/product/process-workflows.md`
- `docs/security/access-control-matrix.md`
- `docs/security/rgpd-and-audit-strategy.md`
- `docs/backlog/roadmap.md`
- `docs/backlog/sprint-0-foundation.md`
- `docs/backlog/sprint-1-foundation.md`
- `docs/backlog/sprint-2-foundation.md`
- `docs/qa/acceptance-criteria.md`
- `docs/qa/testing-strategy.md`

## Criterios de aceitacao

- A pasta `docs/` existe.
- Todos os documentos obrigatorios existem.
- A visao do produto esta documentada.
- A arquitetura tecnica esta documentada.
- O modelo de dados alvo esta documentado.
- Os workflows futuros estao documentados.
- A matriz de permissoes esta documentada.
- A estrategia RGPD esta documentada.
- O roadmap Sprints 0 a 21 existe.
- Sprint 1 esta preparada documentalmente.
- Sprint 2 esta preparada documentalmente.
- Nenhuma migration foi criada.
- Nenhum controller foi criado.
- Nenhum model foi criado.
- Nenhuma route aplicacional foi alterada.
- Nenhuma dependencia foi instalada.

## Excecao registada

A branch nao foi confirmada porque `git branch --show-current` falhou com erro de ausencia de repositorio Git. Antes da Sprint 1, o trabalho deve ocorrer dentro de repositorio Git inicializado ou no diretorio correto do repositorio.
