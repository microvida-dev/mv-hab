# Fecho do concurso

## Objetivo

Fechar concurso após validação das pendências críticas pós-sorteio.

## Pendências críticas

- Sorteio por validar.
- Sorteio validado sem vencedor.
- Relatório pós-sorteio em falta.
- Entrega de chaves pendente.
- Transição para área do inquilino pendente.

## Implementação

`ContestClosureService` cria `contest_closures` com número único, snapshot, resumo e auditoria. O fecho não apaga dados.
