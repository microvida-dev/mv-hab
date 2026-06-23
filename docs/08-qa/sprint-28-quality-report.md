# Sprint 28 — Relatório de Qualidade

## Testes adicionados

- `DocumentClassificationServicesTest`
- `DocumentOcrServicesTest`
- `DocumentClassificationAccuracyTest`
- `DocumentOcrClassificationIntegrationTest`
- `DocumentAiClassificationPanelTest`

## Dataset QA

Foram criadas fixtures sintéticas anonimizadas em `tests/Fixtures/document-intelligence/classification`.

Categorias cobertas:

- Cartão de Cidadão
- Título de Residência
- Passaporte
- IRS
- Nota de Liquidação
- Recibo de vencimento
- Declaração Segurança Social
- Declaração AT
- IBAN
- Contrato de arrendamento
- Comprovativo de morada
- Atestado Multiusos
- Certidão escolar

## Resultado focado

`php artisan test --filter=DocumentIntelligence` passou com 24 testes e 105 asserções.

## Riscos

- A precisão real depende de documentos reais anonimizados e validação municipal.
- OCR HEIC depende de suporte `libheif` no ImageMagick instalado.
- Ollama começa opcional/desativado; a classificação continua por sinais determinísticos.
