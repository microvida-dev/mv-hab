# Indicadores de risco documental

## Objetivo

As flags da Sprint 31 normalizam indicadores técnicos para revisão manual. A linguagem deve ser neutra e administrativa.

## Códigos suportados

| Código | Significado |
| --- | --- |
| `document_expired` | Documento com validade expirada |
| `document_unreadable` | Documento ilegível |
| `page_cropped` | Página cortada ou incompleta |
| `insufficient_ocr` | OCR insuficiente |
| `nif_mismatch` | Divergência de NIF |
| `name_mismatch` | Divergência de nome |
| `income_incompatible` | Rendimentos incompatíveis com o declarado |
| `duplicate_document` | Documento tecnicamente duplicado |
| `empty_document` | Ficheiro sem conteúdo útil |
| `missing_required_fields` | Campos obrigatórios ausentes |

## Severidades

`info`, `low`, `medium`, `high`, `critical`.

## Fontes

- `document_quality_analyzer`;
- `document_duplicate_detector`;
- `candidate_document_validation`.

## Limites

Uma flag representa um indicador técnico. Não representa prova de fraude, falsidade documental ou má-fé do candidato.
