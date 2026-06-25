# QA-38 — Queues, Scheduler & Workers Operationalization

## Sumario executivo

Foi reforcada a operacionalizacao de filas e scheduler para staging/piloto municipal, mantendo `database` ou `redis` como configuracoes esperadas e tratando `sync` apenas como local/testes.

## Alteracoes

- Criacao de `QueueHealthService`.
- Criacao do comando `mvhab:operations:queue-health`.
- Reforco do runbook `docs/11-operacoes/scheduler-queues-workers-runbook.md`.
- Criacao de `docs/11-operacoes/queue-failed-jobs-playbook.md`.
- Testes QA38 e operations para runbooks, comando e health service.

## Regras validadas

- `schedule:list` deve executar.
- `queue:work --stop-when-empty` deve ser ensaiavel.
- `failed_jobs` e `job_batches` existem para queue database.
- `queue:restart`, `queue:failed`, `queue:retry` e `queue:forget` estao documentados.
- Sem tarefas agendadas atuais registadas; scheduler continua obrigatorio para futuras rotinas.

## Riscos residuais

- Em producao/piloto real ainda e necessario configurar service manager do servidor.
- Workers devem ser monitorizados fora da aplicacao.

## Evidencia

- `storage/qa/qa-38-tests.txt`: PASS, 2 testes, 13 assercoes.
- `storage/qa/phase-1-operations-tests.txt`: PASS, 12 testes, 100 assercoes.
- `storage/qa/phase-1-schedule-list.txt`: PASS; sem tarefas agendadas atuais.
- `storage/qa/phase-1-queue-worker.txt`: PASS; sem jobs pendentes/output.

## Decisao

PASS_WITH_ACCEPTED_RISK: scheduler sem tarefas agendadas atuais, aceite para demo/staging controlado.
