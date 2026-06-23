# Document Intelligence — Overview

## Objetivo

O módulo Document Intelligence cria uma camada técnica para análise documental assistida por IA local, integrada com as submissões documentais privadas da plataforma MV HAB.

A Sprint 27 implementou a infraestrutura. A Sprint 28 acrescentou OCR local controlado e classificação automática do tipo documental. A Sprint 29 acrescentou extração estruturada de campos por schema documental. A Sprint 30 acrescenta validação assistida contra dados declarados na candidatura. A decisão final permanece sempre humana e municipal.

## Limites do módulo até à Sprint 30

- Não valida documentos administrativamente de forma automática.
- Não exclui candidaturas.
- Não altera estados de candidaturas.
- Não altera estados documentais funcionais.
- Não calcula elegibilidade, pontuação ou ranking.
- Não expõe resultados ao candidato.
- Não chama APIs pagas.
- O cruzamento automático apenas sinaliza divergências e revisão manual.

## Modelos criados

- `DocumentAiAnalysis`: execução principal da análise.
- `DocumentAiField`: campos estruturados futuros.
- `DocumentAiFlag`: alertas técnicos e revisão manual.
- `DocumentAiProcessingLog`: logs minimizados da pipeline.

## Estados da análise

- `pending`: análise criada.
- `processing`: job em execução.
- `completed`: infraestrutura executada.
- `failed`: falha técnica controlada.
- `manual_review`: revisão manual recomendada.

## Sprint 28 — OCR e classificação

A Sprint 28 adiciona:

- enum `DocumentAiOcrStatus`;
- enum `DocumentAiClassificationStatus`;
- enum `DocumentAiDocumentType`;
- campos de OCR e classificação em `document_ai_analyses`;
- serviços de extração OCR, pré-processamento de imagem, palavras-chave, sinais de layout, prompt JSON, normalização de resposta IA local e score final;
- painel backoffice em `backoffice.document-ai.classifications.*`;
- policy dedicada `DocumentAiAnalysisPolicy`;
- testes unitários e funcionais com fixtures sintéticas.

## Sprint 29 — Extração estruturada

A Sprint 29 adiciona:

- enum `DocumentAiExtractionStatus`;
- enum `DocumentAiExtractedFieldType`;
- enum `DocumentAiExtractionSource`;
- campos de extração em `document_ai_analyses`;
- metadados de tipo, fonte e revisão em `document_ai_fields`;
- schemas de extração por tipo documental;
- normalização de campos extraídos;
- painel backoffice em `backoffice.document-ai.extractions.*`;
- policy para campos extraídos;
- eventos de extração;
- testes unitários e funcionais com fixtures sintéticas.

## Sprint 30 — Validação contra candidatura

A Sprint 30 adiciona:

- enums de estado, severidade, grupo e método de comparação;
- `document_ai_validation_runs`;
- `document_ai_validations`;
- `DocumentCandidateValidationPipeline`;
- `ValidateDocumentAiAgainstApplicationJob`;
- resolvers de dados declarados e extraídos;
- registry de regras por tipo documental;
- comparadores determinísticos;
- persister de validações, hashes, flags e logs;
- painel backoffice em `backoffice.document-ai.validations.*`;
- policies, Form Requests e mascaramento de valores;
- eventos de início, conclusão, falha, revisão e divergência crítica;
- testes unitários, feature, queue fake, eventos e auditoria.

## Categorias suportadas

- `cartao_cidadao`
- `titulo_residencia`
- `passaporte`
- `irs`
- `nota_liquidacao`
- `recibo_vencimento`
- `declaracao_seguranca_social`
- `declaracao_at`
- `iban`
- `contrato_arrendamento`
- `comprovativo_morada`
- `atestado_multiusos`
- `certidao_escolar`
- `outro`

## Pipeline

`DocumentAiPipeline` centraliza:

- criação de análise pendente;
- despacho para queue;
- transições de estado;
- verificação de fonte privada;
- OCR e classificação através de `DocumentClassificationPipeline`;
- gravação de `raw_ai_json`;
- criação de flags;
- logs minimizados;
- auditoria;
- emissão de eventos.

## Queue

`ProcessDocumentAiJob` recebe apenas `documentAiAnalysisId`, recarrega a análise da base de dados e chama `DocumentAiPipeline::process()`.

O job não serializa texto documental, JSON bruto ou paths internos além do ID da análise.

## Eventos

- `DocumentAnalysisStarted`
- `DocumentAnalysisCompleted`
- `DocumentAnalysisFailed`
- `DocumentOcrStarted`
- `DocumentOcrCompleted`
- `DocumentOcrFailed`
- `DocumentClassificationStarted`
- `DocumentClassificationCompleted`
- `DocumentClassificationFailed`
- `DocumentClassificationRequiresReview`
- `DocumentFieldExtractionStarted`
- `DocumentFieldExtractionCompleted`
- `DocumentFieldExtractionFailed`
- `DocumentFieldExtractionRequiresReview`
- `DocumentCandidateValidationStarted`
- `DocumentCandidateValidationCompleted`
- `DocumentCandidateValidationFailed`
- `DocumentCandidateValidationRequiresReview`
- `DocumentCandidateCriticalDivergenceDetected`

Os eventos transportam apenas identificadores, estado final, tipo documental, confiança ou contadores quando aplicável. Não transportam texto OCR bruto, JSON bruto, valores declarados, valores extraídos ou conteúdo documental.

## Integração com documentos existentes

A integração ocorre em `DocumentUploadService` após upload ou substituição documental. A criação da análise é executada depois do commit da transação, preservando o documento mesmo que a preparação da IA falhe.

## Limitações conhecidas

- A disponibilidade de Tesseract, Poppler, ImageMagick e suporte HEIC depende da instalação no servidor.
- Ollama é opcional e começa desativado.
- Validação cruzada com candidaturas fica para a Sprint 30.
- Score avançado/fraude/aperfeiçoamento fica para a Sprint 31.

## Sprint 31 — Assistente IA

A camada de assistente IA acrescenta score de confiança, indicadores de risco e sugestões de aperfeiçoamento documental ao fluxo existente:

```text
Upload privado
→ análise IA
→ OCR/classificação
→ extração estruturada
→ validação contra candidatura
→ score assistivo e sugestões internas
→ decisão humana municipal
```

O módulo é composto por:

- `DocumentAiAssistantPipeline`;
- `CalculateDocumentAiScoreJob`;
- `document_ai_scores`;
- `document_ai_suggestions`;
- painel `backoffice.document-ai.assistant.*`.

O módulo não altera decisões, estados processuais ou regras de elegibilidade.
