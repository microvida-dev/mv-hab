# Backoffice — Painel de Extração IA

## Rotas

- `backoffice.document-ai.extractions.index`
- `backoffice.document-ai.extractions.show`
- `backoffice.document-ai.fields.review`

## Listagem

A listagem permite filtrar por:

- tipo documental;
- estado da extração;
- revisão manual;
- chave de campo;
- confiança mínima/máxima.

## Detalhe

O detalhe mostra:

- tipo documental classificado;
- estado e confiança da extração;
- campos extraídos;
- valores normalizados;
- fonte e confiança por campo;
- flags da extração;
- logs técnicos da etapa de extração.

## Segurança visual

Valores sensíveis podem ser mascarados. Dados de saúde podem ficar totalmente ocultos. O painel não mostra texto OCR bruto nem JSON bruto integral.

## Ação humana

O utilizador autorizado pode marcar um campo para revisão manual. Essa ação:

- marca `document_ai_fields.requires_review`;
- altera `extraction_status` para `manual_review`;
- cria flag técnica;
- cria processing log;
- regista audit log.
