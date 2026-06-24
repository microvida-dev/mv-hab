# QA-29 Backup and Restore Runbook

## Objetivo

Definir um procedimento operacional seguro para proteger base de dados, documentos privados, configuração fora do Git e artefactos de build antes de deploy, staging ou demonstração municipal avançada.

Este runbook não autoriza a criação de dumps reais dentro do repositório.

## Âmbito

- Base de dados MySQL/MariaDB.
- Storage privado da aplicação.
- Ficheiro de ambiente mantido fora de Git.
- Artefactos frontend em `public/build` ou reconstrução com `npm run build`.
- Evidências operacionais em `storage/qa`.

## Responsáveis

| Responsabilidade | Perfil recomendado |
| --- | --- |
| Autorizar janela de backup | Responsável técnico municipal ou owner SaaS |
| Executar backup | DevOps/engenharia |
| Validar integridade | Engenharia + responsável funcional |
| Autorizar restore | Responsável técnico municipal |
| Guardar evidência | Engenharia |

## Retenção e proteção

- Reter pelo menos 7 cópias diárias e 4 cópias semanais para staging/pre-produção.
- Para produção, ajustar retenção à política municipal e contrato de serviço.
- Guardar cópias fora do servidor aplicacional.
- Encriptar cópias antes de transporte ou arquivo externo.
- Testar restore periodicamente em ambiente isolado.
- Nunca versionar dumps, documentos privados, logs ou ficheiros de ambiente reais.

## Backup de base de dados

Exemplo MySQL/MariaDB:

```bash
mkdir -p backups/db
mysqldump --single-transaction --routines --triggers --events --default-character-set=utf8mb4 -u <db_user> -p <database> > backups/db/mvhab-$(date +%F-%H%M).sql
sha256sum backups/db/mvhab-*.sql > backups/db/checksums.sha256
```

Compressão opcional:

```bash
gzip backups/db/mvhab-YYYY-MM-DD-HHMM.sql
```

Encriptação opcional:

```bash
gpg --symmetric --cipher-algo AES256 backups/db/mvhab-YYYY-MM-DD-HHMM.sql.gz
```

## Backup de storage privado

```bash
mkdir -p backups/storage
tar -czf backups/storage/mvhab-private-storage-$(date +%F-%H%M).tar.gz storage/app/private
sha256sum backups/storage/mvhab-private-storage-*.tar.gz > backups/storage/checksums.sha256
```

Validar que o arquivo não inclui:

- `storage/logs`;
- `storage/framework`;
- documentos de teste não necessários;
- dumps de base de dados dentro do storage.

## Backup de configuração fora do Git

```bash
mkdir -p backups/config
cp .env backups/config/env-$(date +%F-%H%M).backup
chmod 600 backups/config/env-*.backup
```

O ficheiro de ambiente deve ficar fora do repositório e só acessível a operadores autorizados.

## Build frontend

Preferência operacional:

```bash
npm ci
npm run build
```

Alternativa, quando o build é promovido como artefacto:

```bash
tar -czf backups/build/public-build-$(date +%F-%H%M).tar.gz public/build
```

## Restore de base de dados

Executar apenas em janela aprovada e com a aplicação em manutenção.

```bash
php artisan down
mysql --default-character-set=utf8mb4 -u <db_user> -p <database> < backups/db/mvhab-YYYY-MM-DD-HHMM.sql
php artisan optimize:clear
php artisan up
```

Se o ficheiro estiver comprimido:

```bash
gunzip -c backups/db/mvhab-YYYY-MM-DD-HHMM.sql.gz | mysql --default-character-set=utf8mb4 -u <db_user> -p <database>
```

## Restore de storage privado

```bash
php artisan down
tar -xzf backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz -C .
php artisan optimize:clear
php artisan up
```

Validar permissões:

```bash
find storage/app/private -type f -maxdepth 5 | head
php artisan route:list --except-vendor
```

## Validação pós-restore

Executar:

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --except-vendor
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA29
```

Smoke funcional:

- portal público carrega;
- login carrega;
- área reservada bloqueia guest;
- backoffice exige autenticação;
- documento privado não abre por URL direto;
- download autorizado funciona por controller;
- filas e scheduler retomam sem erro.

## Critérios de falha

Abortar restore se:

- checksum não confere;
- ficheiro de backup não corresponde ao ambiente esperado;
- storage privado não existe ou está incompleto;
- migrations esperadas divergem do código promovido;
- smoke tests falham;
- há erro de permissões em `storage` ou `bootstrap/cache`.
