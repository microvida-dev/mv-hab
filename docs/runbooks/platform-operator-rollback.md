# Runbook - Recuperação de operadores de plataforma

## Princípio

O rollback nunca pode reativar `municipality_id === null` como autorização
global, atribuir uma role fixa ou criar permissions diretas.

## Falha antes da mutação

Se migration ou dry-run falhar:

1. manter maintenance mode;
2. preservar logs e output sanitizado;
3. corrigir a conta ou o manifesto aprovado;
4. repetir o dry-run;
5. executar apenas após plano válido.

Não há dados de associação a reverter.

## Falha durante o bootstrap

O bootstrap é transacional. Se um alvo for inválido:

1. confirmar que não existem associações parciais;
2. confirmar que não existem eventos parciais;
3. corrigir o manifesto ou a conta;
4. repetir o dry-run;
5. repetir com `--confirm`.

## Associação concedida ao ID errado

1. manter ou ativar maintenance mode se existir risco imediato;
2. confirmar que outro operador global válido permanece ativo;
3. revogar a associação pela área protegida, com justificação e MFA;
4. confirmar `platform_operator_revoked`;
5. confirmar que roles e permissions não foram alteradas;
6. executar novo bootstrap apenas com manifesto corrigido e reaprovado.

Nunca eliminar fisicamente a associação.

## Proteção do último operador

O sistema recusa a revogação do último operador ativo. Para substituir esse
operador:

1. aprovar um segundo ID;
2. executar bootstrap ou concessão normal;
3. validar o novo acesso numa sessão separada;
4. só depois revogar o operador anterior.

## Rollback estrutural

Preferir sempre roll-forward.

A migration só pode ser revertida quando:

- não existem associações ou eventos operacionais a preservar;
- a aplicação está em maintenance mode;
- a versão de código instalada não consulta a tabela;
- a versão compatível mantém fail-closed sem inferência por null;
- existe aprovação técnica e de segurança.

Comando, após validação:

```bash
php artisan migrate:rollback --step=1 --force
```

Não usar este comando se outras migrations posteriores fizerem parte do mesmo
batch sem plano de rollback validado.

## Validação final

- utilizadores sem associação continuam recusados;
- candidate continua fora do backoffice;
- utilizadores municipais não obtêm scope global;
- auditor continua sem mutações;
- eventos de auditoria permanecem imutáveis;
- não existem roles ou permissions diretas criadas pela recuperação.
