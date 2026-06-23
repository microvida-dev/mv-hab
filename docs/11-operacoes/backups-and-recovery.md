# Backups and Recovery

## Antes de deploy

- Backup da base de dados.
- Backup de `.env` fora do Git.
- Backup de `storage/app/private` quando contem documentos reais.
- Registo do commit atualmente em producao.

## MySQL/MariaDB

```bash
mysqldump --single-transaction --routines --triggers --events -u USER -p DATABASE > backup.sql
```

## Rollback logico

1. Colocar aplicacao em manutencao.
2. Reverter para commit/tag anterior.
3. Reinstalar dependencias se necessario.
4. Executar migrations reversiveis apenas quando seguro.
5. Limpar e reconstruir caches.
6. Validar fluxos criticos.

