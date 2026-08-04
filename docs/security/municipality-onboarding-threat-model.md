# Threat model — Onboarding Municipal

## Ativos protegidos

- identidade e configuração do Município;
- conta do primeiro administrador;
- matriz de permissions municipal;
- scope global do operador de plataforma;
- tokens de definição de palavra-passe;
- trilho de auditoria;
- dados institucionais e pessoais.

## Fronteiras de confiança

```text
CLI autorizado
    -> PlatformOperatorAssignment + MFA + permission
    -> transação MariaDB
    -> queue database / worker notifications
    -> password broker
    -> transport de email
```

## Ameaças e controlos

### Inferência indevida de scope global

**Risco:** tratar `municipality_id = null` como operador global.

**Controlo:** `PlatformOperatorScopeService` exige assignment global explícito e ativo. O operador global permanece sem Município.

### Escalada por wildcard

**Risco:** atribuir `*` ou permissions de plataforma à role municipal.

**Controlo:** template exato, validação antes do onboarding, teste de arquitetura e rejeição de qualquer permission com `*`.

### Self-promotion ou assignment entre Municípios

**Risco:** reutilizar o fluxo municipal normal para bootstrap global.

**Controlo:** caminho dedicado apenas para o primeiro administrador; `RoleAssignmentService::assign()` normal não é enfraquecido e continua fail-closed.

### Duplicação e corrida concorrente

**Risco:** criar dois Municípios, administradores ou convites.

**Controlo:** unique constraints, fingerprints, `DB::transaction()`, `lockForUpdate()`, ledger único por código e Job idempotente.

### Efeito externo antes do commit

**Risco:** enviar convite e depois reverter a base de dados.

**Controlo:** invitation intent persistido na transação e dispatch após commit. O Job recebe apenas o ID técnico.

### Exposição de credenciais

**Risco:** password, token ou URL aparecerem em output, logs ou auditoria.

**Controlo:** password aleatória nunca comunicada; token criado apenas no Job; output contém apenas IDs, códigos técnicos e fingerprints; auditoria não contém nomes, emails, NIPC ou token.

### Reenvio duplicado

**Risco:** múltiplos emails e tokens concorrentes.

**Controlo:** `ShouldBeUnique`, lock do convite, estados persistidos e verificação de `sent`, `consumed` e `expired`.

### Falha depois do envio

**Risco:** email enviado mas atualização do estado falhar, permitindo retry.

**Controlo:** o password broker invalida tokens anteriores quando aplicável; o Job é idempotente ao nível do estado persistido. A ocorrência deve ser investigada antes de reenvio manual quando o transport não fornecer confirmação transacional.

### Dados demo em produção

**Risco:** executar `DemoAlcanenaAffordableRentSeeder` ou transportar parâmetros fictícios.

**Controlo:** comando próprio cria apenas Programa e Concurso em draft. Não executa seeders, não cria utilizadores demo e não cria configuração regulamentar.

### Publicação prematura

**Risco:** tornar público um programa/concurso incompleto.

**Controlo:** `ProgramStatus::Draft`, `ContestStatus::Draft`, ausência de snapshot regulamentar e marcador municipal `publication_blocked=true`.

## Dados proibidos em logs e auditoria

- nomes;
- emails;
- NIPC;
- password;
- token;
- URL de reset;
- conteúdo integral da justificação;
- dados de candidatura ou documentos.

## Riscos residuais

- o transport de email não é transacional;
- a confirmação de entrega depende do fornecedor;
- a criação de convite não substitui a confirmação de MFA;
- o catálogo inicial contém datas provisórias que devem ser substituídas antes da publicação;
- rollback de código após onboarding concluído deve privilegiar forward fix e validar compatibilidade de schema.
