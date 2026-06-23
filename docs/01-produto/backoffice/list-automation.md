# Automação Assistida de Listas — Sprint 24

## Objetivo

Criar execuções auditáveis para geração assistida de listas provisórias e definitivas a partir de dados já existentes, sem publicação automática.

## Implementação

- Tabela: `list_automation_runs`
- Model: `ListAutomationRun`
- Controller: `ListAutomationController`
- Services: `ProvisionalListAutomationService`, `DefinitiveListAutomationService`, `ListAutomationValidator`, `ListAutomationRunService`

## Texto obrigatório

```text
A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.
```

## Guardrails

- Não publica automaticamente.
- Requer snapshot de ranking interno/bloqueado para lista provisória.
- Requer lista provisória com prazo de reclamação fechado para lista definitiva.
- Aprovação humana fica registada na execução.

## Pendências

- Validar regras reais de geração automática com jurídico e júri.
