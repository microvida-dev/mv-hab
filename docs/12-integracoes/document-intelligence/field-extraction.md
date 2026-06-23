# Document Intelligence — Extração Estruturada de Campos

## Objetivo

A Sprint 29 acrescenta extração estruturada de campos a partir do texto OCR e do tipo documental classificado na Sprint 28.

A extração é assistiva e técnica. Nenhum valor extraído altera automaticamente candidatura, agregado, rendimento, elegibilidade, pontuação, decisão administrativa, documento funcional, contrato ou workflow.

## Pipeline

```text
Documento submetido
→ análise IA pendente
→ OCR local
→ classificação documental
→ extração estruturada por schema
→ normalização de campos
→ score de confiança
→ persistência em document_ai_fields
→ painel backoffice
→ revisão técnica humana quando necessário
```

## Serviços

- `DocumentFieldExtractionPipeline`: coordena a etapa de extração após a classificação.
- `DocumentExtractionSchemaRegistry`: fornece schemas por tipo documental.
- `RegexFieldExtractor`: extrai campos por rótulos e padrões determinísticos.
- `LocalAiFieldExtractor`: usa Ollama local opcional quando configurado.
- `DocumentFieldNormalizer`: normaliza datas, valores monetários, percentagens, inteiros, identificadores e moradas.
- `DocumentExtractionResultValidator`: garante campos esperados, remove desconhecidos e sinaliza obrigatórios em falta.
- `DocumentExtractionScorer`: calcula confiança global e revisão manual.
- `DocumentExtractionPersister`: persiste JSON estruturado, campos normalizados, flags e logs.
- `DocumentExtractedFieldPresenter`: mascara dados sensíveis no backoffice.

## Persistência

`document_ai_analyses` recebe estado e payload agregados:

- `extraction_status`;
- `extraction_schema_version`;
- `extraction_json`;
- `extraction_confidence`;
- `extraction_model`;
- `extraction_prompt_version`;
- timestamps de início/conclusão/falha;
- `extraction_requires_manual_review`.

`document_ai_fields` guarda os campos extraídos com:

- `document_type`;
- `key`;
- `label`;
- `value`;
- `normalized_value`;
- `value_type`;
- `confidence`;
- `source`;
- `requires_review`;
- `metadata` com `category`, sensibilidade, dado de saúde e versão de schema.

## Eventos

- `DocumentFieldExtractionStarted`;
- `DocumentFieldExtractionCompleted`;
- `DocumentFieldExtractionFailed`;
- `DocumentFieldExtractionRequiresReview`.

Os eventos transportam IDs, tipo documental, estado e confiança. Não transportam texto OCR, JSON bruto ou valores extraídos.

## Limites

- Não valida autenticidade documental.
- Não altera candidatura nem documento funcional.
- Não cruza valores com dados declarados pelo candidato.
- Não exclui nem aprova candidatura.
- Não usa APIs pagas.
- Ollama local é opcional e começa desativado.
