# QA-40 — Municipal Demo Data & Seeder Hardening

## Sumario executivo

Foi criado um seeder de piloto municipal para Alcanena que reutiliza seeders existentes, adiciona perfis operacionais ficticios, liga equipas municipais e cria dados demo de apoio, visitas, FAQ e Work Tasks.

## Alteracoes

- Criacao de `MunicipalPilotStagingSeeder`.
- Reforco de `CandidateSupportDemoSeeder` para usar utilizadores demo Alcanena e criar Work Tasks.
- Remocao de password trivial no seeder Alcanena.
- Criacao de `docs/11-operacoes/municipal-demo-data-guide.md`.
- Testes QA40, Seeder, Demo e Security para dados ficticios.

## Dados demo

- Municipio Alcanena.
- Programa e concurso demo.
- Fogos publicados e nao publicados.
- Utilizadores demo com dominios reservados.
- FAQ contextual.
- Ticket demo.
- Visita demo.
- Work Tasks demo.

## Guardrails

- Sem emails reais.
- Sem documentos reais.
- Sem passwords triviais.
- Sem storage privado versionado.

## Riscos residuais

- Dados demo nao substituem ensaio com scripts de migracao em ambiente isolado.
- Acesso demo deve ser feito por convite/reset seguro, nao por password fixa documentada.

## Evidencia

- `storage/qa/qa-40-tests.txt`: PASS, 2 testes, 39 assercoes.
- `storage/qa/phase-1-seeder-tests.txt`: PASS, 9 testes, 171 assercoes.
- `storage/qa/phase-1-security-tests.txt`: PASS, inclui privacidade de dados demo.
- Smoke demo Alcanena coberto por `AlcanenaDemoSmokeTest` no filtro Seeder.

## Decisao

PASS.
