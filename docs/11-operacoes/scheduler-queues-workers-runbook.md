# Scheduler, Queues and Workers Runbook

## Objetivo

Garantir que tarefas assicronas, IA documental, notificacoes internas, Work Tasks, SLA e manutencao operacional correm de forma controlada em producao municipal.

## Cron Laravel

Configurar um unico cron no servidor aplicacional:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Validar:

```bash
php artisan schedule:list
```

## Workers

Exemplo para worker manual:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Exemplo de restart seguro apos deploy:

```bash
php artisan queue:restart
```

## Supervisao

Em producao usar `systemd`, `supervisord` ou servico equivalente.

Regras:

- reiniciar workers apos deploy;
- limitar memoria e tempo por processo;
- escrever logs fora de `public/`;
- monitorizar jobs falhados;
- nao usar `queue:listen` em producao;
- nao usar `QUEUE_CONNECTION=sync` em producao.

## Failed jobs

Comandos operacionais:

```bash
php artisan queue:failed
php artisan queue:retry <failed_job_id>
php artisan queue:forget <failed_job_id>
php artisan queue:flush
```

`queue:flush` so deve ser usado com aprovacao operacional.

## Filas criticas

- Document Intelligence e OCR assistivo;
- validacoes documentais;
- notificacoes internas;
- Work Tasks e SLA;
- comunicacoes operacionais;
- marcacao de tarefas vencidas;
- faturacao/rendas administrativas quando configurado.

## Health check operacional

Antes de abrir trafego:

```bash
php artisan schedule:list
php artisan mvhab:operations:queue-health
php artisan queue:work --stop-when-empty
php artisan queue:restart
```

Na revisao QA-36/Phase 1 o estado e: sem tarefas agendadas atuais registadas em `schedule:list`. O cron deve continuar configurado para rotinas futuras, SLA/overdue e jobs operacionais que venham a ser ativados antes do piloto real.

## Regras de ambiente

- `QUEUE_CONNECTION=sync` e aceitavel apenas em local/testes.
- Staging/piloto deve usar `database` ou `redis`.
- Workers devem conseguir ler/gravar `storage/app/private` sem expor esse caminho publicamente.
- Jobs devem ser idempotentes quando podem ser repetidos por retry ou deploy.

## Bloqueadores

- scheduler ausente;
- workers parados;
- jobs falhados sem triagem;
- logs de workers com excecoes repetidas;
- filas a correr contra codigo antigo apos deploy;
- workers sem acesso a `storage/app/private`.
