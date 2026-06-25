# Rollback Runbook

## Objetivo

Definir rollback de codigo, build, base de dados e storage privado quando um deploy municipal falha.

## Criterios para rollback

- homepage ou login indisponiveis;
- backoffice inacessivel;
- migracao falha;
- documentos privados ficam publicos;
- area autenticada deixa de exigir login;
- smoke tests falham;
- erro critico repetido em logs;
- perda de acesso a documentos privados;
- comportamento financeiro/contratual incorreto.

## Procedimento

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

## Base de dados

Restore de base de dados so deve ocorrer quando rollback de codigo nao resolve ou quando migracoes/dados ficaram inconsistentes.

Nunca usar em dados reais:

```bash
php artisan migrate:fresh
```

## Storage privado

Restaurar storage privado apenas quando houver perda/corrupcao de ficheiros:

```bash
php artisan down
tar -xzf /secure/backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz -C .
php artisan optimize:clear
php artisan up
```

## Smoke pos-rollback

- homepage;
- concursos publicos;
- login;
- dashboard autenticado;
- area candidato;
- documentos privados;
- backoffice;
- contratos/rendas;
- area inquilino;
- visitas/tickets/FAQ;
- tentativa de acesso nao autorizado.

## Tempos alvo

| Ambiente | Decisao | Reposicao |
| --- | --- | --- |
| Staging | ate 15 minutos | ate 60 minutos |
| Producao municipal | conforme SLA aprovado | conforme SLA aprovado |

## Evidencia

Registar em `storage/qa` ou ferramenta operacional:

- commit/tag com falha;
- commit/tag restaurado;
- hora de inicio/fim;
- operador;
- comandos executados;
- resultado de smoke tests;
- decisao final.
