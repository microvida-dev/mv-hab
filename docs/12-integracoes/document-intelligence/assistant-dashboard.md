# Painel Assistente IA

## Rotas

- `backoffice.document-ai.assistant.index`
- `backoffice.document-ai.assistant.show`
- `backoffice.document-ai.assistant.recalculate`
- `backoffice.document-ai.assistant.suggestions.update`
- `backoffice.document-ai.assistant.suggestions.accept`
- `backoffice.document-ai.assistant.suggestions.dismiss`

## Funcionalidades

- Listagem de scores IA.
- Filtros por label, flag, revisão, candidatura e datas.
- Detalhe por análise documental.
- Visualização de score, componentes, explicação e flags.
- Gestão interna de sugestões de aperfeiçoamento.
- Reprocessamento manual do score por utilizador autorizado.

## Segurança

- Guest é redirecionado para login.
- Candidato não acede ao backoffice.
- O painel não mostra `raw_ai_json`, OCR bruto, `extraction_json` nem paths internos.
- A mensagem de contexto explicita que o score não produz decisão automática.
