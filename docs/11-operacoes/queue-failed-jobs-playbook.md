# Queue Failed Jobs Playbook

## Objetivo

Definir resposta operacional para jobs falhados em staging/piloto municipal sem expor segredos nem dados pessoais.

## Comandos

```bash
php artisan queue:failed
php artisan queue:retry <failed_job_id>
php artisan queue:forget <failed_job_id>
php artisan queue:flush
php artisan queue:restart
```

`queue:flush` requer aprovacao operacional e nao deve ser usado para esconder incidentes.

## Triagem

1. Confirmar ambiente, commit e hora do incidente.
2. Identificar job, fila, excecao e numero de tentativas.
3. Verificar se o job e idempotente.
4. Corrigir configuracao ou bug antes de retry.
5. Executar retry apenas quando a causa estiver controlada.
6. Registar decisao em Work Task ou incidente operacional.

## Guardrails

- Nao copiar payloads com PII para tickets externos.
- Nao publicar stack traces em dossiers municipais.
- Nao apagar failed jobs sem decisao registada.
- Nao correr workers com codigo antigo apos deploy.
- Workers precisam de acesso a storage privado, sem o tornar publico.

## Evidencia minima

- output sanitizado de `queue:failed`;
- decisao de retry/forget;
- resultado do retry;
- verificacao posterior de logs;
- smoke test do fluxo afetado.
