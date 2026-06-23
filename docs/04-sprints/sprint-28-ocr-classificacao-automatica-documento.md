# Sprint 28 — OCR e Classificação Automática do Documento

## 1. Objetivo da Sprint

Implementar OCR e classificação automática do tipo documental, permitindo que o sistema identifique a categoria provável de cada documento submetido **antes de qualquer validação manual ou funcional**.

Esta sprint deve evoluir o módulo **Document Intelligence** criado na Sprint 27, mantendo a separação clara entre:

```text
Classificação automática assistida por IA
Validação documental manual ou funcional
Decisão administrativa
```

A classificação automática serve apenas para apoiar triagem, organização, revisão técnica e qualidade operacional.

Não deve aprovar, reprovar, excluir, validar, pontuar ou alterar automaticamente qualquer candidatura, documento, agregado, concurso, lista ou processo.

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 28 — OCR e Classificação Automática do Documento
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Dependência da Sprint 27

Assumir que a Sprint 27 criou ou deverá criar a infraestrutura base:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
DocumentAiStatus
DocumentAiPipeline
ProcessDocumentAiJob
DocumentAnalysisStarted
DocumentAnalysisCompleted
DocumentAnalysisFailed
Auditoria
Armazenamento de raw_ai_json
Integração com documentos privados existentes
```

Antes de implementar, confirmar a estrutura real existente no projeto.

Se a Sprint 27 ainda não estiver implementada, criar apenas adaptações mínimas compatíveis, sem duplicar conceitos, tabelas ou services.

Não criar um segundo módulo paralelo de IA documental.

---

## 4. Princípios Técnicos

Preservar todas as funcionalidades existentes.

Manter documentos privados por defeito.

Manter validação documental existente como fonte de verdade.

Classificação automática nunca substitui a decisão humana nesta sprint.

Usar Eloquent, migrations reversíveis, enums, casts tipados, Form Requests, Policies, Services, Jobs, Events e testes, seguindo os padrões reais do projeto.

Controllers devem continuar magros.

Toda a lógica de OCR, normalização, scoring e classificação deve ficar em Services.

Não expor dados pessoais em logs técnicos, eventos, mensagens de erro ou painel sem autorização.

Não guardar credenciais.

Não usar dados pessoais reais em testes.

Não introduzir APIs pagas.

---

## 5. Stack Permitida

Usar apenas ferramentas locais/gratuitas:

```text
PDF pesquisável: Poppler / pdftotext
PDF digitalizado: Poppler + ImageMagick + Tesseract OCR
JPG: ImageMagick + Tesseract OCR
PNG: ImageMagick + Tesseract OCR
HEIC: ImageMagick com suporte HEIC/libheif + Tesseract OCR
IA local: Ollama local
Modelos recomendados: Gemma 3 4B ou Qwen 2.5 7B Instruct
```

Não efetuar chamadas para APIs pagas.

Não integrar OpenAI, Anthropic, Google Vision, Azure AI, AWS Textract, Mistral Cloud, OCR.space, Mindee, Nanonets ou qualquer fornecedor externo pago nesta sprint.

Se uma ferramenta local não estiver instalada, o sistema deve:

```text
falhar de forma controlada;
registar flag técnica;
manter upload documental funcional;
marcar OCR como indisponível;
permitir revisão manual;
documentar a limitação;
não bloquear a candidatura.
```

---

## 6. Funcionalidades Obrigatórias

### 6.1 OCR automático

Suportar:

```text
PDF pesquisável
PDF digitalizado
JPG
PNG
HEIC
```

Requisitos:

```text
Detetar se PDF já contém texto pesquisável.
Usar extração direta para PDF pesquisável.
Converter páginas de PDF digitalizado para imagem antes do OCR.
Normalizar imagens antes do OCR quando possível.
Executar OCR em português e inglês.
Guardar texto OCR em campo sensível da análise.
Guardar metadados técnicos minimizados.
Não guardar imagens convertidas permanentemente salvo necessidade justificada.
Limpar ficheiros temporários.
Registar duração, método e qualidade aproximada.
```

### 6.2 Classificação automática

Classificar documentos numa das categorias suportadas:

```text
cartao_cidadao
titulo_residencia
passaporte
irs
nota_liquidacao
recibo_vencimento
declaracao_seguranca_social
declaracao_at
iban
contrato_arrendamento
comprovativo_morada
atestado_multiusos
certidao_escolar
outro
```

Labels públicos/administrativos:

```text
Cartão de Cidadão
Título de Residência
Passaporte
IRS
Nota de Liquidação
Recibo de vencimento
Declaração Segurança Social
Declaração AT
IBAN
Contrato de arrendamento
Comprovativo de morada
Atestado Multiusos
Certidão escolar
Outro
```

### 6.3 Base de decisão

A classificação deve combinar:

```text
OCR
palavras-chave
layout
IA local
```

Regras:

```text
OCR fornece o texto base.
Palavras-chave dão sinais determinísticos.
Layout fornece sinais estruturais quando possível.
IA local resolve ambiguidades e devolve JSON.
Score final combina sinais, não depende exclusivamente da IA.
Quando a confiança for baixa, classificar como outro ou manual_review.
```

### 6.4 Prompt estruturado

Usar prompt estruturado para IA local:

```text
Classifique este documento numa das categorias suportadas e devolva apenas JSON.
```

O prompt completo deve exigir JSON estrito, sem markdown, sem explicação adicional.

Formato de resposta recomendado:

```json
{
  "document_type": "irs",
  "label": "IRS",
  "confidence": 0.94,
  "signals": ["modelo_3", "declaracao_rendimentos", "autoridade_tributaria"],
  "reason": "Documento contém referências compatíveis com declaração anual de IRS.",
  "requires_manual_review": false
}
```

Validar o JSON recebido.

Se o JSON for inválido:

```text
registar flag ai_invalid_json;
usar fallback por palavras-chave/layout;
marcar requires_manual_review se confiança for baixa;
não falhar o upload;
não expor resposta bruta no painel por defeito.
```

---

## 7. Backoffice Obrigatório

Criar novo painel administrativo para análise de classificações IA.

Nome sugerido:

```text
Backoffice > Documentos > Classificação IA
```

Rotas e nomes devem respeitar a estrutura real do projeto.

O painel deve mostrar:

```text
Documento
Classificação IA
Confiança
Estado
OCR disponível
```

Campos recomendados adicionais:

```text
Candidato/processo, se permitido pela policy
Tipo esperado, se existir checklist documental
Tipo classificado
Fonte de classificação
Flags
Data da análise
Ação: ver detalhe autorizado
Ação: marcar para revisão manual
```

Não mostrar `raw_text` nem `raw_ai_json` por defeito.

Se existir detalhe administrativo, deve:

```text
usar auth;
usar policy/gate;
auditar acesso;
mostrar OCR truncado ou minimizado;
mostrar sinais técnicos;
ocultar dados pessoais quando o perfil não tem permissão;
não permitir download direto por path;
não expor documentos privados publicamente.
```

---

## 8. Leitura Inicial do Projeto

Antes de implementar, analisar a estrutura real:

```bash
rg "DocumentAi|document_ai|DocumentIntelligence|ProcessDocumentAiJob" app database routes tests config docs
rg "DocumentSubmission|RequiredDocument|document_submission|document_submissions|DocumentAccess" app database routes tests
rg "Backoffice.*Document|Document.*Controller|documents" app/Http routes resources/views tests
rg "Audit|audit|Activity|DocumentAccessAction" app database tests
rg "Gate::authorize|policy|Policy" app/Policies app/Http routes tests
rg "Queue::fake|Event::fake|ShouldQueue" tests app
```

Identificar:

```text
Modelo real dos documentos submetidos
Tabela real dos documentos privados
Relações entre candidatura/documento/checklist
Pipeline criada na Sprint 27
Job usado para análise documental
Padrão real de backoffice
Padrão real de auditoria
Padrão real de permissões
Stack frontend real: Blade, Alpine, Inertia ou outro
```

Adaptar nomes e paths à estrutura real.

Não mudar a stack frontend.

Não misturar Blade e Inertia se o projeto já tiver stack definida.

---

## 9. Modelo de Dados

Reutilizar as tabelas da Sprint 27 sempre que possível.

Se `document_ai_analyses` já existir, criar migration incremental para adicionar campos de OCR/classificação.

Campos recomendados para `document_ai_analyses`:

```text
ocr_status string nullable
ocr_available boolean default false
ocr_engine string nullable
ocr_language string nullable
ocr_text longText nullable
ocr_quality_score decimal nullable
ocr_pages_count unsignedInteger nullable
ocr_processed_at timestamp nullable
classification_status string nullable
detected_document_type string nullable indexed
detected_document_label string nullable
classification_confidence decimal nullable
classification_source string nullable
classification_model string nullable
classification_prompt_version string nullable
classification_signals json nullable
classification_requires_manual_review boolean default false
classified_at timestamp nullable
```

Se o projeto preferir separar responsabilidades, criar tabela:

```text
document_ai_classifications
```

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
document_type string indexed
label string
confidence decimal
source string
model string nullable
prompt_version string nullable
signals json nullable
raw_response json nullable
requires_manual_review boolean default false
created_by nullable
timestamps
```

Escolha recomendada:

```text
Adicionar campos principais em document_ai_analyses para listagem rápida.
Usar document_ai_fields, document_ai_flags e raw_ai_json para detalhe.
Criar document_ai_classifications apenas se o projeto precisar de histórico de múltiplas classificações por análise.
```

Índices recomendados:

```text
detected_document_type
classification_status
classification_confidence
ocr_available
classification_requires_manual_review
classified_at
```

Regras RGPD:

```text
ocr_text é dado sensível.
raw_ai_json é dado sensível.
raw_response é dado sensível.
Não indexar texto integral OCR.
Não colocar dados pessoais em logs.
```

---

## 10. Enums Obrigatórios

Criar:

```text
App\Enums\DocumentAiDocumentType
App\Enums\DocumentAiOcrStatus
App\Enums\DocumentAiClassificationStatus
```

### 10.1 DocumentAiDocumentType

Valores:

```php
enum DocumentAiDocumentType: string
{
    case CartaoCidadao = 'cartao_cidadao';
    case TituloResidencia = 'titulo_residencia';
    case Passaporte = 'passaporte';
    case Irs = 'irs';
    case NotaLiquidacao = 'nota_liquidacao';
    case ReciboVencimento = 'recibo_vencimento';
    case DeclaracaoSegurancaSocial = 'declaracao_seguranca_social';
    case DeclaracaoAt = 'declaracao_at';
    case Iban = 'iban';
    case ContratoArrendamento = 'contrato_arrendamento';
    case ComprovativoMorada = 'comprovativo_morada';
    case AtestadoMultiusos = 'atestado_multiusos';
    case CertidaoEscolar = 'certidao_escolar';
    case Outro = 'outro';
}
```

Incluir método `label()`:

```php
public function label(): string;
```

### 10.2 DocumentAiOcrStatus

Valores:

```text
pending
processing
completed
failed
unavailable
skipped
```

### 10.3 DocumentAiClassificationStatus

Valores:

```text
pending
processing
completed
failed
manual_review
low_confidence
```

Usar casts nos models.

Evitar strings soltas fora de migrations e testes específicos.

---

## 11. Services Obrigatórios

Criar ou completar services em namespace coerente:

```text
App\Services\DocumentIntelligence\DocumentOcrExtractor
App\Services\DocumentIntelligence\DocumentTextExtractor
App\Services\DocumentIntelligence\DocumentImagePreprocessor
App\Services\DocumentIntelligence\DocumentKeywordClassifier
App\Services\DocumentIntelligence\DocumentLayoutSignalExtractor
App\Services\DocumentIntelligence\LocalAiDocumentClassifier
App\Services\DocumentIntelligence\DocumentClassificationPromptBuilder
App\Services\DocumentIntelligence\DocumentClassificationResultNormalizer
App\Services\DocumentIntelligence\DocumentClassificationScorer
App\Services\DocumentIntelligence\DocumentClassificationPipeline
```

Se a base já tiver `DocumentAiPipeline`, integrar estes services nele sem criar duplicação.

### 11.1 DocumentTextExtractor

Responsável por:

```text
Detetar mime/extensão.
Encaminhar PDF pesquisável para pdftotext.
Encaminhar PDF digitalizado para OCR.
Encaminhar JPG/PNG/HEIC para OCR.
Normalizar resultado textual.
Retornar DTO estruturado.
```

### 11.2 DocumentOcrExtractor

Responsável por:

```text
Executar Tesseract.
Definir idioma por+eng.
Controlar timeout.
Capturar erros.
Medir duração.
Não escrever texto integral em logs.
```

### 11.3 DocumentImagePreprocessor

Responsável por:

```text
Converter HEIC quando suportado.
Converter PDF digitalizado em imagens temporárias.
Melhorar contraste quando possível.
Normalizar orientação quando possível.
Limpar ficheiros temporários.
```

### 11.4 DocumentKeywordClassifier

Responsável por:

```text
Analisar palavras-chave portuguesas.
Pontuar categorias.
Devolver sinais determinísticos.
Trabalhar mesmo sem Ollama.
```

### 11.5 DocumentLayoutSignalExtractor

Responsável por:

```text
Extrair sinais simples de layout.
Detetar presença de tabelas, cabeçalhos, NIF/IBAN mascarados, blocos repetidos.
Não executar reconhecimento biométrico.
Não guardar coordenadas com dados sensíveis sem necessidade.
```

### 11.6 LocalAiDocumentClassifier

Responsável por:

```text
Chamar Ollama local se ativo.
Enviar texto OCR minimizado/truncado.
Usar prompt estruturado.
Validar resposta JSON.
Devolver resultado normalizado.
Falhar de forma controlada.
```

### 11.7 DocumentClassificationScorer

Responsável por:

```text
Combinar score de keywords.
Combinar score de layout.
Combinar score de IA local.
Aplicar limiar de confiança.
Gerar classificação final.
Gerar flag low_confidence quando necessário.
```

### 11.8 DocumentClassificationPipeline

Responsável por:

```text
Executar OCR.
Executar classificação por palavras-chave.
Executar classificação por layout.
Executar classificação por IA local quando configurado.
Combinar resultados.
Guardar detected_document_type.
Guardar confidence.
Guardar OCR disponível.
Criar flags.
Criar logs minimizados.
Atualizar DocumentAiAnalysis.
Emitir eventos.
Auditar.
```

---

## 12. DTOs Recomendados

Criar DTOs tipados, se o projeto já usa esse padrão:

```text
App\Data\DocumentIntelligence\OcrResult
App\Data\DocumentIntelligence\KeywordClassificationResult
App\Data\DocumentIntelligence\LayoutSignalResult
App\Data\DocumentIntelligence\AiClassificationResult
App\Data\DocumentIntelligence\DocumentClassificationResult
```

Estrutura mínima de `DocumentClassificationResult`:

```php
final readonly class DocumentClassificationResult
{
    public function __construct(
        public DocumentAiDocumentType $documentType,
        public string $label,
        public float $confidence,
        public string $source,
        /** @var list<string> */
        public array $signals,
        public bool $requiresManualReview,
    ) {}
}
```

Se o projeto não usa DTOs, usar arrays estruturados com PHPDoc preciso.

Não usar `mixed` sem necessidade.

---

## 13. Prompt da IA Local

Criar `DocumentClassificationPromptBuilder`.

Prompt base obrigatório:

```text
Classifique este documento numa das categorias suportadas e devolva apenas JSON.
```

Prompt completo recomendado:

```text
Classifique este documento numa das categorias suportadas e devolva apenas JSON.

Categorias suportadas:
- cartao_cidadao
- titulo_residencia
- passaporte
- irs
- nota_liquidacao
- recibo_vencimento
- declaracao_seguranca_social
- declaracao_at
- iban
- contrato_arrendamento
- comprovativo_morada
- atestado_multiusos
- certidao_escolar
- outro

Regras:
- Responda apenas com JSON válido.
- Não inclua markdown.
- Não inclua comentários.
- Não inclua texto fora do JSON.
- Se houver dúvida relevante, use "outro" ou requires_manual_review=true.
- Não invente dados que não estejam presentes.

Formato obrigatório:
{
  "document_type": "uma_categoria_suportada",
  "label": "label legível",
  "confidence": 0.0,
  "signals": ["sinal_1", "sinal_2"],
  "reason": "resumo curto sem dados pessoais",
  "requires_manual_review": true
}

Texto OCR:
{{ocr_text}}
```

Antes de enviar para Ollama:

```text
Truncar texto OCR para limite configurável.
Remover excesso de espaços.
Evitar enviar páginas inteiras se não necessário.
Não enviar ficheiro original.
Não enviar imagem.
```

---

## 14. Palavras-Chave Recomendadas

Criar mapa configurável em:

```text
config/document-ai-classification.php
```

Exemplos de sinais:

```php
return [
    'thresholds' => [
        'auto_classification' => 0.90,
        'manual_review' => 0.70,
    ],
    'keywords' => [
        'cartao_cidadao' => [
            'cartao de cidadao',
            'republica portuguesa',
            'documento de identificacao',
            'numero de identificacao civil',
        ],
        'titulo_residencia' => [
            'titulo de residencia',
            'autorizacao de residencia',
            'servico de estrangeiros',
            'aima',
        ],
        'passaporte' => [
            'passaporte',
            'passport',
            'republica portuguesa',
            'passport no',
        ],
        'irs' => [
            'declaracao de rendimentos',
            'modelo 3',
            'irs',
            'autoridade tributaria',
        ],
        'nota_liquidacao' => [
            'nota de liquidacao',
            'liquidacao de irs',
            'demonstracao de liquidacao',
        ],
        'recibo_vencimento' => [
            'recibo de vencimento',
            'remuneracao',
            'vencimento base',
            'subsidio de alimentacao',
        ],
        'declaracao_seguranca_social' => [
            'seguranca social',
            'instituto da seguranca social',
            'declaracao de situacao contributiva',
        ],
        'declaracao_at' => [
            'autoridade tributaria',
            'certidao',
            'situacao tributaria',
            'portal das financas',
        ],
        'iban' => [
            'iban',
            'identificacao bancaria',
            'comprovativo de iban',
            'numero internacional de conta bancaria',
        ],
        'contrato_arrendamento' => [
            'contrato de arrendamento',
            'senhorio',
            'arrendatario',
            'renda mensal',
        ],
        'comprovativo_morada' => [
            'comprovativo de morada',
            'domicilio fiscal',
            'morada fiscal',
            'fatura',
        ],
        'atestado_multiusos' => [
            'atestado medico de incapacidade multiuso',
            'incapacidade permanente global',
            'junta medica',
        ],
        'certidao_escolar' => [
            'certidao escolar',
            'declaracao de matricula',
            'estabelecimento de ensino',
            'ano letivo',
        ],
    ],
];
```

Não hardcodar tudo dentro do service se o projeto já usa config para regras.

---

## 15. Configuração Obrigatória

Atualizar ou criar:

```text
config/document-ai.php
config/document-ai-classification.php
```

Variáveis recomendadas:

```env
DOCUMENT_AI_ENABLED=true
DOCUMENT_AI_CLASSIFICATION_ENABLED=true
DOCUMENT_AI_OCR_ENABLED=true
DOCUMENT_AI_TESSERACT_BINARY=tesseract
DOCUMENT_AI_TESSERACT_LANG=por+eng
DOCUMENT_AI_PDFTOTEXT_BINARY=pdftotext
DOCUMENT_AI_PDFTOPPM_BINARY=pdftoppm
DOCUMENT_AI_MAGICK_BINARY=magick
DOCUMENT_AI_OLLAMA_ENABLED=false
DOCUMENT_AI_OLLAMA_URL=http://127.0.0.1:11434
DOCUMENT_AI_OLLAMA_MODEL=gemma3:4b
DOCUMENT_AI_CLASSIFICATION_MIN_CONFIDENCE=0.90
DOCUMENT_AI_CLASSIFICATION_MANUAL_REVIEW_THRESHOLD=0.70
DOCUMENT_AI_OCR_MAX_PAGES=10
DOCUMENT_AI_OCR_TIMEOUT=120
DOCUMENT_AI_CLASSIFICATION_TIMEOUT=120
```

Não colocar credenciais.

Não tornar Ollama obrigatório.

Não quebrar ambientes que não tenham OCR instalado.

---

## 16. Jobs e Pipeline

Reutilizar:

```text
ProcessDocumentAiJob
DocumentAiPipeline
```

Se necessário, criar job separado:

```text
App\Jobs\ClassifyDocumentAiJob
```

Preferência:

```text
Usar ProcessDocumentAiJob para orquestrar OCR + classificação se a Sprint 27 já o criou.
Criar ClassifyDocumentAiJob apenas se o projeto separar claramente etapas de pipeline.
```

Requisitos:

```text
Job deve receber apenas ID da análise.
Job não deve serializar OCR text.
Job não deve serializar raw_ai_json.
Job deve usar afterCommit quando despachado após upload.
Job deve falhar de forma controlada.
Job deve atualizar status e flags.
Job deve emitir eventos.
```

---

## 17. Eventos Recomendados

Criar ou completar:

```text
App\Events\DocumentOcrStarted
App\Events\DocumentOcrCompleted
App\Events\DocumentOcrFailed
App\Events\DocumentClassificationStarted
App\Events\DocumentClassificationCompleted
App\Events\DocumentClassificationFailed
App\Events\DocumentClassificationRequiresReview
```

Eventos devem transportar:

```text
ID da análise
ID do documento, se seguro
tipo classificado, se já calculado
confiança, se já calculada
```

Eventos não devem transportar:

```text
raw_text
ocr_text
raw_ai_json
ficheiro original
path privado
dados pessoais extraídos
```

---

## 18. Auditoria e RGPD

Auditar:

```text
Início de OCR
Conclusão de OCR
Falha de OCR
Início de classificação
Conclusão de classificação
Falha de classificação
Classificação com baixa confiança
Marcação para revisão manual
Consulta backoffice do detalhe autorizado
```

Não auditar:

```text
Texto OCR integral
JSON bruto integral
Dados pessoais extraídos
Path privado completo
Conteúdo do documento
```

Auditoria deve conter apenas:

```text
ID da análise
ID do documento
Estado anterior
Estado novo
Tipo documental classificado
Confiança
Fonte de classificação
Utilizador responsável, quando existir
Timestamp
```

Se a auditoria existente tiver enum de ações, adicionar ações específicas seguindo o padrão local.

Se não existir mecanismo de auditoria claro, registar limitação no relatório de qualidade e usar `document_ai_processing_logs` minimizados.

---

## 19. Policies e Permissões

Criar ou completar policy:

```text
DocumentAiAnalysisPolicy
```

Permissões mínimas:

```text
viewAny
view
viewSensitiveOutput
markManualReview
```

Regras:

```text
Guest não acede.
Candidato não acede ao painel de classificação IA.
Técnico autorizado vê listagem.
Auditor pode consultar sem alterar, se perfil existir.
Admin pode marcar revisão manual.
raw_text/raw_ai_json só para perfis explicitamente autorizados.
Downloads continuam controlados pelos controllers existentes.
```

Nunca confiar apenas no frontend.

---

## 20. Backoffice — Rotas e Controllers

Criar ou completar:

```text
App\Http\Controllers\Backoffice\DocumentAiClassificationController
```

Métodos recomendados:

```php
index()
show(DocumentAiAnalysis $analysis)
markManualReview(DocumentAiAnalysis $analysis)
```

Rotas sugeridas, adaptar ao projeto:

```php
Route::middleware(['auth'])
    ->prefix('backoffice/documentos/ia')
    ->name('backoffice.document-ai.')
    ->group(function (): void {
        Route::get('/classificacoes', [DocumentAiClassificationController::class, 'index'])
            ->name('classifications.index');
        Route::get('/classificacoes/{analysis}', [DocumentAiClassificationController::class, 'show'])
            ->name('classifications.show');
        Route::post('/classificacoes/{analysis}/revisao-manual', [DocumentAiClassificationController::class, 'markManualReview'])
            ->name('classifications.manual-review');
    });
```

Se o projeto já tiver grupo de backoffice com middleware próprio, usar esse grupo.

Não duplicar prefixos.

Não criar rotas públicas.

---

## 21. Form Requests

Criar:

```text
App\Http\Requests\Backoffice\MarkDocumentAiManualReviewRequest
App\Http\Requests\Backoffice\FilterDocumentAiClassificationsRequest
```

Filtros recomendados:

```php
'document_type' => ['nullable', 'string'],
'status' => ['nullable', 'string'],
'ocr_available' => ['nullable', 'boolean'],
'requires_manual_review' => ['nullable', 'boolean'],
'confidence_min' => ['nullable', 'numeric', 'min:0', 'max:1'],
'confidence_max' => ['nullable', 'numeric', 'min:0', 'max:1'],
'created_from' => ['nullable', 'date'],
'created_until' => ['nullable', 'date', 'after_or_equal:created_from'],
```

Validar enums com `Rule::enum()` quando disponível.

---

## 22. Views / Páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/document-ai/classifications/index.blade.php
resources/views/backoffice/document-ai/classifications/show.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes na stack real.

Não mudar stack frontend.

O index deve mostrar:

```text
Documento
Classificação IA
Confiança
Estado
OCR disponível
Flags
Data
Ações autorizadas
```

O detalhe deve mostrar:

```text
Metadados do documento
Estado OCR
Estado classificação
Tipo classificado
Confiança
Fonte
Sinais
Flags
Logs técnicos minimizados
Botão de revisão manual, se autorizado
```

Não mostrar por defeito:

```text
Texto OCR integral
JSON bruto
Path privado
Dados pessoais extraídos
```

Se mostrar excerto OCR, truncar e auditar acesso.

---

## 23. Precisão e Dataset de Teste

Critério de aceitação:

```text
Precisão superior a 90% em documentos portugueses comuns.
```

Implementar forma objetiva de medir precisão sem dados reais.

Criar fixtures sintéticas:

```text
tests/Fixtures/document-intelligence/classification/cartao_cidadao.txt
tests/Fixtures/document-intelligence/classification/titulo_residencia.txt
tests/Fixtures/document-intelligence/classification/passaporte.txt
tests/Fixtures/document-intelligence/classification/irs.txt
tests/Fixtures/document-intelligence/classification/nota_liquidacao.txt
tests/Fixtures/document-intelligence/classification/recibo_vencimento.txt
tests/Fixtures/document-intelligence/classification/declaracao_seguranca_social.txt
tests/Fixtures/document-intelligence/classification/declaracao_at.txt
tests/Fixtures/document-intelligence/classification/iban.txt
tests/Fixtures/document-intelligence/classification/contrato_arrendamento.txt
tests/Fixtures/document-intelligence/classification/comprovativo_morada.txt
tests/Fixtures/document-intelligence/classification/atestado_multiusos.txt
tests/Fixtures/document-intelligence/classification/certidao_escolar.txt
tests/Fixtures/document-intelligence/classification/outro.txt
```

Fixtures devem conter apenas dados fictícios.

Não usar NIF real, IBAN real, número de cartão real, nomes reais, moradas reais completas ou dados de candidatos reais.

Criar teste:

```text
tests/Feature/DocumentIntelligence/DocumentClassificationAccuracyTest.php
```

O teste deve:

```text
executar classificador determinístico sem depender obrigatoriamente de Ollama;
classificar fixtures sintéticas;
calcular accuracy;
exigir accuracy >= 0.90 no dataset de teste;
documentar limitações;
```

Nota:

```text
A precisão real em produção deve ser medida posteriormente com amostras anonimizadas e validação humana.
Nesta sprint, o critério de 90% aplica-se ao dataset de QA sintético e a documentos portugueses comuns anonimizados quando disponíveis.
```

---

## 24. Testes Obrigatórios

Criar ou completar testes.

### 24.1 Unit — OCR

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentTextExtractorTest.php
tests/Unit/DocumentIntelligence/DocumentOcrExtractorTest.php
tests/Unit/DocumentIntelligence/DocumentImagePreprocessorTest.php
```

Cobrir:

```text
PDF pesquisável usa extração direta.
PDF digitalizado segue caminho OCR.
JPG é aceite.
PNG é aceite.
HEIC é aceite quando suportado ou falha de forma controlada.
Ferramenta OCR ausente gera flag/erro controlado.
Texto OCR não é escrito em logs técnicos.
Ficheiros temporários são limpos.
```

### 24.2 Unit — Classificação

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentKeywordClassifierTest.php
tests/Unit/DocumentIntelligence/DocumentLayoutSignalExtractorTest.php
tests/Unit/DocumentIntelligence/DocumentClassificationPromptBuilderTest.php
tests/Unit/DocumentIntelligence/DocumentClassificationResultNormalizerTest.php
tests/Unit/DocumentIntelligence/DocumentClassificationScorerTest.php
```

Cobrir:

```text
Cada categoria suportada pode ser classificada por sinais mínimos.
JSON válido da IA é normalizado.
JSON inválido gera fallback e flag.
Baixa confiança exige revisão manual.
Categoria desconhecida vira outro.
Prompt exige apenas JSON.
Score combina OCR, keywords, layout e IA local.
```

### 24.3 Feature — Integração com upload

Criar:

```text
tests/Feature/DocumentIntelligence/DocumentOcrClassificationIntegrationTest.php
```

Cobrir:

```text
Documento submetido cria análise.
Job executa OCR/classificação.
Classificação é guardada.
Confiança é guardada.
OCR disponível é guardado.
Upload continua funcional.
Validação documental existente não é alterada.
Estado da candidatura não é alterado.
```

### 24.4 Feature — Backoffice

Criar:

```text
tests/Feature/Backoffice/DocumentAiClassificationPanelTest.php
```

Cobrir:

```text
Técnico autorizado vê painel.
Guest não acede.
Candidato não acede.
Listagem mostra documento, classificação, confiança, estado e OCR disponível.
Detalhe não mostra raw_ai_json por defeito.
Detalhe não mostra OCR integral por defeito.
Marcar revisão manual exige autorização.
Consulta sensível é auditada quando aplicável.
```

### 24.5 Queue fake

Cobrir:

```text
ProcessDocumentAiJob ou ClassifyDocumentAiJob é despachado.
Job recebe apenas ID da análise.
Job não transporta texto OCR.
Job não transporta raw_ai_json.
```

### 24.6 Eventos

Cobrir:

```text
DocumentOcrStarted
DocumentOcrCompleted
DocumentOcrFailed
DocumentClassificationStarted
DocumentClassificationCompleted
DocumentClassificationFailed
DocumentClassificationRequiresReview
```

Quando aplicável, usar:

```php
Event::fake();
Event::assertDispatched(DocumentClassificationCompleted::class);
```

### 24.7 Auditoria

Cobrir:

```text
OCR concluído é auditado.
Classificação concluída é auditada.
Revisão manual é auditada.
Auditoria não contém OCR integral.
Auditoria não contém raw_ai_json.
Auditoria não contém dados pessoais sensíveis.
```

---

## 25. PHPStan e Tipagem

Todos os ficheiros novos devem ser preparados para PHPStan.

Corrigir especialmente:

```text
missingType.generics
missingType.iterableValue
argument.type
return.type
property.notFound
method.notFound
enum/value type mismatch
invalid relation generics
array shape incompleto
```

Em relações Eloquent, usar PHPDoc generics:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiClassification> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{document_type: string, confidence: float, signals: list<string>, requires_manual_review: bool} */
```

Em DTOs, preferir propriedades readonly e tipos explícitos.

Não adicionar `mixed` sem necessidade.

Não silenciar erros com ignores genéricos.

---

## 26. Verificação PHPStan Antes de Publicar

Antes de considerar a sprint pronta para publicação, tentar executar PHPStan uma única vez usando `phpstan.neon`, se o ficheiro existir.

Executar apenas uma tentativa:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint28-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint28-before-publish.json
```

Se `vendor/bin/phpstan` não existir, documentar:

```text
PHPStan não executado porque vendor/bin/phpstan não existe.
Bloqueia publicação: depende da política do projeto.
```

Não afirmar que PHPStan passou se não foi executado.

Não ocultar erros.

No relatório final, distinguir:

```text
Erros legados já existentes
Erros introduzidos em ficheiros novos/alterados pela Sprint 28
Erros bloqueantes
Erros não bloqueantes
```

---

## 27. Comandos Finais Obrigatórios

Executar, adaptando à stack real:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se o projeto usar frontend build e houver alterações frontend:

```bash
npm run build
```

PHPStan:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint28-before-publish.json
```

Executar PHPStan com `phpstan.neon` apenas uma vez.

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

---

## 28. Documentação Obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-28-ocr-classificacao-automatica-documento.md
docs/document-intelligence/ocr.md
docs/document-intelligence/classification.md
docs/document-intelligence/classification-taxonomy.md
docs/document-intelligence/backoffice-classification-panel.md
docs/document-intelligence/security-and-gdpr.md
docs/qa/sprint-28-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 28.1 docs/document-intelligence/ocr.md

Incluir:

```text
Objetivo
Formatos suportados
PDF pesquisável
PDF digitalizado
JPG
PNG
HEIC
Ferramentas locais
Limitações
Falhas controladas
Privacidade
```

### 28.2 docs/document-intelligence/classification.md

Incluir:

```text
Objetivo
Fluxo de classificação
OCR
Keywords
Layout
IA local
Scoring
Confiança
Revisão manual
Limites funcionais
```

### 28.3 docs/document-intelligence/classification-taxonomy.md

Incluir:

```text
Categorias suportadas
Labels
Exemplos de sinais
Exemplos de palavras-chave
Casos ambíguos
Quando usar outro
```

### 28.4 docs/document-intelligence/backoffice-classification-panel.md

Incluir:

```text
Objetivo do painel
Permissões
Filtros
Campos apresentados
Campos ocultos por defeito
Auditoria
Ações disponíveis
```

### 28.5 docs/qa/sprint-28-quality-report.md

Incluir:

```text
Comandos executados
Resultado das migrations
Resultado dos testes
Resultado do PHPStan antes de publicar
Confirmação de tentativa única com phpstan.neon
Erros legados identificados
Erros novos introduzidos: sim/não
Dataset de classificação
Accuracy obtida
Cobertura de OCR
Cobertura de classificação
Cobertura de backoffice
Cobertura de queue fake
Cobertura de eventos
Cobertura de auditoria
Riscos RGPD
Riscos funcionais
Riscos técnicos
Recomendação de publicação
```

---

## 29. Critérios de Aceitação

A Sprint 28 está concluída quando:

```text
OCR automático suporta PDF pesquisável.
OCR automático suporta PDF digitalizado.
OCR automático suporta JPG.
OCR automático suporta PNG.
OCR automático suporta HEIC quando ambiente suporta ImageMagick/libheif.
Falhas de OCR são controladas e não quebram upload.
Sistema classifica documentos nas categorias suportadas.
Classificação combina OCR, palavras-chave, layout e IA local quando disponível.
Ollama é opcional e não bloqueia classificação determinística.
Prompt estruturado exige JSON.
JSON inválido da IA é tratado com fallback.
Classificação e confiança ficam guardadas.
OCR disponível fica guardado.
Baixa confiança gera revisão manual.
Backoffice tem painel com Documento, Classificação IA, Confiança, Estado e OCR disponível.
Painel respeita auth, policies e auditoria.
raw_text, ocr_text e raw_ai_json não aparecem por defeito.
Documentos continuam privados.
Nenhuma validação funcional é alterada.
Nenhum estado de candidatura é alterado automaticamente.
Precisão superior a 90% é demonstrada em dataset QA sintético/anonimizado.
Testes Unit foram criados.
Testes Feature foram criados.
Testes Queue fake foram criados.
Testes Eventos foram criados.
Testes Auditoria foram criados quando o módulo existe.
PHPStan foi tentado antes de publicar, usando phpstan.neon uma única vez quando disponível.
php artisan route:list executa sem erro ou falha é documentada.
php artisan test executa sem erro ou falha é documentada.
php artisan migrate executa sem erro se houver migrations novas ou falha é documentada.
./vendor/bin/pint executa sem erro se existir ou alterações são documentadas.
Documentação foi criada/atualizada.
Não foram usadas APIs pagas.
Não foram usados dados pessoais reais.
Não foram usadas credenciais.
Não foram implementadas funcionalidades fora de âmbito.
```

---

## 30. Fora de Âmbito

Não implementar nesta sprint:

```text
Validação automática final de documentos.
Aprovação automática de documentos.
Reprovação automática de documentos.
Exclusão automática de candidaturas.
Alteração automática de pontuação.
Alteração automática de elegibilidade.
Alteração automática de tipologia.
Alteração automática de renda.
Alteração automática de listas provisórias/definitivas.
Reconhecimento facial.
Biometria.
Verificação de autenticidade documental.
Consulta externa à AT.
Consulta externa à Segurança Social.
Consulta bancária.
Integração com CMD/autenticacao.gov.
Assinatura digital.
APIs pagas de OCR ou IA.
Treino/fine-tuning de modelos.
```

---

## 31. Riscos e Cuidados Específicos

### 31.1 Risco de falsa confiança

A classificação automática pode errar.

Mitigação:

```text
Mostrar confiança.
Usar revisão manual para baixa confiança.
Não tomar decisões funcionais automáticas.
Auditar classificação.
Medir accuracy em dataset controlado.
```

### 31.2 Risco RGPD

OCR e IA podem processar dados pessoais sensíveis.

Mitigação:

```text
Storage privado.
Logs minimizados.
raw_text oculto por defeito.
raw_ai_json oculto por defeito.
Sem APIs pagas/externas.
Permissões explícitas.
Auditoria.
```

### 31.3 Risco operacional

Tesseract, Poppler, ImageMagick ou suporte HEIC podem não existir no servidor.

Mitigação:

```text
Verificar disponibilidade por service.
Falhar de forma controlada.
Documentar dependências.
Não bloquear upload.
Permitir revisão manual.
```

---

## 32. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Dependências da Sprint 27 reaproveitadas
5. Models criados ou alterados
6. Migrations criadas
7. Enums criados
8. Services criados ou alterados
9. DTOs criados ou alterados, se aplicável
10. Jobs criados ou alterados
11. Eventos criados ou alterados
12. Controllers criados ou alterados
13. Form Requests criados ou alterados
14. Policies criadas ou alteradas
15. Rotas backoffice criadas ou alteradas
16. Views/components criados ou alterados
17. Estado do OCR para PDF pesquisável
18. Estado do OCR para PDF digitalizado
19. Estado do OCR para JPG
20. Estado do OCR para PNG
21. Estado do OCR para HEIC
22. Estado da classificação automática
23. Categorias documentais suportadas
24. Estado do prompt estruturado JSON
25. Estado da combinação OCR/keywords/layout/IA local
26. Estado do painel backoffice
27. Estado da confiança/classification score
28. Accuracy obtida no dataset QA
29. Estado da auditoria
30. Estado dos logs minimizados
31. Confirmação de ausência de APIs pagas
32. Testes Unit criados ou alterados
33. Testes Feature criados ou alterados
34. Testes Queue fake criados ou alterados
35. Testes Eventos criados ou alterados
36. Testes Auditoria criados ou alterados
37. Resultado de php artisan route:list
38. Resultado de php artisan migrate, se aplicável
39. Resultado de php artisan test
40. Resultado de ./vendor/bin/pint, se aplicável
41. Resultado de npm run build, se aplicável
42. Resultado PHPStan antes de publicar
43. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
44. Erros PHPStan legados considerados
45. Novos erros PHPStan introduzidos pela Sprint 28: sim/não
46. Documentação criada ou atualizada
47. Riscos RGPD ainda existentes
48. Riscos técnicos ainda existentes
49. Pendências técnicas
50. Confirmação de que não foram usados dados pessoais reais
51. Confirmação de que não foram usadas credenciais
52. Confirmação de que não foram implementadas funcionalidades fora de âmbito
53. Recomendação objetiva para publicar ou não publicar
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 33. Definition of Done

A Sprint 28 só está concluída quando o sistema executar OCR local controlado para PDF pesquisável, PDF digitalizado, JPG, PNG e HEIC quando suportado, classificar automaticamente documentos nas categorias suportadas com combinação de OCR, palavras-chave, layout e IA local opcional, apresentar painel backoffice seguro com Documento, Classificação IA, Confiança, Estado e OCR disponível, demonstrar precisão superior a 90% em dataset QA sintético/anonimizado, manter documentos privados, auditar ações relevantes, cobrir Unit/Feature/Queue/Eventos/Auditoria, documentar riscos e executar PHPStan antes de publicação com uma única tentativa usando `phpstan.neon` quando disponível, sem alterar validações, pontuação, elegibilidade, workflows ou decisões administrativas existentes.

---

## 34. Execução Imediata

Executa agora apenas:

```text
Sprint 28 — OCR e Classificação Automática do Documento
```

Fim da master prompt da Sprint 28.

---

## 35. Estado de Execução

Sprint executada em 2026-06-21.

### Implementado

- OCR local controlado através de `DocumentTextExtractor`, `DocumentImagePreprocessor` e `DocumentOcrExtractor`.
- Classificação automática com `DocumentKeywordClassifier`, `DocumentLayoutSignalExtractor`, `LocalAiDocumentClassifier`, `DocumentClassificationScorer` e `DocumentClassificationPipeline`.
- Enums `DocumentAiOcrStatus`, `DocumentAiClassificationStatus` e `DocumentAiDocumentType`.
- Campos de OCR/classificação em `document_ai_analyses`.
- Painel backoffice `backoffice.document-ai.classifications.*`.
- Permissões via `DocumentAiAnalysisPolicy`.
- Form Requests para filtros e marcação de revisão manual.
- Fixtures sintéticas e testes Unit/Feature.

### Fora de âmbito preservado

- Não há validação automática de documentos.
- Não há exclusão automática de candidaturas.
- Não há alteração automática de estados documentais funcionais.
- Não há decisão administrativa, elegibilidade, pontuação, ranking ou listas.
- Não há APIs pagas.

### Accuracy QA

O teste `DocumentClassificationAccuracyTest` valida precisão mínima de 90% sobre fixtures sintéticas anonimizadas.

### Pendências

- Testar com documentos reais anonimizados em staging.
- Confirmar ferramentas locais instaladas.
- Avançar para Sprint 29 apenas após validação explícita.
