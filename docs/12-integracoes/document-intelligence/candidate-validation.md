# Document Intelligence — Validação contra Candidatura

## Objetivo

A Sprint 30 acrescenta uma camada assistiva que cruza dados declarados na candidatura com campos estruturados extraídos dos documentos na Sprint 29.

Esta camada produz comparações, alertas, severidade e recomendações para revisão técnica. Não altera automaticamente candidatura, estado documental, elegibilidade, pontuação, ranking, listas, contratos ou workflow administrativo.

## Componentes

- `DocumentCandidateValidationPipeline`: orquestra a validação de um documento ou de todos os documentos de uma candidatura.
- `ValidateDocumentAiAgainstApplicationJob`: executa a validação em fila após extração estruturada.
- `CandidateDeclaredDataResolver`: resolve dados declarados da candidatura, adesão, agregado, rendimentos e situação habitacional.
- `ExtractedDocumentDataResolver`: lê campos extraídos em `document_ai_fields`.
- `DocumentValidationRuleRegistry`: define regras por tipo documental.
- `DocumentValidationComparator`: executa comparação determinística.
- `DocumentValidationSeverityResolver`: classifica divergências como nenhuma, ligeira, média ou crítica.
- `DocumentValidationPersister`: grava runs, resultados, hashes, flags e logs.

## Entidades

- `document_ai_validation_runs`: execução por candidatura, com contadores e estado.
- `document_ai_validations`: resultado granular por análise, candidatura, grupo e chave de validação.

## Estados e resultados

Resultados de comparação:

- `match`
- `partial_match`
- `mismatch`
- `inconclusive`
- `missing_candidate_value`
- `missing_document_value`
- `manual_review`
- `failed`

Estados de execução:

- `pending`
- `processing`
- `completed`
- `manual_review`
- `failed`

## Integração

Quando a extração estruturada termina e o documento está associado a uma candidatura, a pipeline agenda `ValidateDocumentAiAgainstApplicationJob`.

O backoffice também permite reprocessar uma candidatura, gerando nova run com base nos documentos e campos existentes.

## Limites

- A IA local não decide exclusões.
- Uma divergência crítica exige revisão técnica.
- Valores extraídos não substituem os valores declarados.
- Eventos e flags não transportam dados pessoais em payload.
- O painel mascara dados sensíveis para utilizadores sem permissão de auditoria.
