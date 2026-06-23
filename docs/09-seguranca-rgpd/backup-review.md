# Revisão de Backups

## Estado Sprint 18

A Sprint 18 criou `backup_reviews` para registar revisões operacionais de backup/restore.

Campos previstos:

- ambiente;
- âmbito do backup;
- frequência;
- período de retenção;
- data do último backup;
- data do último teste de restore;
- findings;
- recomendações;
- responsável e timestamp da revisão.

Pendências:

- definir provider real de backup;
- configurar retenção offsite;
- testar restore completo antes de produção;
- documentar RPO/RTO municipal.
