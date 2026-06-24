# QA-29 Restore and Rollback Runbook

## Objetivo

Definir uma sequência segura para rollback de código, rollback de build, restore de base de dados, restore de storage privado e validação pós-incidente.

Usar este runbook quando um release, staging deploy ou ensaio de pré-produção falhar sem correção rápida e segura.

## Critérios para acionar rollback

Acionar rollback se ocorrer um destes eventos:

- indisponibilidade do portal público;
- login indisponível;
- erro crítico em backoffice;
- falha em migrations;
- perda de acesso a documentos privados;
- documento privado acessível sem autorização;
- alteração indevida de dados administrativos;
- erro financeiro ou contratual com impacto operacional;
- falha de smoke test crítica.

## Tempo máximo aceitável

Para staging/pre-produção:

- decisão de rollback até 15 minutos após deteção;
- reposição técnica até 60 minutos, salvo falha de infraestrutura externa;
- validação funcional mínima até 30 minutos após reposição.

Para produção municipal, estes tempos devem ser substituídos pelos SLA contratuais.

## Responsáveis

| Etapa | Responsável |
| --- | --- |
| Decisão de abortar release | Responsável técnico municipal + engenharia |
| Rollback de código | Engenharia/DevOps |
| Restore DB/storage | Engenharia/DevOps |
| Validação funcional | Produto + técnico municipal |
| Comunicação | Responsável operacional |

## Rollback de código

1. Entrar em maintenance mode:

```bash
php artisan down
```

2. Identificar commit/tag anterior:

```bash
git log --oneline -5
git tag --sort=-creatordate | head
```

3. Repor código:

```bash
git fetch origin
git checkout <previous_release_ref>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache
php artisan queue:restart
```

4. Abrir apenas após smoke tests:

```bash
php artisan route:list --except-vendor
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA29
php artisan up
```

## Rollback de build

Se o código não mudou mas o build frontend falhou:

```bash
php artisan down
rm -rf public/build
tar -xzf backups/build/public-build-YYYY-MM-DD-HHMM.tar.gz -C .
php artisan optimize:clear
php artisan up
```

Validar:

- assets carregam no browser;
- login e dashboards não têm erro visual crítico;
- `npm run build` deve ser corrigido antes da próxima promoção.

## Restore de base de dados

Executar apenas se o rollback de código não for suficiente ou se migrations/dados ficaram inconsistentes.

```bash
php artisan down
mysql --default-character-set=utf8mb4 -u <db_user> -p <database> < backups/db/mvhab-YYYY-MM-DD-HHMM.sql
php artisan optimize:clear
php artisan migrate:status
php artisan up
```

Validar depois:

- número de migrations esperado;
- concursos públicos carregam;
- áreas autenticadas bloqueiam guest;
- candidato/inquilino vê apenas os seus dados;
- documentos privados descarregam apenas por controller autorizado.

## Restore de storage privado

```bash
php artisan down
tar -xzf backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz -C .
php artisan optimize:clear
php artisan up
```

Validar depois:

- ficheiros privados existem no disco correto;
- permissões do sistema de ficheiros permitem leitura pela aplicação;
- URL direto em `/storage/...` não expõe ficheiro privado;
- download autorizado continua auditado.

## Smoke tests pós-rollback

Executar:

```bash
composer validate
php artisan optimize:clear
php artisan route:list --except-vendor
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA29
```

Validar manualmente:

- homepage;
- concursos públicos;
- detalhe de concurso;
- login;
- dashboard;
- candidatura;
- documentos;
- backoffice;
- contratos/rendas/inquilino;
- download autorizado;
- tentativa de acesso não autorizado.

## Critérios de sucesso

Rollback é aceite quando:

- aplicação sai de maintenance mode;
- smoke tests passam;
- logs não mostram erro crítico repetido;
- dados e documentos esperados estão acessíveis por fluxos autorizados;
- documentos privados não ficam públicos;
- responsável funcional confirma que o processo municipal crítico está operacional.

## Evidência

Registar em `storage/qa`:

- commit/tag anterior;
- commit/tag com falha;
- hora de início/fim;
- operador;
- comandos executados;
- resultado de smoke tests;
- decisão final;
- riscos residuais.
