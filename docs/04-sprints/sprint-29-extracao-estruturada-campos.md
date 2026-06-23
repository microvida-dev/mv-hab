# Sprint 29 — Extração Estruturada de Campos

## 1. Objetivo da Sprint

Implementar a extração automática de dados úteis a partir dos documentos submetidos, guardando os resultados em JSON estruturado e em campos normalizados de apoio à revisão técnica.

Esta sprint evolui o módulo **Document Intelligence** criado nas Sprints 27 e 28:

```text
Sprint 27: infraestrutura base de análise documental por IA
Sprint 28: OCR e classificação automática do documento
Sprint 29: extração estruturada de campos por tipo documental
```

A extração estruturada deve apoiar a triagem e revisão documental, mas **não deve alterar automaticamente qualquer candidatura**.

Regra central:

```text
Nenhum valor extraído altera automaticamente candidatura, agregado, rendimentos, pontuação, elegibilidade, tipologia, renda, decisão, lista, contrato ou workflow.
```

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 29 — Extração Estruturada de Campos
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Dependências das Sprints 27 e 28

Assumir que já existem, ou devem ser reaproveitados se existirem:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
DocumentAiStatus
DocumentAiDocumentType
DocumentAiOcrStatus
DocumentAiClassificationStatus
DocumentAiPipeline
DocumentClassificationPipeline
ProcessDocumentAiJob
OCR local
Classificação automática
raw_ai_json
ocr_text
detected_document_type
classification_confidence
Auditoria
Backoffice de classificação
```

Antes de implementar, confirmar a estrutura real existente no projeto.

Se alguma peça das Sprints 27/28 ainda não existir, criar apenas adaptação mínima compatível, sem duplicar módulos, services, jobs ou tabelas.

Não criar uma segunda infraestrutura paralela de análise documental.

---

## 4. Princípios Técnicos

Preservar todas as funcionalidades existentes.

Documentos continuam privados por defeito.

OCR, classificação e extração são auxiliares técnicos, não decisões administrativas.

Toda a lógica crítica deve ficar em Services.

Controllers devem continuar magros.

Usar Eloquent, migrations reversíveis, casts tipados, enums, Form Requests, Policies, Services, Jobs, Events e testes, seguindo os padrões reais do projeto.

Usar DTOs tipados ou arrays estruturados com PHPDoc rigoroso.

Não expor dados pessoais em logs técnicos, mensagens de erro, eventos ou responses não autorizadas.

Não guardar credenciais.

Não usar dados pessoais reais em testes, fixtures, seeders ou documentação.

Não introduzir APIs pagas.

Não alterar regras de elegibilidade, pontuação, renda, validação documental, listas ou workflows.

---

## 5. Stack Permitida

Reutilizar stack local/gratuita:

```text
OCR: Tesseract OCR
PDF: Poppler
Imagem: ImageMagick
IA local: Ollama local
Modelos recomendados: Gemma 3 4B ou Qwen 2.5 7B Instruct
```

Não efetuar chamadas para APIs pagas.

Não integrar OpenAI, Anthropic, Google Vision, Azure AI, AWS Textract, Mistral Cloud, Mindee, Nanonets, OCR.space ou qualquer fornecedor externo pago nesta sprint.

Se Ollama ou OCR local não estiverem disponíveis:

```text
falhar de forma controlada;
registar flag técnica;
guardar estado de extração como failed, unavailable ou manual_review;
manter upload documental funcional;
manter classificação/manual review funcional;
documentar limitação;
não bloquear candidatura.
```

---

## 6. Funcionalidades Obrigatórias

### 6.1 Extração estruturada por tipo documental

Extrair campos úteis com base no tipo documental classificado na Sprint 28.

Tipos obrigatórios:

```text
Cartão de Cidadão
Título de Residência
IRS
Nota de Liquidação
Recibo de vencimento
Declaração Segurança Social
Contrato de arrendamento
Atestado Multiusos
```

O sistema deve guardar:

```text
JSON estruturado
Campos normalizados em document_ai_fields
Confiança por campo
Fonte por campo
Flags de baixa confiança
Flags de inconsistência
Logs técnicos minimizados
Auditoria
```

### 6.2 Não alteração automática da candidatura

Valores extraídos não podem:

```text
preencher automaticamente candidatura sem confirmação;
alterar agregado;
alterar rendimentos declarados;
alterar pontuação;
alterar elegibilidade;
alterar tipologia;
alterar renda;
alterar estado de candidatura;
validar documento;
reprovar documento;
criar lista;
alterar contrato;
notificar decisão ao candidato.
```

Valores extraídos podem:

```text
aparecer em painel técnico autorizado;
ser comparados com dados existentes;
gerar flags para revisão manual;
ser exportados apenas por mecanismos autorizados;
ser usados futuramente como sugestão, mediante sprint própria.
```

---

## 7. Campos Obrigatórios por Documento

### 7.1 Cartão de Cidadão

Extrair:

```text
Nome
Data nascimento
Sexo
Nacionalidade
Número documento
Validade
NIF, quando presente
```

Chaves JSON recomendadas:

```json
{
  "document_type": "cartao_cidadao",
  "fields": {
    "name": null,
    "birth_date": null,
    "sex": null,
    "nationality": null,
    "document_number": null,
    "expiry_date": null,
    "nif": null
  }
}
```

Cuidados:

```text
Não fazer reconhecimento facial.
Não extrair fotografia.
Não validar autenticidade.
Não guardar biometria.
NIF deve ser validado apenas por formato/dígito de controlo se existir helper local.
```

### 7.2 Título de Residência

Extrair:

```text
Nome
Número
Validade
Nacionalidade
```

Chaves JSON recomendadas:

```json
{
  "document_type": "titulo_residencia",
  "fields": {
    "name": null,
    "document_number": null,
    "expiry_date": null,
    "nationality": null
  }
}
```

Cuidados:

```text
Não validar autenticidade.
Não consultar entidades externas.
Não inferir estatuto migratório para decisão automática.
```

### 7.3 IRS

Extrair:

```text
Ano fiscal
Sujeito passivo
NIF
Rendimento global
Rendimento coletável
```

Chaves JSON recomendadas:

```json
{
  "document_type": "irs",
  "fields": {
    "fiscal_year": null,
    "taxpayer_name": null,
    "nif": null,
    "gross_income": null,
    "taxable_income": null
  }
}
```

Cuidados:

```text
Valores monetários devem ser normalizados para decimal.
Ano fiscal deve ser inteiro.
Não alterar rendimentos declarados na candidatura.
Não recalcular elegibilidade.
```

### 7.4 Nota de Liquidação

Extrair:

```text
Ano
Total rendimento
Estado
```

Chaves JSON recomendadas:

```json
{
  "document_type": "nota_liquidacao",
  "fields": {
    "year": null,
    "total_income": null,
    "status": null
  }
}
```

Cuidados:

```text
Estado é estado documental/fiscal textual, não estado de candidatura.
Não alterar candidatura.
Não validar dívida fiscal.
Não consultar AT.
```

### 7.5 Recibo de Vencimento

Extrair:

```text
Entidade patronal
Trabalhador
Salário base
Ilíquido
Líquido
```

Chaves JSON recomendadas:

```json
{
  "document_type": "recibo_vencimento",
  "fields": {
    "employer": null,
    "worker": null,
    "base_salary": null,
    "gross_amount": null,
    "net_amount": null
  }
}
```

Cuidados:

```text
Valores monetários devem ser normalizados para decimal.
Não alterar rendimentos do agregado.
Não calcular médias automaticamente nesta sprint.
```

### 7.6 Declaração Segurança Social

Extrair:

```text
Beneficiário
Número
Prestação
Valor
```

Chaves JSON recomendadas:

```json
{
  "document_type": "declaracao_seguranca_social",
  "fields": {
    "beneficiary": null,
    "beneficiary_number": null,
    "benefit": null,
    "amount": null
  }
}
```

Cuidados:

```text
Não consultar Segurança Social.
Não validar situação contributiva.
Não alterar apoios/rendimentos na candidatura.
```

### 7.7 Contrato de Arrendamento

Extrair:

```text
Senhorio
Inquilino
Morada
Renda
Data início
Data fim
```

Chaves JSON recomendadas:

```json
{
  "document_type": "contrato_arrendamento",
  "fields": {
    "landlord": null,
    "tenant": null,
    "address": null,
    "rent_amount": null,
    "start_date": null,
    "end_date": null
  }
}
```

Cuidados:

```text
Morada é dado pessoal.
Não alterar morada do candidato.
Não alterar habitação atual.
Não validar contrato juridicamente.
```

### 7.8 Atestado Multiusos

Extrair:

```text
Grau incapacidade
Data emissão
Entidade
Resultado
```

Chaves JSON recomendadas:

```json
{
  "document_type": "atestado_multiusos",
  "fields": {
    "disability_degree": null,
    "issued_at": null,
    "issuing_entity": null,
    "result": null
  }
}
```

Cuidados:

```text
Dados de incapacidade são dados sensíveis de saúde.
Aplicar minimização máxima.
Não expor no painel sem permissão explícita.
Não alterar pontuação/bonificação automaticamente.
Não gerar decisão automática.
```

---

## 8. Estrutura JSON Obrigatória

Guardar tudo em JSON estruturado.

Formato base recomendado:

```json
{
  "schema_version": "1.0",
  "document_type": "irs",
  "document_label": "IRS",
  "extraction_status": "completed",
  "extraction_confidence": 0.91,
  "source": "ocr_keywords_local_ai",
  "model": "gemma3:4b",
  "fields": {
    "fiscal_year": {
      "value": 2025,
      "normalized_value": 2025,
      "type": "integer",
      "confidence": 0.96,
      "source": "ocr_local_ai",
      "page": 1,
      "requires_review": false
    }
  },
  "flags": [
    {
      "code": "low_confidence_field",
      "field": "taxable_income",
      "severity": "medium",
      "message": "Campo extraído com confiança inferior ao limiar."
    }
  ],
  "metadata": {
    "ocr_available": true,
    "classification_confidence": 0.94,
    "processed_at": "2026-06-21T00:00:00+01:00"
  }
}
```

Regras:

```text
JSON estruturado deve ser guardado em raw_ai_json ou extraction_json, conforme estrutura existente.
Campos pesquisáveis devem ser replicados em document_ai_fields.
Dados sensíveis não devem ser indexados de forma desnecessária.
Valores monetários devem ter normalized_value decimal.
Datas devem ter normalized_value ISO 8601 quando possível.
NIF deve ter normalized_value apenas se formato for plausível.
Campos sem confiança suficiente devem requires_review=true.
```

---

## 9. Modelo de Dados

Reutilizar:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
```

Se necessário, criar migration incremental para `document_ai_analyses`:

```text
extraction_status string nullable indexed
extraction_schema_version string nullable
extraction_json json nullable
extraction_confidence decimal nullable
extraction_model string nullable
extraction_prompt_version string nullable
extraction_started_at timestamp nullable
extraction_completed_at timestamp nullable
extraction_failed_at timestamp nullable
extraction_requires_manual_review boolean default false
```

Se `raw_ai_json` já existir e for o campo canónico para saída IA, pode ser usado para armazenar o JSON bruto, mantendo `extraction_json` para o payload validado/normalizado.

Atualizar `document_ai_fields` se necessário:

```text
document_ai_analysis_id
document_type string nullable indexed
key string indexed
label string nullable
value text nullable
normalized_value text nullable
value_type string nullable
confidence decimal nullable
source string nullable
page unsignedInteger nullable
bbox json nullable
requires_review boolean default false
metadata json nullable
timestamps
```

Índices recomendados:

```text
document_ai_analysis_id
document_type
key
requires_review
confidence
extraction_status
extraction_requires_manual_review
```

Regras RGPD:

```text
extraction_json é dado sensível.
document_ai_fields pode conter dados sensíveis.
Não criar índices full-text em valores pessoais nesta sprint.
Não copiar dados extraídos para tabelas funcionais da candidatura.
```

---

## 10. Enums Obrigatórios

Criar ou completar:

```text
App\Enums\DocumentAiExtractionStatus
App\Enums\DocumentAiExtractedFieldType
App\Enums\DocumentAiExtractionSource
```

### 10.1 DocumentAiExtractionStatus

Valores:

```text
pending
processing
completed
failed
manual_review
unsupported_document_type
low_confidence
```

### 10.2 DocumentAiExtractedFieldType

Valores:

```text
string
date
integer
decimal
money
percentage
identifier
address
enum
unknown
```

### 10.3 DocumentAiExtractionSource

Valores:

```text
ocr
regex
keywords
layout
local_ai
combined
manual
```

Usar casts nos models.

Evitar strings soltas fora de migrations e testes específicos.

---

## 11. Services Obrigatórios

Criar ou completar services em namespace coerente:

```text
App\Services\DocumentIntelligence\DocumentFieldExtractionPipeline
App\Services\DocumentIntelligence\DocumentExtractionPromptBuilder
App\Services\DocumentIntelligence\LocalAiFieldExtractor
App\Services\DocumentIntelligence\RegexFieldExtractor
App\Services\DocumentIntelligence\DocumentFieldNormalizer
App\Services\DocumentIntelligence\DocumentExtractionSchemaRegistry
App\Services\DocumentIntelligence\DocumentExtractionResultValidator
App\Services\DocumentIntelligence\DocumentExtractionPersister
App\Services\DocumentIntelligence\DocumentExtractionScorer
```

Se existir `DocumentAiPipeline`, integrar nele sem duplicar orquestração.

### 11.1 DocumentFieldExtractionPipeline

Responsável por:

```text
Receber DocumentAiAnalysis.
Confirmar OCR disponível.
Confirmar tipo documental classificado.
Carregar schema de extração do tipo documental.
Executar extração regex/determinística.
Executar IA local quando configurada.
Normalizar campos.
Validar payload.
Calcular confiança por campo.
Calcular confiança global.
Persistir JSON estruturado.
Persistir document_ai_fields.
Criar flags.
Criar logs minimizados.
Emitir eventos.
Auditar.
```

### 11.2 DocumentExtractionSchemaRegistry

Responsável por:

```text
Definir campos esperados por tipo documental.
Definir tipos de campo.
Definir campos sensíveis.
Definir obrigatoriedade técnica.
Definir labels.
Definir regras de normalização.
```

### 11.3 RegexFieldExtractor

Responsável por:

```text
Extrair padrões simples.
Extrair datas.
Extrair montantes.
Extrair NIF quando presente.
Extrair percentagens.
Extrair anos fiscais.
Extrair IBAN apenas se documento classificado como IBAN em sprint anterior ou se schema futuro o exigir.
Não validar dados por serviços externos.
```

### 11.4 LocalAiFieldExtractor

Responsável por:

```text
Chamar Ollama local se ativo.
Enviar texto OCR truncado/minimizado.
Usar prompt estruturado por tipo documental.
Exigir JSON válido.
Validar resposta contra schema.
Falhar de forma controlada.
Não enviar ficheiro original.
Não enviar imagens.
```

### 11.5 DocumentFieldNormalizer

Responsável por:

```text
Normalizar datas para ISO 8601 quando possível.
Normalizar dinheiro para decimal.
Normalizar percentagens para decimal/inteiro definido.
Normalizar NIF removendo espaços.
Normalizar nomes removendo ruído evidente.
Manter valor original em value.
Guardar valor normalizado em normalized_value.
```

### 11.6 DocumentExtractionResultValidator

Responsável por:

```text
Validar estrutura JSON.
Validar campos permitidos.
Remover campos inesperados.
Validar tipos.
Validar intervalo plausível de datas.
Validar montantes positivos quando aplicável.
Validar grau de incapacidade entre 0 e 100 quando aplicável.
Criar flags de inconsistência.
```

### 11.7 DocumentExtractionPersister

Responsável por:

```text
Guardar extraction_json.
Guardar raw_ai_json quando aplicável.
Sincronizar document_ai_fields.
Criar flags.
Criar logs.
Não copiar valores para candidatura.
Não alterar documentos funcionais.
```

### 11.8 DocumentExtractionScorer

Responsável por:

```text
Calcular confiança por campo.
Calcular confiança global.
Marcar campos abaixo do limiar.
Marcar extração como manual_review quando necessário.
```

---

## 12. DTOs Recomendados

Criar DTOs tipados, se o projeto já usa esse padrão:

```text
App\Data\DocumentIntelligence\ExtractedDocumentField
App\Data\DocumentIntelligence\DocumentExtractionResult
App\Data\DocumentIntelligence\DocumentExtractionSchema
App\Data\DocumentIntelligence\DocumentExtractionFlag
```

Estrutura mínima:

```php
final readonly class ExtractedDocumentField
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public mixed $value,
        public mixed $normalizedValue,
        public float $confidence,
        public string $source,
        public bool $requiresReview,
        public ?int $page = null,
    ) {}
}
```

Se PHPStan estiver rigoroso, evitar `mixed` no DTO e usar union types ou arrays estruturados quando possível:

```php
public string|int|float|bool|null $value
public string|int|float|bool|null $normalizedValue
```

Estrutura mínima de resultado:

```php
final readonly class DocumentExtractionResult
{
    /**
     * @param list<ExtractedDocumentField> $fields
     * @param list<array{code: string, field?: string, severity: string, message: string}> $flags
     */
    public function __construct(
        public string $schemaVersion,
        public DocumentAiDocumentType $documentType,
        public DocumentAiExtractionStatus $status,
        public float $confidence,
        public array $fields,
        public array $flags,
        public bool $requiresManualReview,
    ) {}
}
```

---

## 13. Schemas de Extração

Criar configuração:

```text
config/document-ai-extraction.php
```

Estrutura recomendada:

```php
return [
    'enabled' => env('DOCUMENT_AI_EXTRACTION_ENABLED', true),
    'schema_version' => '1.0',
    'thresholds' => [
        'field_review' => (float) env('DOCUMENT_AI_EXTRACTION_FIELD_REVIEW_THRESHOLD', 0.75),
        'document_review' => (float) env('DOCUMENT_AI_EXTRACTION_DOCUMENT_REVIEW_THRESHOLD', 0.80),
    ],
    'ollama' => [
        'enabled' => env('DOCUMENT_AI_EXTRACTION_OLLAMA_ENABLED', false),
        'model' => env('DOCUMENT_AI_OLLAMA_MODEL', 'gemma3:4b'),
        'timeout' => (int) env('DOCUMENT_AI_EXTRACTION_TIMEOUT', 120),
        'max_chars' => (int) env('DOCUMENT_AI_EXTRACTION_MAX_CHARS', 12000),
    ],
    'schemas' => [
        'cartao_cidadao' => [
            'fields' => [
                'name' => ['type' => 'string', 'label' => 'Nome', 'sensitive' => true],
                'birth_date' => ['type' => 'date', 'label' => 'Data nascimento', 'sensitive' => true],
                'sex' => ['type' => 'enum', 'label' => 'Sexo', 'sensitive' => true],
                'nationality' => ['type' => 'string', 'label' => 'Nacionalidade', 'sensitive' => true],
                'document_number' => ['type' => 'identifier', 'label' => 'Número documento', 'sensitive' => true],
                'expiry_date' => ['type' => 'date', 'label' => 'Validade', 'sensitive' => true],
                'nif' => ['type' => 'identifier', 'label' => 'NIF', 'sensitive' => true],
            ],
        ],
    ],
];
```

Completar todos os schemas obrigatórios.

Não hardcodar todas as regras nos services se o projeto usa config para regras.

---

## 14. Prompt da IA Local

Criar `DocumentExtractionPromptBuilder`.

Prompt base obrigatório:

```text
Extraia apenas os campos solicitados deste documento e devolva apenas JSON válido.
```

Prompt completo recomendado:

```text
Extraia apenas os campos solicitados deste documento e devolva apenas JSON válido.

Tipo documental classificado:
{{document_type}}

Campos solicitados:
{{fields_schema}}

Regras:
- Responda apenas com JSON válido.
- Não inclua markdown.
- Não inclua comentários.
- Não inclua texto fora do JSON.
- Não invente valores.
- Se um campo não existir ou estiver ilegível, use null.
- Preserve o valor original em "value".
- Quando possível, devolva "normalized_value".
- A confiança deve estar entre 0 e 1.
- Não tome decisões administrativas.
- Não valide candidatura.

Formato obrigatório:
{
  "schema_version": "1.0",
  "document_type": "{{document_type}}",
  "fields": {
    "field_key": {
      "value": null,
      "normalized_value": null,
      "type": "string",
      "confidence": 0.0,
      "source": "local_ai",
      "requires_review": true
    }
  },
  "flags": []
}

Texto OCR:
{{ocr_text}}
```

Antes de enviar para Ollama:

```text
Truncar texto OCR para limite configurável.
Remover excesso de espaços.
Não enviar ficheiro original.
Não enviar imagem.
Não enviar path privado.
Não enviar dados fora do documento necessário.
```

---

## 15. Normalização e Validação

### 15.1 Datas

Normalizar para:

```text
YYYY-MM-DD
```

Aceitar formatos comuns:

```text
DD/MM/YYYY
DD-MM-YYYY
YYYY-MM-DD
DD.MM.YYYY
```

Se ambíguo:

```text
manter value original;
normalized_value null;
requires_review true;
flag ambiguous_date.
```

### 15.2 Valores monetários

Normalizar:

```text
1.234,56 €
1234,56
1 234,56 EUR
```

para decimal:

```text
1234.56
```

Se ambíguo:

```text
requires_review true;
flag ambiguous_money_value.
```

### 15.3 Percentagens

Normalizar:

```text
60%
60,00 %
```

para:

```text
60.0
```

Aplicável ao grau de incapacidade.

### 15.4 NIF

Normalizar removendo espaços e pontuação.

Validar apenas formato português quando helper local existir.

Se inválido:

```text
não descartar automaticamente;
guardar value;
normalized_value null ou value;
requires_review true;
flag invalid_nif_format.
```

### 15.5 Nomes

Remover ruído evidente:

```text
prefixos de campo;
quebras de linha internas desnecessárias;
espaços repetidos.
```

Não inferir nomes em falta.

### 15.6 Moradas

Tratar como dados pessoais.

Não normalizar agressivamente.

Não geocodificar.

Não consultar APIs externas.

---

## 16. Jobs e Pipeline

Reutilizar:

```text
ProcessDocumentAiJob
DocumentAiPipeline
DocumentClassificationPipeline
```

Se necessário, criar job separado:

```text
App\Jobs\ExtractDocumentAiFieldsJob
```

Preferência:

```text
Usar ProcessDocumentAiJob para orquestrar OCR + classificação + extração, se essa for a arquitetura da Sprint 27.
Criar ExtractDocumentAiFieldsJob apenas se o projeto separar etapas.
```

Requisitos:

```text
Job recebe apenas ID da análise.
Job não serializa OCR text.
Job não serializa extraction_json.
Job não serializa raw_ai_json.
Job não transporta dados pessoais.
Job usa afterCommit quando despachado após upload.
Job falha de forma controlada.
Job atualiza status e flags.
Job emite eventos.
```

---

## 17. Eventos Recomendados

Criar ou completar:

```text
App\Events\DocumentFieldExtractionStarted
App\Events\DocumentFieldExtractionCompleted
App\Events\DocumentFieldExtractionFailed
App\Events\DocumentFieldExtractionRequiresReview
```

Eventos devem transportar:

```text
ID da análise
ID do documento, se seguro
tipo documental
estado de extração
confiança global
```

Eventos não devem transportar:

```text
ocr_text
extraction_json
raw_ai_json
valores extraídos
ficheiro original
path privado
dados pessoais
```

---

## 18. Auditoria e RGPD

Auditar:

```text
Início de extração estruturada
Conclusão de extração estruturada
Falha de extração estruturada
Extração com baixa confiança
Campo sensível marcado para revisão
Consulta backoffice de resultados extraídos
```

Não auditar:

```text
Valores extraídos
Texto OCR integral
JSON estruturado integral
JSON bruto da IA
Dados de saúde
NIF
Número de documento
Morada
Path privado completo
```

Auditoria deve conter apenas:

```text
ID da análise
ID do documento
Tipo documental
Estado anterior
Estado novo
Quantidade de campos extraídos
Quantidade de campos com revisão necessária
Confiança global
Utilizador responsável, quando existir
Timestamp
```

Dados de incapacidade em Atestado Multiusos são dados de saúde e exigem cuidado reforçado.

Se existir perfil/permissão específica para dados sensíveis, respeitar essa permissão.

Se não existir, ocultar por defeito no backoffice e documentar pendência.

---

## 19. Policies e Permissões

Criar ou completar:

```text
DocumentAiAnalysisPolicy
DocumentAiFieldPolicy
```

Permissões mínimas:

```text
viewExtractedFields
viewSensitiveExtractedFields
viewHealthExtractedFields
markFieldForReview
exportExtractedFields, se existir exportação
```

Regras:

```text
Guest não acede.
Candidato não acede aos resultados IA nesta sprint.
Técnico autorizado vê campos não sensíveis necessários.
Técnico com permissão reforçada vê campos sensíveis.
Dados de saúde exigem permissão explícita.
Auditor pode consultar sem alterar, se perfil existir.
Admin pode marcar revisão manual.
Não mostrar raw_ai_json por defeito.
Não mostrar extraction_json integral por defeito.
```

Nunca confiar apenas no frontend.

---

## 20. Backoffice

Atualizar o painel criado na Sprint 28 ou criar nova secção:

```text
Backoffice > Documentos > Extração IA
```

O painel deve mostrar:

```text
Documento
Tipo documental
Estado da extração
Confiança global
Campos extraídos
Campos com revisão necessária
Data da extração
OCR disponível
Classificação IA
```

Detalhe autorizado deve mostrar:

```text
Campo
Valor extraído
Valor normalizado
Tipo
Confiança
Fonte
Requer revisão
Flags
```

Aplicar mascaramento quando necessário:

```text
NIF: *** *** 123
Número documento: parcialmente mascarado
Morada: ocultar se sem permissão
Dados de saúde: ocultar se sem permissão explícita
```

Não mostrar por defeito:

```text
OCR integral
raw_ai_json
extraction_json integral
path privado
ficheiro original inline
```

---

## 21. Rotas e Controllers

Criar ou completar:

```text
App\Http\Controllers\Backoffice\DocumentAiExtractionController
```

Métodos recomendados:

```php
index()
show(DocumentAiAnalysis $analysis)
fields(DocumentAiAnalysis $analysis)
markFieldForReview(DocumentAiField $field)
```

Rotas sugeridas, adaptar ao projeto:

```php
Route::middleware(['auth'])
    ->prefix('backoffice/documentos/ia')
    ->name('backoffice.document-ai.')
    ->group(function (): void {
        Route::get('/extracoes', [DocumentAiExtractionController::class, 'index'])
            ->name('extractions.index');
        Route::get('/extracoes/{analysis}', [DocumentAiExtractionController::class, 'show'])
            ->name('extractions.show');
        Route::post('/campos/{field}/revisao', [DocumentAiExtractionController::class, 'markFieldForReview'])
            ->name('fields.review');
    });
```

Se já existir grupo backoffice, usar o grupo real.

Não duplicar prefixos.

Não criar rotas públicas.

---

## 22. Form Requests

Criar:

```text
App\Http\Requests\Backoffice\FilterDocumentAiExtractionsRequest
App\Http\Requests\Backoffice\MarkDocumentAiFieldReviewRequest
```

Filtros recomendados:

```php
'document_type' => ['nullable', 'string'],
'extraction_status' => ['nullable', 'string'],
'requires_review' => ['nullable', 'boolean'],
'field_key' => ['nullable', 'string', 'max:100'],
'confidence_min' => ['nullable', 'numeric', 'min:0', 'max:1'],
'confidence_max' => ['nullable', 'numeric', 'min:0', 'max:1'],
'created_from' => ['nullable', 'date'],
'created_until' => ['nullable', 'date', 'after_or_equal:created_from'],
```

Validar enums com `Rule::enum()` quando disponível.

---

## 23. Views / Páginas

Se o projeto usa Blade, criar ou completar:

```text
resources/views/backoffice/document-ai/extractions/index.blade.php
resources/views/backoffice/document-ai/extractions/show.blade.php
resources/views/backoffice/document-ai/extractions/_fields-table.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes na stack real.

Não mudar stack frontend.

O index deve ser denso, administrativo e pesquisável.

O detalhe deve separar:

```text
Resumo da análise
Classificação do documento
Estado da extração
Campos extraídos
Flags
Logs técnicos minimizados
Auditoria, se existir e for autorizada
```

Não adicionar interfaces que permitam aplicar valores extraídos à candidatura nesta sprint.

---

## 24. Testes Obrigatórios

Criar ou completar testes.

### 24.1 Unit — Schema e normalização

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentExtractionSchemaRegistryTest.php
tests/Unit/DocumentIntelligence/DocumentFieldNormalizerTest.php
tests/Unit/DocumentIntelligence/DocumentExtractionResultValidatorTest.php
```

Cobrir:

```text
Schemas existem para todos os documentos obrigatórios.
Datas são normalizadas.
Valores monetários são normalizados.
Percentagens são normalizadas.
NIF é normalizado.
Campos desconhecidos são rejeitados ou ignorados.
Valores ambíguos exigem revisão.
```

### 24.2 Unit — Extração

Criar:

```text
tests/Unit/DocumentIntelligence/RegexFieldExtractorTest.php
tests/Unit/DocumentIntelligence/LocalAiFieldExtractorTest.php
tests/Unit/DocumentIntelligence/DocumentFieldExtractionPipelineTest.php
tests/Unit/DocumentIntelligence/DocumentExtractionScorerTest.php
```

Cobrir:

```text
Cartão de Cidadão extrai campos esperados.
Título de Residência extrai campos esperados.
IRS extrai campos esperados.
Nota de Liquidação extrai campos esperados.
Recibo de vencimento extrai campos esperados.
Segurança Social extrai campos esperados.
Contrato de arrendamento extrai campos esperados.
Atestado Multiusos extrai campos esperados.
JSON inválido da IA é tratado.
Baixa confiança gera manual_review.
Campos sensíveis são marcados como sensíveis no schema.
```

### 24.3 Feature — Integração com análise documental

Criar:

```text
tests/Feature/DocumentIntelligence/DocumentFieldExtractionIntegrationTest.php
```

Cobrir:

```text
Análise com OCR e classificação executa extração.
Campos são guardados em document_ai_fields.
JSON estruturado é guardado.
Flags são criadas para baixa confiança.
Upload continua funcional.
Estado da candidatura não muda.
Dados do agregado não mudam.
Rendimentos declarados não mudam.
Pontuação não muda.
Elegibilidade não muda.
```

### 24.4 Feature — Backoffice

Criar:

```text
tests/Feature/Backoffice/DocumentAiExtractionPanelTest.php
```

Cobrir:

```text
Técnico autorizado vê painel.
Guest não acede.
Candidato não acede.
Painel mostra estado, confiança e contagem de campos.
Detalhe mostra campos autorizados.
Dados sensíveis são mascarados ou ocultos sem permissão.
Dados de saúde são ocultos sem permissão explícita.
raw_ai_json não aparece por defeito.
extraction_json integral não aparece por defeito.
Marcar campo para revisão exige autorização.
Consulta é auditada quando aplicável.
```

### 24.5 Queue fake

Cobrir:

```text
ProcessDocumentAiJob ou ExtractDocumentAiFieldsJob é despachado.
Job recebe apenas ID da análise.
Job não transporta campos extraídos.
Job não transporta extraction_json.
Job não transporta raw_ai_json.
```

### 24.6 Eventos

Cobrir:

```text
DocumentFieldExtractionStarted
DocumentFieldExtractionCompleted
DocumentFieldExtractionFailed
DocumentFieldExtractionRequiresReview
```

Usar:

```php
Event::fake();
Event::assertDispatched(DocumentFieldExtractionCompleted::class);
```

### 24.7 Auditoria

Cobrir:

```text
Extração concluída é auditada.
Extração falhada é auditada.
Consulta backoffice é auditada.
Auditoria não contém valores extraídos.
Auditoria não contém dados de saúde.
Auditoria não contém OCR integral.
Auditoria não contém raw_ai_json.
```

---

## 25. Fixtures de Teste

Criar fixtures sintéticas:

```text
tests/Fixtures/document-intelligence/extraction/cartao_cidadao.txt
tests/Fixtures/document-intelligence/extraction/titulo_residencia.txt
tests/Fixtures/document-intelligence/extraction/irs.txt
tests/Fixtures/document-intelligence/extraction/nota_liquidacao.txt
tests/Fixtures/document-intelligence/extraction/recibo_vencimento.txt
tests/Fixtures/document-intelligence/extraction/declaracao_seguranca_social.txt
tests/Fixtures/document-intelligence/extraction/contrato_arrendamento.txt
tests/Fixtures/document-intelligence/extraction/atestado_multiusos.txt
```

Fixtures devem usar apenas dados fictícios:

```text
Nome fictício
NIF fictício reservado para teste
Número de documento fictício
Morada fictícia
Entidade fictícia
Montantes fictícios
Datas fictícias
```

Não usar dados reais.

Não usar documentos reais de candidatos.

Não usar screenshots reais com dados pessoais.

---

## 26. PHPStan e Tipagem

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
mixed desnecessário
```

Em relações Eloquent, usar PHPDoc generics:

```php
/** @return \Illuminate\Database\Eloquent\Relations\HasMany<DocumentAiField, DocumentAiAnalysis> */
public function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(DocumentAiField::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php
/**
 * @return array{
 *   schema_version: string,
 *   document_type: string,
 *   extraction_status: string,
 *   extraction_confidence: float,
 *   fields: array<string, array{
 *     value: string|int|float|bool|null,
 *     normalized_value: string|int|float|bool|null,
 *     type: string,
 *     confidence: float,
 *     source: string,
 *     requires_review: bool
 *   }>,
 *   flags: list<array{code: string, severity: string, message: string}>
 * }
 */
```

Não adicionar `mixed` sem necessidade.

Não silenciar erros com ignores genéricos.

---

## 27. Verificação PHPStan Antes de Publicar

Antes de considerar a sprint pronta para publicação, tentar executar PHPStan uma única vez usando `phpstan.neon`, se o ficheiro existir.

Executar apenas uma tentativa:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint29-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint29-before-publish.json
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
Erros introduzidos em ficheiros novos/alterados pela Sprint 29
Erros bloqueantes
Erros não bloqueantes
```

---

## 28. Comandos Finais Obrigatórios

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
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint29-before-publish.json
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

## 29. Documentação Obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-29-extracao-estruturada-campos.md
docs/document-intelligence/field-extraction.md
docs/document-intelligence/extraction-schemas.md
docs/document-intelligence/extracted-fields-security.md
docs/document-intelligence/backoffice-extraction-panel.md
docs/qa/sprint-29-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 29.1 docs/document-intelligence/field-extraction.md

Incluir:

```text
Objetivo
Fluxo de extração
Dependência de OCR
Dependência de classificação
Regex
IA local
Normalização
Confiança
Revisão manual
Limite funcional: não altera candidatura
```

### 29.2 docs/document-intelligence/extraction-schemas.md

Incluir:

```text
Tipos documentais suportados
Campos por tipo documental
Tipos de campo
Campos sensíveis
Regras de normalização
Exemplos de JSON
```

### 29.3 docs/document-intelligence/extracted-fields-security.md

Incluir:

```text
Dados pessoais
Dados de saúde
Mascaramento
Permissões
Auditoria
Logs minimizados
Storage privado
Ausência de aplicação automática
```

### 29.4 docs/document-intelligence/backoffice-extraction-panel.md

Incluir:

```text
Objetivo do painel
Permissões
Filtros
Campos apresentados
Campos ocultos por defeito
Revisão manual
Auditoria
Limitações
```

### 29.5 docs/qa/sprint-29-quality-report.md

Incluir:

```text
Comandos executados
Resultado das migrations
Resultado dos testes
Resultado do PHPStan antes de publicar
Confirmação de tentativa única com phpstan.neon
Erros legados identificados
Erros novos introduzidos: sim/não
Cobertura por tipo documental
Cobertura de normalização
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

## 30. Critérios de Aceitação

A Sprint 29 está concluída quando:

```text
Extração estruturada existe para Cartão de Cidadão.
Extração estruturada existe para Título de Residência.
Extração estruturada existe para IRS.
Extração estruturada existe para Nota de Liquidação.
Extração estruturada existe para Recibo de vencimento.
Extração estruturada existe para Declaração Segurança Social.
Extração estruturada existe para Contrato de arrendamento.
Extração estruturada existe para Atestado Multiusos.
JSON estruturado é guardado.
Campos normalizados são guardados em document_ai_fields.
Confiança por campo é guardada.
Confiança global é guardada.
Campos de baixa confiança exigem revisão.
Flags são criadas quando há inconsistências.
Backoffice apresenta extrações autorizadas.
Dados sensíveis são mascarados ou ocultos sem permissão.
Dados de saúde são protegidos com permissão reforçada.
OCR integral não aparece por defeito.
raw_ai_json não aparece por defeito.
extraction_json integral não aparece por defeito.
Nenhum valor extraído altera automaticamente candidatura.
Nenhum valor extraído altera automaticamente agregado.
Nenhum valor extraído altera automaticamente rendimentos.
Nenhum valor extraído altera automaticamente pontuação.
Nenhum valor extraído altera automaticamente elegibilidade.
Nenhum valor extraído altera automaticamente workflows.
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

## 31. Fora de Âmbito

Não implementar nesta sprint:

```text
Aplicação automática dos campos extraídos à candidatura.
Validação automática final de documentos.
Aprovação automática de documentos.
Reprovação automática de documentos.
Exclusão automática de candidaturas.
Alteração automática de pontuação.
Alteração automática de elegibilidade.
Alteração automática de tipologia.
Alteração automática de renda.
Alteração automática de listas.
Consulta externa à AT.
Consulta externa à Segurança Social.
Consulta bancária.
Verificação de autenticidade documental.
Reconhecimento facial.
Biometria.
Assinatura digital.
Integração CMD/autenticacao.gov.
APIs pagas.
Treino/fine-tuning de modelos.
```

---

## 32. Riscos e Mitigações

### 32.1 Risco de extração incorreta

Mitigação:

```text
Guardar confiança por campo.
Exigir revisão para baixa confiança.
Não aplicar valores automaticamente.
Manter valor original e normalizado separados.
Criar flags de inconsistência.
```

### 32.2 Risco RGPD

Mitigação:

```text
Storage privado.
Policies.
Mascaramento.
Auditoria sem valores.
Logs minimizados.
Sem APIs pagas/externas.
Dados de saúde ocultos por defeito.
```

### 32.3 Risco de acoplamento funcional

Mitigação:

```text
Não escrever em tabelas funcionais de candidatura.
Não disparar serviços de elegibilidade.
Não alterar estados.
Não reutilizar estes valores em scoring nesta sprint.
Testar que candidatura permanece inalterada.
```

---

## 33. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Dependências das Sprints 27/28 reaproveitadas
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
17. Estado da extração do Cartão de Cidadão
18. Estado da extração do Título de Residência
19. Estado da extração de IRS
20. Estado da extração de Nota de Liquidação
21. Estado da extração de Recibo de vencimento
22. Estado da extração de Declaração Segurança Social
23. Estado da extração de Contrato de arrendamento
24. Estado da extração de Atestado Multiusos
25. Estado do JSON estruturado
26. Estado dos document_ai_fields
27. Estado da confiança por campo
28. Estado da confiança global
29. Estado das flags de revisão
30. Estado do painel backoffice
31. Estado do mascaramento de dados sensíveis
32. Estado da proteção de dados de saúde
33. Confirmação de que nenhum valor altera automaticamente candidatura
34. Estado da auditoria
35. Estado dos logs minimizados
36. Confirmação de ausência de APIs pagas
37. Testes Unit criados ou alterados
38. Testes Feature criados ou alterados
39. Testes Queue fake criados ou alterados
40. Testes Eventos criados ou alterados
41. Testes Auditoria criados ou alterados
42. Resultado de php artisan route:list
43. Resultado de php artisan migrate, se aplicável
44. Resultado de php artisan test
45. Resultado de ./vendor/bin/pint, se aplicável
46. Resultado de npm run build, se aplicável
47. Resultado PHPStan antes de publicar
48. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
49. Erros PHPStan legados considerados
50. Novos erros PHPStan introduzidos pela Sprint 29: sim/não
51. Documentação criada ou atualizada
52. Riscos RGPD ainda existentes
53. Riscos técnicos ainda existentes
54. Pendências técnicas
55. Confirmação de que não foram usados dados pessoais reais
56. Confirmação de que não foram usadas credenciais
57. Confirmação de que não foram implementadas funcionalidades fora de âmbito
58. Recomendação objetiva para publicar ou não publicar
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 34. Definition of Done

A Sprint 29 só está concluída quando o sistema extrair campos estruturados dos documentos suportados, guardar JSON estruturado e campos normalizados, proteger dados pessoais e dados de saúde, apresentar resultados apenas em backoffice autorizado, auditar ações relevantes, cobrir Unit/Feature/Queue/Eventos/Auditoria, documentar riscos e executar PHPStan antes de publicação com uma única tentativa usando `phpstan.neon` quando disponível, sem que qualquer valor extraído altere automaticamente candidatura, agregado, rendimentos, pontuação, elegibilidade, tipologia, renda, listas, contratos ou workflows.

---

## 35. Execução Imediata

Executa agora apenas:

```text
Sprint 29 — Extração Estruturada de Campos
```

Fim da master prompt da Sprint 29.

---

## Estado implementado nesta execução

Implementado:

- migration `2026_06_21_000029_add_structured_extraction_fields_to_document_ai_tables.php`;
- enums `DocumentAiExtractionStatus`, `DocumentAiExtractedFieldType` e `DocumentAiExtractionSource`;
- config `document-ai-extraction.php` com schemas dos oito tipos obrigatórios;
- DTOs e services de registry, normalização, regex, IA local opcional, validação, scoring, persistência e pipeline;
- eventos `DocumentFieldExtractionStarted`, `DocumentFieldExtractionCompleted`, `DocumentFieldExtractionFailed` e `DocumentFieldExtractionRequiresReview`;
- integração da extração após a classificação existente na `DocumentClassificationPipeline`;
- policy `DocumentAiFieldPolicy` e extensão da `DocumentAiAnalysisPolicy`;
- controller, Form Requests, rotas e views do painel backoffice de extração;
- fixtures sintéticas e testes focados.

Fora de âmbito mantido:

- nenhum valor extraído altera candidatura, agregado, rendimentos, elegibilidade, pontuação, decisão, documento funcional ou workflow;
- não foram criadas integrações externas pagas;
- não foi implementada validação cruzada com candidatura, fraude ou score avançado.
