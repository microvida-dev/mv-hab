# Runbook de recuperação — Onboarding Municipal

## Matriz de falhas

| Situação | Estado esperado | Ação |
|---|---|---|
| Preview com conflito | zero writes | corrigir a fonte ou resolver o conflito; repetir preview |
| Falha antes da criação do Município | run `failed` ou nenhum run | analisar `failure_code`; repetir apenas com o mesmo fingerprint |
| Falha durante role/admin/assignment | Município e utilizador revertidos | preservar o ledger de falha; corrigir e repetir |
| Onboarding concluído e dispatch falha | domínio preservado; convite `failed` | corrigir queue/transport e reenfileirar |
| Job falha após retries | `failed_jobs` e convite `failed` | rever erro, corrigir causa e executar retry controlado |
| Convite expirado | domínio preservado; convite `expired` | criar/reutilizar fluxo autorizado de reenvio; não recriar Município |
| Password definida | convite `consumed` | não reenviar automaticamente |
| Catálogo falha | onboarding preservado | corrigir conflito e repetir apenas o comando do catálogo |
| Regressão após onboarding concluído | dados persistidos | privilegiar forward fix; não eliminar Município/admin automaticamente |

## Diagnóstico

Consultar apenas IDs técnicos e estados:

```sql
SELECT id, operation_id, municipality_code, status, attempt_count,
       failure_code, started_at, completed_at, failed_at
FROM municipality_onboarding_runs
ORDER BY id DESC;

SELECT id, onboarding_run_id, user_id, status, attempt_count,
       queued_at, sent_at, failed_at, consumed_at, expires_at,
       last_failure_code
FROM municipal_administrator_invitations
ORDER BY id DESC;
```

Não copiar emails, nomes, NIPC, tokens ou conteúdo de notificações para tickets ou logs de recuperação.

## Retry do worker

Antes do retry:

```bash
systemctl is-active mvhab-queue-default.service
/opt/plesk/php/8.4/bin/php artisan queue:failed
```

Depois de corrigida a causa, usar o mecanismo operacional aprovado para retry do Job. Não editar diretamente o estado do convite na base de dados.

## Onboarding falhado

A repetição é permitida apenas quando:

- o run está `failed`;
- o fingerprint coincide;
- não existem Município, administrador, role ou invitation parciais;
- o ator continua global, autorizado e com MFA confirmado.

Dados divergentes exigem investigação e nova decisão operacional; não são reconciliados silenciosamente.

## Rollback de release

Antes de mudar o symlink para uma release anterior, confirmar que o código anterior reconhece as migrations do onboarding. Depois de dados reais criados, rollback de código não implica rollback destrutivo de dados.

## Ações proibidas

- executar `DatabaseSeeder`;
- executar `SystemAccessSeeder` sem plano aprovado;
- executar `DemoAlcanenaAffordableRentSeeder`;
- atribuir Município ao operador global;
- atribuir permissions diretamente ao utilizador;
- inserir roles ou assignments por SQL;
- eliminar o run de auditoria;
- eliminar Município/admin para resolver uma falha de email;
- ativar entitlements como parte do retry.
