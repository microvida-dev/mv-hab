# Audit Trail

## Estado Sprint 18

O audit trail avançado usa `audit_events` e mantém compatibilidade com `audit_logs`.

Eventos registam:

- número único;
- ator e titular;
- categoria e severidade;
- entidade auditada;
- request path, método, rota, IP e user agent;
- valores antigos/novos mascarados;
- metadata mascarada;
- timestamp.

Garantias:

- `AuditEvent` bloqueia update/delete por model event;
- `AuditEventFormatter` mascara chaves de password, token, secret, recovery code, NIF, documentos e paths;
- downloads, exportações, login, logout e falhas de login criam rastreabilidade adicional em logs específicos.
