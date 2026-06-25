# Observability and Logging Runbook

## Objetivo

Definir verificacoes de observabilidade para piloto municipal controlado.

## Health checks

Executar:

```bash
php artisan mvhab:operations:health
php artisan mvhab:operations:queue-health
php artisan schedule:list
php artisan queue:failed
```

Os comandos nao devem expor APP_KEY, passwords, tokens, cookies, paths privados ou dados pessoais.

## Sinais a monitorizar

- estado de DB;
- cache;
- queue connection;
- failed jobs;
- storage privado gravavel;
- canal de logs;
- rotas carregadas;
- scheduler disponivel;
- erros 500;
- acessos sensiveis;
- downloads/exportacoes;
- Work Tasks vencidas;
- SLA de tickets/visitas.

## Logs

- usar canal diario ou equivalente;
- logs nao devem conter documentos, NIF completo, tokens ou passwords;
- stack traces podem ser usados internamente, nunca em dossier externo sem sanitizacao;
- incidentes SEV1/SEV2 exigem preservacao de evidencia tecnica minimizada.

## Failed jobs

Usar `docs/11-operacoes/queue-failed-jobs-playbook.md`.

## Evidencia de drill

Guardar apenas:

- timestamp;
- comando;
- resultado PASS/WARN/FAIL;
- acao de mitigacao;
- referencia a incidente/Work Task.
