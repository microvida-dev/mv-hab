# Deploy Prompt Template

Executar deploy seguro da plataforma MV HAB.

## Antes de iniciar

- Confirmar branch alvo.
- Confirmar ambiente: local, staging ou producao.
- Confirmar backup de base de dados.
- Confirmar se ha migrations.
- Confirmar se ha alteracoes frontend.

## Ordem segura

1. `git status`
2. `git fetch origin`
3. `git checkout main`
4. `git pull --ff-only origin main`
5. `composer install --no-dev --optimize-autoloader`
6. `npm ci`
7. `npm run build`
8. `php artisan down` quando necessario
9. `php artisan migrate --force`
10. `php artisan optimize:clear`
11. `php artisan config:cache`
12. `php artisan route:cache`
13. `php artisan view:cache`
14. `php artisan up`
15. Smoke tests

## Validacao final

- Login.
- Area candidato.
- Backoffice.
- Upload/download documental privado.
- Concurso publico.
- Submissao candidatura.
- Listas e ranking.
- Logs sem erro critico.

