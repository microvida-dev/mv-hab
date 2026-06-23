# Document Intelligence — Stack Local

## Princípio

O módulo usa apenas ferramentas gratuitas e executadas localmente. Não há chamadas para APIs pagas ou serviços cloud de IA.

## Ferramentas previstas

| Área | Ferramenta |
| --- | --- |
| OCR | Tesseract OCR |
| PDF | Poppler (`pdftotext`, `pdftoppm`) |
| Imagem | ImageMagick (`magick`) |
| NLP local | Ollama |
| Modelos recomendados | Gemma 3 4B ou Qwen 2.5 7B Instruct |

## Configuração

Arquivo: `config/document-ai.php`

Variáveis suportadas:

- `DOCUMENT_AI_ENABLED`
- `DOCUMENT_AI_QUEUE`
- `DOCUMENT_AI_CHECK_LOCAL_TOOLS`
- `DOCUMENT_AI_MISSING_TOOLS_STATUS`
- `DOCUMENT_AI_CLASSIFICATION_ENABLED`
- `DOCUMENT_AI_CLASSIFICATION_TIMEOUT`
- `DOCUMENT_AI_OCR_DRIVER`
- `DOCUMENT_AI_TESSERACT_BINARY`
- `DOCUMENT_AI_TESSERACT_LANG`
- `DOCUMENT_AI_OCR_MAX_PAGES`
- `DOCUMENT_AI_OCR_TIMEOUT`
- `DOCUMENT_AI_OCR_FALLBACK_TO_SOURCE_TEXT`
- `DOCUMENT_AI_PDFTOTEXT_BINARY`
- `DOCUMENT_AI_PDFIMAGES_BINARY`
- `DOCUMENT_AI_PDFTOPPM_BINARY`
- `DOCUMENT_AI_MAGICK_BINARY`
- `DOCUMENT_AI_OLLAMA_ENABLED`
- `DOCUMENT_AI_OLLAMA_URL`
- `DOCUMENT_AI_OLLAMA_MODEL`
- `DOCUMENT_AI_OLLAMA_TIMEOUT`

## Comportamento quando ferramentas não existem

Se as ferramentas locais obrigatórias não estiverem disponíveis, a análise não bloqueia o upload documental. A pipeline:

- cria flags técnicas;
- marca a análise para `manual_review`;
- regista logs minimizados;
- audita o evento;
- mantém o documento disponível para validação manual.

## Ollama

Ollama começa opcional e desativado por defeito. Quando ativo, deve apontar para uma instância local ou self-hosted, sem credenciais no código.

## Ausência de APIs pagas

A Sprint 28 não integra OpenAI, Anthropic, Google Vision, Azure AI, AWS Textract, Mistral Cloud, OCR.space, Mindee, Nanonets ou qualquer fornecedor pago.

## OCR por formato na Sprint 28

| Formato | Estratégia |
| --- | --- |
| PDF pesquisável | `pdftotext` com fallback controlado para revisão manual |
| PDF digitalizado | conversão de páginas via `pdftoppm` e OCR Tesseract |
| JPG | OCR Tesseract direto |
| PNG | OCR Tesseract direto |
| HEIC/HEIF | conversão ImageMagick para PNG e OCR Tesseract, se o servidor tiver suporte HEIC/libheif |

## IA local

Ollama é opcional. Quando desativado ou indisponível, a classificação usa OCR, palavras-chave e sinais de layout. A falha da IA local não bloqueia upload nem validação manual.
