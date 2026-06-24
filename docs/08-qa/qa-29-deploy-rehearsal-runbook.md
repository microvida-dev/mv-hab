# QA-29 Deploy Rehearsal Runbook

## Objetivo

Definir o ensaio técnico de deploy para staging ou pré-produção municipal, com foco em reversibilidade, caches Laravel, migrations, assets frontend, filas, scheduler e smoke tests.

Este procedimento não cria novas funcionalidades e não substitui uma janela de release aprovada.

## Pré-condições

- Branch/tag de release aprovado.
- Quality gate local concluído.
- Backup de base de dados concluído.
- Backup de storage privado concluído.
- Ficheiro de ambiente validado fora do Git.
- Permissões de `storage` e `bootstrap/cache` confirmadas.
- Queue workers e scheduler identificados.
- Janela de manutenção aprovada.

## Comandos de ensaio

1. Entrar em maintenance mode:

```bash
php artisan down
```

2. Atualizar código para commit/tag aprovado:

```bash
git fetch origin
git checkout <release_ref>
git status --short --branch
```

3. Instalar dependências PHP para ambiente de execução:

```bash
composer install --no-dev --optimize-autoloader
```

4. Instalar dependências frontend e compilar assets:

```bash
npm ci
npm run build
```

5. Executar migrations:

```bash
php artisan migrate --force
```

6. Limpar caches antigas:

```bash
php artisan optimize:clear
```

7. Reconstruir caches Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

8. Reiniciar filas:

```bash
php artisan queue:restart
```

9. Validar rotas e estado básico:

```bash
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
```

10. Sair de maintenance mode:

```bash
php artisan up
```

11. Executar smoke tests manuais ou automatizados:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA29
```

## Smoke tests mínimos

- Homepage pública.
- Lista de concursos públicos.
- Detalhe de concurso.
- Mapa/oferta habitacional.
- Login.
- Dashboard autenticado.
- Área candidato.
- Upload/download documental por fluxo autorizado.
- Backoffice.
- Área inquilino.
- Tentativa de acesso não autorizado.

## Riscos a controlar

| Risco | Controlo |
| --- | --- |
| Migration não reversível | Rever migration antes de release e ter restore testado |
| Jobs pendentes | Pausar workers antes de deploy e reiniciar depois |
| Scheduler ativo durante deploy | Suspender ou garantir idempotência |
| Permissões incorretas | Validar `storage` e `bootstrap/cache` antes de abrir tráfego |
| Chave da aplicação incorreta | Confirmar valor do ambiente antes de caches |
| URL pública incorreta | Confirmar URL do ambiente antes de `config:cache` |
| Base de dados errada | Confirmar host, database e utilizador antes de migrate |
| Mailer incorreto | Usar sandbox em staging e validar remetentes |
| Queue worker antigo | Executar `queue:restart` após deploy |
| `route:cache` incompatível | Se falhar, abortar cache de rotas e documentar causa |

## Critérios para abortar deploy

Abortar e iniciar rollback se:

- `composer install` falha;
- `npm run build` falha;
- `php artisan migrate --force` falha;
- `php artisan route:list --except-vendor` falha;
- `phpunit --filter QA29` falha;
- homepage ou login não carregam;
- documentos privados ficam acessíveis por URL direto;
- backoffice não exige autenticação;
- logs mostram erro crítico repetido após abertura do tráfego.

## Pós-deploy

Registar:

- commit/tag promovido;
- hora de início e fim;
- operador;
- resultado de `php artisan about`;
- resultado de `migrate:status`;
- resultado dos smoke tests;
- incidentes e decisões;
- referência do backup usado para eventual restore.
