# Deploy Runbook Municipal

## 1. Pre-condicoes

- Branch/tag de release aprovado.
- Quality gate verde.
- Backup de base de dados concluido.
- Backup de storage privado concluido.
- `.env` validado fora do Git.
- Permissoes de `storage` e `bootstrap/cache` confirmadas.
- Workers e scheduler identificados.
- Janela de manutencao aprovada.

## 2. Backup obrigatorio

Seguir `docs/11-operacoes/backup-restore-runbook.md` antes de qualquer deploy.

## 3. Maintenance mode

```bash
php artisan down
```

## 4. Checkout de release/tag

```bash
git fetch origin
git checkout <release_ref>
git status --short --branch
```

## 5. Dependencias PHP

```bash
composer install --no-dev --optimize-autoloader
```

## 6. Dependencias frontend

```bash
npm ci
npm run build
```

## 7. Migracoes

```bash
php artisan migrate --force
```

Nunca usar `migrate:fresh` em dados reais.

## 8. Caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 9. Workers e scheduler

```bash
php artisan queue:restart
php artisan schedule:list
php artisan route:list --except-vendor
```

## 10. Smoke tests

Executar a checklist em `docs/11-operacoes/municipal-smoke-test-checklist.md`.

## 11. Sair de maintenance mode

```bash
php artisan up
```

## 12. Monitorizacao pos-deploy

- logs Laravel;
- logs webserver;
- jobs falhados;
- scheduler;
- erros de login;
- erros de acesso documental;
- tempo de resposta de portal publico e backoffice.

## 13. Criterios para abortar

Abortar e executar rollback se:

- `composer install` falha;
- `npm run build` falha;
- `migrate --force` falha;
- `route:list` falha;
- smoke tests falham;
- homepage/login falham;
- documentos privados ficam publicos;
- backoffice nao exige autenticacao;
- logs mostram erro critico repetido.
