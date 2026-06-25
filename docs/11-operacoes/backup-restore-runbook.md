# Backup and Restore Runbook

## Objetivo

Definir backup e restore seguros para base de dados, storage privado, configuracao fora do Git e artefactos de release da MV HAB.

## Regras obrigatorias

- Nunca guardar backup real no Git.
- Nunca guardar backup em `public/`.
- Nunca commitar dumps SQL, ficheiros `.dump`, `.tar`, `.zip` ou documentos reais.
- Encriptar backups com dados pessoais.
- Separar backup de base de dados, storage privado e configuracao.
- Validar restore periodicamente em ambiente isolado.

## Backup de base de dados

```bash
mkdir -p /secure/backups/db
mysqldump --single-transaction --routines --triggers --events -u USER -p DATABASE > /secure/backups/db/mvhab-YYYY-MM-DD-HHMM.sql
sha256sum /secure/backups/db/mvhab-YYYY-MM-DD-HHMM.sql > /secure/backups/db/mvhab-YYYY-MM-DD-HHMM.sql.sha256
```

## Backup de storage privado

```bash
mkdir -p /secure/backups/storage
tar -czf /secure/backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz storage/app/private
sha256sum /secure/backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz > /secure/backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz.sha256
```

## Backup de configuracao

```bash
mkdir -p /secure/backups/config
cp .env /secure/backups/config/env-YYYY-MM-DD-HHMM.backup
chmod 600 /secure/backups/config/env-YYYY-MM-DD-HHMM.backup
```

## Build frontend

Preferencial:

```bash
npm ci
npm run build
```

Opcionalmente promover `public/build` como artefacto de release sem dados pessoais.

## Retencao

| Ambiente | Retencao minima |
| --- | --- |
| Staging | 7 copias diarias e 4 semanais |
| Producao municipal | conforme politica municipal e contrato |

Backups devem ficar fora do servidor aplicacional sempre que possivel.

## Restore seguro

Executar apenas com janela aprovada:

```bash
php artisan down
mysql --default-character-set=utf8mb4 -u USER -p DATABASE < /secure/backups/db/mvhab-YYYY-MM-DD-HHMM.sql
tar -xzf /secure/backups/storage/mvhab-private-storage-YYYY-MM-DD-HHMM.tar.gz -C .
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --except-vendor
php artisan up
```

## Validacao pos-restore

- homepage;
- login;
- concursos;
- candidatura;
- documentos privados por controller autorizado;
- backoffice;
- listas;
- contratos;
- rendas manuais;
- area do inquilino;
- manutencao;
- vistorias;
- visitas;
- tickets;
- FAQ;
- auditoria;
- RGPD.

## Responsaveis

| Responsabilidade | Perfil |
| --- | --- |
| Autorizar backup/restore | responsavel municipal ou owner SaaS |
| Executar | DevOps/engenharia |
| Validar dados | tecnico municipal + engenharia |
| Registar evidencia | engenharia |
