# Staging Rollback Validation Checklist

## Escopo

Checklist para ensaiar rollback em staging municipal controlado.

## Pre-condicoes

- [ ] Ambiente nao produtivo confirmado.
- [ ] Commit atual registado.
- [ ] `<previous_release_ref>` identificado.
- [ ] Backup DB e storage privado disponiveis fora do Git.
- [ ] Criterios para abortar conhecidos pela equipa.

## Procedimento base

```bash
php artisan down
git fetch origin
git checkout <previous_release_ref>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan route:list --except-vendor
php artisan up
```

Nunca usar em dados reais:

```bash
php artisan migrate:fresh
```

## Smoke pos-rollback

- [ ] homepage.
- [ ] concursos publicos.
- [ ] login.
- [ ] area candidato.
- [ ] documentos privados.
- [ ] backoffice.
- [ ] area inquilino.
- [ ] visitas.
- [ ] tickets.
- [ ] FAQ.
- [ ] tentativa de acesso nao autorizado.

## criterios para abortar

- `composer install` falha.
- `npm run build` falha.
- `route:list` falha.
- documentos privados ficam publicos.
- backoffice nao exige autenticacao.
- smoke falha.
- logs mostram erro critico repetido.

## Evidencia

Registar commit original, commit restaurado, hora, operador, comandos executados e resultado de smoke em `storage/qa/phase-2-dr-rehearsal.txt`.
