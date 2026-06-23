# Checklist de Segurança Pré-Produção

## Estado Sprint 18

A plataforma inclui `security_checklists` e `security_checklist_items` para validar controlos antes de entrada em produção.

Categorias iniciais:

- autenticação;
- MFA;
- permissões;
- storage;
- documentos;
- auditoria;
- logs de acesso;
- exportações;
- backups;
- passwords;
- sessões;
- headers de segurança;
- RGPD;
- retenção;
- alertas;
- configuração de produção.

Regras:

- checklist é criada por utilizador backoffice autorizado;
- cada item deve ter estado e evidência;
- aprovação é bloqueada se existir item `failed`;
- a checklist é evidência operacional, não substitui validação DPO/jurídica.
