# Sprint 27 — Infraestrutura Base de Análise Documental por IA

## Objetivo

Criar a fundação técnica do módulo Document Intelligence para análise documental assistida por IA local, sem alterar a lógica funcional existente de candidatura, elegibilidade, classificação, revisão documental ou decisão administrativa.

## Implementado

- Módulo `DocumentIntelligence` com `DocumentAiPipeline`.
- Tabelas `document_ai_analyses`, `document_ai_fields`, `document_ai_flags` e `document_ai_processing_logs`.
- Enum `DocumentAiStatus` com estados `pending`, `processing`, `completed`, `failed` e `manual_review`.
- Models e factories para as quatro entidades Document AI.
- Job `ProcessDocumentAiJob`, executado por queue e recebendo apenas o ID da análise.
- Eventos `DocumentAnalysisStarted`, `DocumentAnalysisCompleted` e `DocumentAnalysisFailed`, sem transporte de texto ou JSON sensível.
- Configuração `config/document-ai.php` para Tesseract, Poppler, ImageMagick e Ollama local.
- Integração com `DocumentUploadService` após submissão e substituição documental.
- Criação de análise `pending` sem bloquear o upload.
- Armazenamento de `raw_ai_json` técnico e minimizado.
- Logs de processamento minimizados em tabela própria.
- Auditoria via `AuditLogger`, sem texto integral do documento nem JSON bruto nas mensagens.
- Testes unitários, feature, queue fake, eventos e auditoria.

## Fora de âmbito preservado

- Sem classificação documental automática funcional.
- Sem extração estruturada real de campos.
- Sem validação automática contra candidaturas.
- Sem score de fraude.
- Sem alteração automática de estados documentais.
- Sem decisão automática de elegibilidade, pontuação ou exclusão.
- Sem rotas públicas ou views para resultados de IA.
- Sem APIs pagas ou serviços cloud.

## Integração com documentos existentes

O ponto de integração é `App\Services\Documents\DocumentUploadService`.

Após `store()` ou `replace()` concluir a gravação da submissão e da versão documental em storage privado, a aplicação agenda, via `DB::afterCommit`, a criação de uma análise pendente e o despacho de `ProcessDocumentAiJob`.

Se a criação da análise falhar, o upload documental é preservado e a falha fica auditada como `document_ai_pending_failed`.

## Estados

| Estado | Uso |
| --- | --- |
| `pending` | Análise criada e pronta para queue |
| `processing` | Job iniciou processamento |
| `completed` | Infraestrutura executada sem flags bloqueantes |
| `failed` | Falha técnica controlada |
| `manual_review` | Ferramentas locais indisponíveis ou revisão humana recomendada |

## Pendências para Sprint 28

- OCR real para PDF pesquisável, PDF digitalizado, JPG, PNG e HEIC.
- Normalização de texto extraído.
- Classificação automática do tipo documental.
- Painel backoffice de consulta de classificação, confiança e estado OCR.
- Métricas de precisão sobre amostra documental portuguesa.

## Riscos

- A disponibilidade real de Tesseract, Poppler, ImageMagick e Ollama depende do servidor.
- `raw_text` e `raw_ai_json` são dados sensíveis e não devem ser expostos por defeito.
- Workers de queue devem estar ativos em staging/produção para processamento assíncrono.
- PHPStan global continua sujeito a dívida técnica legada fora do escopo desta sprint.
