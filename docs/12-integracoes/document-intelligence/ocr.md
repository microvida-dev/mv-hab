# Document Intelligence — OCR

## Âmbito

A Sprint 28 implementa OCR local controlado para documentos submetidos em storage privado.

## Estratégias

| Formato | Estratégia |
| --- | --- |
| PDF pesquisável | extração direta com Poppler `pdftotext` |
| PDF digitalizado | conversão de páginas para PNG com `pdftoppm` e OCR com Tesseract |
| JPG | OCR direto com Tesseract |
| PNG | OCR direto com Tesseract |
| HEIC/HEIF | conversão via ImageMagick e OCR com Tesseract quando o ambiente suporta HEIC/libheif |

## Estados

- `pending`
- `processing`
- `completed`
- `failed`
- `unavailable`
- `skipped`

## Segurança

O texto extraído é sensível e fica em `ocr_text`/`raw_text`. Não é enviado em eventos, logs de auditoria ou páginas públicas.

## Falha controlada

Se ferramentas locais não existirem ou o OCR falhar, a análise é marcada para revisão manual e o documento continua disponível no fluxo documental existente.
