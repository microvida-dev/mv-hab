# Minutas de Procedimento — Sprint 24

## Objetivo

Gerir minutas reutilizáveis para relatórios, dossiers, listas, atas, confirmações e notas internas.

## Implementação

- Tabela: `procedure_templates`
- Model: `ProcedureTemplate`
- Controller: `ProcedureTemplateController`
- Services: `ProcedureTemplateService`, `TemplateRenderingService`, `TemplateVariableResolver`

## Regras

- Minutas começam em rascunho.
- Publicação ativa a minuta e substitui minutas ativas do mesmo tipo como superseded.
- Alterar minuta ativa cria versão sucessora em vez de sobrescrever diretamente.

## Variáveis base

- `candidate_name`
- `application_number`
- `process_number`
- `contest_title`
- `program_name`
- `generated_at`

## Pendências

- Aprovação jurídica das minutas oficiais antes de produção.
