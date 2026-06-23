# Document Intelligence — Painel Backoffice

## Rota

- `backoffice.document-ai.classifications.index`
- `backoffice.document-ai.classifications.show`
- `backoffice.document-ai.classifications.manual-review`

## Colunas principais

- Documento
- Classificação IA
- Confiança
- Estado
- OCR disponível

## Permissões

O painel usa `DocumentAiAnalysisPolicy`.

- Candidatos: sem acesso.
- Backoffice com `documents.view`: acesso a classificação e metadados.
- Perfis com `documents.audit` ou `audit_logs.view`: podem ver excerto OCR sensível.
- Marcação de revisão manual exige `documents.approve`, `documents.update` ou `documents.audit`.

## Auditoria

São auditadas consultas de detalhe e marcações de revisão manual. O texto OCR e `raw_ai_json` não são copiados para auditoria.
