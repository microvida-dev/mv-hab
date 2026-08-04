# Runbook de produção — Onboarding Municipal

## Regra principal

Deploy de código e onboarding de dados são operações separadas. O deploy nunca executa automaticamente o comando de onboarding, o catálogo de Alcanena, seeders ou ativação de entitlements.

## 1. Gates antes do deploy

Confirmar:

```text
release construída a partir da RC5 ou descendente aprovada
backup atualizado
migrations revistas e reversíveis
mailer real validado
worker default/notifications ativo
worker reports ativo
scheduler ativo
failed_jobs = 0
config cache preparado
storage partilhado
plano de recuperação disponível
```

## 2. Deploy do código

Aplicar migrations aprovadas, reconstruir caches e reiniciar workers. Não executar onboarding nesta fase.

Validações mínimas:

```bash
/opt/plesk/php/8.4/bin/php artisan migrate:status
/opt/plesk/php/8.4/bin/php artisan about
systemctl is-active mvhab-queue-default.service
systemctl is-active mvhab-queue-reports.service
systemctl is-active mvhab-scheduler.timer
```

## 3. Preview

Executar sem `--confirm` e guardar o output técnico no dossier operacional. O preview deve apresentar:

```text
MUNICIPALITY_ONBOARDING=PREVIEW
WRITE_OPERATIONS=0
CONFLICTS=0
ENTITLEMENTS_ACTIVATED=0
```

Confirmar manualmente nome, código, NIPC e emails na fonte institucional, sem os copiar para logs técnicos adicionais.

## 4. Onboarding confirmado

Executar o mesmo comando com `--confirm` apenas depois de aprovação explícita.

Resultado esperado:

```text
MUNICIPALITY_ONBOARDING=PASS
MFA_REQUIRED=true
INVITATION_STATUS=queued|sent
ENTITLEMENTS_ACTIVATED=0
```

## 5. Verificações pós-onboarding

Confirmar através de queries ou interface autorizada:

- um Município;
- um administrador municipal ativo;
- `mfa_required = true`;
- uma role `municipal-administrator` municipal;
- zero wildcard permissions;
- um assignment municipal;
- zero `PlatformOperatorAssignment` para o novo administrador;
- operador global continua com `municipality_id = null`;
- zero entitlements municipais;
- convite `queued`, `sent` ou estado recuperável.

## 6. Palavra-passe e MFA

O administrador:

1. recebe o link temporário;
2. define a palavra-passe;
3. o convite passa a `consumed` após o evento `PasswordReset`;
4. entra na área de MFA;
5. configura e confirma o dispositivo;
6. valida o contexto municipal.

Não comunicar uma password inicial por email, telefone ou ticket.

## 7. Catálogo inicial de Alcanena

Executar primeiro o preview e depois `--confirm`.

Resultado obrigatório:

```text
PROGRAM_STATUS=draft
CONTEST_STATUS=draft
PUBLICATION_BLOCKED=true
ENTITLEMENTS_ACTIVATED=0
```

Não publicar antes de confirmar edital, datas, habitações, rendas, regras, documentos, júri, perfis regulamentares e snapshots.

## 8. Operações posteriores

Depois de MFA validado:

1. preview do template `tecnico-operacoes-concurso`;
2. validação do fingerprint e das 120 permissions;
3. aplicação do template;
4. criação ou associação dos dois técnicos;
5. assignment via `RoleAssignmentService`;
6. ativação separada de `applications.intake`, `applications.review` e `applications.export`;
7. auditoria final de permissions, roles, assignments, scope e entitlements.
