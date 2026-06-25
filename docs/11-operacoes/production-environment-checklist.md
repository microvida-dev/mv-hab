# Production Environment Checklist

## Objetivo

Checklist para preparar a MV HAB em ambiente municipal de producao dentro do ambito aceite, sem integracoes externas de assinatura digital, Autenticacao.gov/CMD ou gateways de pagamento.

## Variaveis esperadas

| Variavel | Valor esperado | Observacao |
| --- | --- | --- |
| `APP_ENV` | `production` | Definido apenas no ambiente alvo. |
| `APP_DEBUG` | `false` | Bloqueante se estiver ativo em producao. |
| `APP_URL` | `https://<dominio-municipal>` | Usar dominio final aprovado. |
| `APP_TIMEZONE` | `Europe/Lisbon` | Obrigatorio para atos administrativos e prazos. |
| `QUEUE_CONNECTION` | `database` ou `redis` | `sync` apenas local/testes. |
| `CACHE_STORE` | `file` ou `redis` | Deve ser consistente com a infraestrutura. |
| `SESSION_DRIVER` | `database` ou `redis` | Necessario para revogacao operacional de sessoes. |
| `LOG_CHANNEL` | `daily` | Evita ficheiros unicos demasiado grandes. |
| `MAIL_MAILER` | municipal ou sandbox | Envio real apenas apos configuracao aprovada. |

## Validacoes

- `.env` fica fora do Git e fora de backups versionados.
- `APP_KEY` existe no ambiente alvo, mas nunca e copiada para docs, testes ou artefactos QA.
- `storage:link` aponta apenas para `storage/app/public`.
- `storage/app/private` nao fica ligado a `public/`.
- `storage` e `bootstrap/cache` permitem escrita pelo utilizador do servidor aplicacional.
- `public/build` e reconstruido por `npm run build` ou promovido como artefacto de release.
- Feature flags de integracoes externas permanecem desativadas ou inexistentes.
- O mailer de producao so e ativado quando remetentes e templates estiverem aprovados.

## Comandos de verificacao

```bash
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
php artisan storage:link
php artisan optimize:clear
```

## Bloqueadores

- `APP_DEBUG=true` em producao.
- `.env` versionado.
- `storage/app/private` acessivel por URL publico.
- `QUEUE_CONNECTION=sync` em producao.
- falta de permissao de escrita em `storage` ou `bootstrap/cache`.
- variaveis de segredo expostas em docs, logs ou artefactos.
