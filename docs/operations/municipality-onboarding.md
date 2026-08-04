# Onboarding Municipal

## Objetivo

O onboarding municipal cria, de forma transacional e auditada:

- um Município;
- a role municipal `municipal-administrator`;
- o primeiro administrador municipal;
- a atribuição dessa role;
- a intenção persistida de convite para definição da palavra-passe.

Não ativa entitlements, não cria técnicos, não publica programas ou concursos e não altera operadores globais.

## Comando

```bash
php artisan mvhab:municipality:onboard \
  --actor-id=<ID_TECNICO_DO_OPERADOR_GLOBAL> \
  --name="<NOME_OFICIAL>" \
  --code="<CODIGO>" \
  --tax-number="<NIPC>" \
  --contact-email="<EMAIL_INSTITUCIONAL>" \
  --admin-name="<NOME_DO_ADMINISTRADOR>" \
  --admin-email="<EMAIL_DO_ADMINISTRADOR>" \
  --justification="<JUSTIFICACAO_APROVADA>"
```

Sem `--confirm`, o comando executa apenas preview read-only. `--dry-run` força o mesmo comportamento, mesmo quando `--confirm` é indicado.

Execução mutável:

```bash
php artisan mvhab:municipality:onboard <opções> --confirm
```

## Pré-condições

O ator deve cumprir cumulativamente:

- conta ativa;
- `municipality_id = null`;
- `PlatformOperatorAssignment` global explícito e ativo;
- MFA obrigatório e dispositivo confirmado;
- permission `municipalities.create`;
- não ser candidato nem auditor.

## Normalização e duplicações

O serviço normaliza código, NIPC e emails antes de calcular o fingerprint. A operação é bloqueada quando existe conflito em:

- código municipal;
- NIPC;
- email institucional;
- email do administrador;
- onboarding concorrente;
- onboarding concluído com fingerprint divergente.

Uma segunda execução com o mesmo fingerprint devolve o resultado existente sem criar dados adicionais.

## Transação

Dentro da transação principal são criados:

1. Município;
2. role municipal administrativa;
3. administrador municipal;
4. assignment da role;
5. invitation intent;
6. auditoria de domínio;
7. estado final do `MunicipalityOnboardingRun`.

O envio da notificação ocorre depois do commit, na queue configurada em:

```text
MVHAB_MUNICIPAL_ADMIN_INVITATION_QUEUE=notifications
```

Uma falha de transporte não elimina o Município. O convite fica recuperável e pode ser reenviado idempotentemente.

## Role administrativa

O template `municipal-administrator`:

- não contém `*` nem wildcards;
- não depende de entitlements;
- não contém permissions de operadores de plataforma;
- inclui as permissions necessárias para gerir utilizadores, roles, equipas e segurança municipal;
- contém a matriz exata do template `tecnico-operacoes-concurso`, permitindo a delegação posterior pelo `RoleAssignmentService` normal.

## Catálogo inicial de Alcanena

O Programa e o Concurso são criados separadamente:

```bash
php artisan mvhab:municipality:provision-initial-catalog \
  --actor-id=<ID_DO_ATOR> \
  --municipality=ALCANENA \
  --profile=alcanena-2026
```

Execução mutável:

```bash
php artisan mvhab:municipality:provision-initial-catalog \
  --actor-id=<ID_DO_ATOR> \
  --municipality=ALCANENA \
  --profile=alcanena-2026 \
  --confirm
```

A operação cria apenas:

- Programa Municipal de Arrendamento Acessível de Alcanena;
- Concurso `ALC-RAA-01-2026`.

Ambos ficam em `draft`, sem publicação, perfil regulamentar, snapshot, júri, fogos, prazos processuais, scoring, documentos, rendas ou entitlements. As datas do concurso são provisórias e não constituem confirmação do edital.

## Códigos de saída

| Código | Significado |
|---:|---|
| `0` | preview ou operação concluída |
| `11` | ator não autorizado ou inexistente |
| `20` | validação, conflito ou domínio bloqueado |
| `30` | falha técnica inesperada |
| `40` | onboarding concluído, mas convite em estado `failed` |
