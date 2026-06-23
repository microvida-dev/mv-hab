# MV HAB Documentation Index

Este diretorio organiza a documentacao da plataforma MV HAB para quatro usos principais:

1. Criar prompts e sprints com contexto completo.
2. Preparar auditorias tecnicas, funcionais, RGPD e de qualidade.
3. Executar deploy local, staging e producao com menor risco.
4. Manter o repositorio Git limpo, reprodutivel e alinhado com Laravel.

## Mapa de pastas

| Pasta | Conteudo |
| --- | --- |
| `00-fontes` | PDFs, imagens, regulamento, requisitos e manuais externos. |
| `01-produto` | Requisitos funcionais, roles, fluxos, portal publico, candidato, inquilino e backoffice. |
| `02-arquitetura` | Arquitetura tecnica, ADRs, modelo de dados e fronteiras de dominio. |
| `03-regulamento-alcanena` | Regras municipais convertidas em requisitos implementaveis. |
| `04-sprints` | Roadmap, sprints, backlog tecnico e prompts historicos por sprint. |
| `05-prompts` | Prompts mestre reutilizaveis para auditoria, implementacao, QA e deploy. |
| `06-deploy` | Procedimentos para local, staging, producao, rollback e checks pos-deploy. |
| `07-git` | Estrategia Git, branches, commits, merges, tags e diretorio de producao. |
| `08-qa` | Quality gates, PHPStan, PHPUnit, Pint, matriz de testes e pre-release. |
| `09-seguranca-rgpd` | Policies, matriz de acessos, documentos privados, auditoria e RGPD. |
| `10-demo-apresentacao` | Roteiro, dados ficticios, readiness report e apresentacao Alcanena. |
| `11-operacoes` | Backups, filas, scheduler, logs, monitorizacao e recuperacao. |
| `12-integracoes` | IA documental, OCR, CMD, Autenticacao.gov, pagamentos, email/SMS e APIs externas. |
| `13-dados-demo` | Seeds, fixtures e dados anonimizados para demonstracao. |

## Ordem recomendada para nova sessao de trabalho

1. Ler `docs/README.md`.
2. Ler `.codex/context/project-summary.md`.
3. Ler `.codex/context/current-state.md`.
4. Ler `docs/08-qa/enterprise-quality-gate.md`.
5. Ler a sprint alvo em `docs/04-sprints`.
6. Executar os comandos de QA antes e depois de qualquer alteracao critica.

