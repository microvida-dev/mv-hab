# Atas do Procedimento — Sprint 24

## Objetivo

Gerar atas de acompanhamento ou deliberação a partir de minutas e dados estruturados, preservando payload e ficheiro privado.

## Implementação

- Tabela: `procedure_minutes`
- Model: `ProcedureMinute`
- Controller: `ProcedureMinuteController`
- Services: `ProcedureMinuteService`, `ProcedureMinutePayloadBuilder`, `ProcedureMinuteExportService`

## Texto obrigatório

```text
A ata foi preparada automaticamente a partir dos dados do procedimento e deve ser revista, validada e aprovada pelos responsáveis competentes.
```

## Segurança

- Storage privado.
- Aprovação humana obrigatória.
- A ata não executa decisão administrativa automaticamente.

## Pendências

- Definir modelos finais de ata por tipo de reunião/procedimento.
