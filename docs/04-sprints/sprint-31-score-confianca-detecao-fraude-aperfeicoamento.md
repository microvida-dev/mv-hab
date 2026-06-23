# Sprint 31 — Score de Confiança, Deteção de Fraude e Apoio ao Aperfeiçoamento

## 1. Objetivo da Sprint

Implementar um assistente inteligente para apoiar os técnicos municipais na análise documental, agregando score de confiança IA, indicadores de risco, flags técnicas e sugestões de aperfeiçoamento documental.

Esta sprint evolui o módulo **Document Intelligence** criado nas Sprints 27, 28, 29 e 30:

```text
Sprint 27: infraestrutura base de análise documental por IA
Sprint 28: OCR e classificação automática do documento
Sprint 29: extração estruturada de campos
Sprint 30: validação automática e cruzamento com a candidatura
Sprint 31: score de confiança, indicadores de risco e apoio ao aperfeiçoamento
```

O objetivo é fornecer uma camada de apoio técnico, não uma camada de decisão automática.

Regra central:

```text
A IA nunca exclui automaticamente uma candidatura.
```

Regra de linguagem:

```text
O sistema deve falar em indicadores de risco, divergências, inconsistências e necessidade de revisão manual. Não deve acusar automaticamente o candidato de fraude.
```

Regra funcional complementar:

```text
Nenhum score, flag, alerta ou sugestão altera automaticamente candidatura, agregado, rendimentos, pontuação, elegibilidade, tipologia, renda, estado, lista, decisão, contrato ou workflow.
```

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 31 — Score de Confiança, Deteção de Fraude e Apoio ao Aperfeiçoamento
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Dependências das Sprints 27 a 30

Assumir que já existem, ou devem ser reaproveitados se existirem:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
document_ai_validations
document_ai_validation_runs
DocumentAiStatus
DocumentAiDocumentType
DocumentAiExtractionStatus
DocumentAiValidationStatus
DocumentAiValidationSeverity
DocumentAiPipeline
DocumentClassificationPipeline
DocumentFieldExtractionPipeline
DocumentCandidateValidationPipeline
ProcessDocumentAiJob
OCR local
Classificação automática
Extração estruturada
Validação cruzada com candidatura
raw_ai_json
ocr_text
extraction_json
document_ai_fields
Auditoria
Backoffice técnico IA
```

Antes de implementar, confirmar a estrutura real existente no projeto.

Se alguma peça das Sprints 27-30 ainda não existir, criar apenas adaptação mínima compatível, sem duplicar módulos, services, jobs ou tabelas.

Não criar uma segunda infraestrutura paralela de análise documental.

---

## 4. Princípios Técnicos

Preservar todas as funcionalidades existentes.

Documentos continuam privados por defeito.

Scores e flags são auxiliares técnicos, não decisões administrativas.

Toda a lógica crítica deve ficar em Services.

Controllers devem continuar magros.

Usar Eloquent, migrations reversíveis, casts tipados, enums, Form Requests, Policies, Services, Jobs, Events e testes, seguindo os padrões reais do projeto.

Usar DTOs tipados ou arrays estruturados com PHPDoc rigoroso.

Não expor dados pessoais em logs técnicos, mensagens de erro, eventos ou responses não autorizadas.

Não guardar credenciais.

Não usar dados pessoais reais em testes, fixtures, seeders ou documentação.

Não introduzir APIs pagas.

Não alterar regras de elegibilidade, pontuação, renda, listas ou workflows.

---

## 5. Conceito Funcional

Esta sprint deve criar um assistente técnico de análise documental com:

```text
Score IA de 0 a 100
Flags técnicas e de consistência
Indicadores de risco documental
Sugestões de aperfeiçoamento
Interface de apoio à análise municipal
Auditoria e RGPD
```

Exemplo de interface:

```text
Documento

Score IA: 96%

✔ OCR Excelente
✔ Classificação correta
⚠ Divergência rendimento
⚠ Rever manualmente
```

O score deve ser explicável.

Cada perda de pontos deve ter motivo rastreável.

Cada flag deve ter severidade, mensagem, fonte e recomendação.

---

## 6. Score IA Obrigatório

Criar score IA de 0 a 100 baseado em:

```text
OCR
consistência
classificação
campos extraídos
divergências
```

### 6.1 Interpretação do score

Recomendação:

```text
90–100: muito confiável
75–89: confiável com pontos de atenção
60–74: requer revisão técnica
40–59: baixa confiança
0–39: crítico / revisão obrigatória
```

Labels:

```text
muito_confiavel
confiavel_com_atencao
requer_revisao
baixa_confianca
critico
```

### 6.2 Componentes do score

Modelo inicial recomendado:

```text
OCR: 20 pontos
Classificação: 20 pontos
Campos extraídos: 20 pontos
Consistência com candidatura: 25 pontos
Flags e risco documental: 15 pontos
```

Total:

```text
100 pontos
```

Configurar pesos em `config/document-ai-score.php`.

Não hardcodar pesos dentro do service se o projeto usa config.

### 6.3 OCR

Fatores:

```text
OCR disponível
qualidade OCR
texto extraído suficiente
páginas processadas
erros de OCR
documento ilegível
página cortada
documento vazio
```

Exemplos:

```text
✔ OCR Excelente
⚠ OCR insuficiente
⚠ Documento ilegível
⚠ Página cortada
```

### 6.4 Classificação

Fatores:

```text
classificação concluída
confiança da classificação
tipo documental esperado vs classificado
categoria "outro"
classificação inconclusiva
```

Exemplos:

```text
✔ Classificação correta
⚠ Classificação de baixa confiança
⚠ Tipo documental inesperado
```

### 6.5 Campos extraídos

Fatores:

```text
campos obrigatórios presentes
confiança por campo
campos críticos ausentes
campos normalizados corretamente
dados sensíveis protegidos
```

Exemplos:

```text
✔ Campos principais extraídos
⚠ Campos obrigatórios ausentes
⚠ Validade não identificada
```

### 6.6 Consistência

Fatores:

```text
nome coincide
NIF coincide
data de nascimento coincide
rendimentos compatíveis
morada consistente
contrato consistente
validações inconclusivas
```

Exemplos:

```text
✔ Nome coincide
⚠ NIF diferente
⚠ Nome diferente
⚠ Rendimento incompatível
```

### 6.7 Divergências

Fatores:

```text
divergência crítica
divergência média
divergência ligeira
quantidade de divergências
reincidência em documentos da mesma candidatura
documento duplicado
```

Divergência crítica deve reduzir score de forma significativa e exigir revisão manual.

---

## 7. Flags Obrigatórias

Criar ou completar flags para:

```text
Documento expirado
Documento ilegível
Página cortada
OCR insuficiente
NIF diferente
Nome diferente
Rendimento incompatível
Documento duplicado
Documento vazio
Campos obrigatórios ausentes
```

Chaves recomendadas:

```text
document_expired
document_unreadable
page_cropped
insufficient_ocr
nif_mismatch
name_mismatch
income_incompatible
duplicate_document
empty_document
missing_required_fields
```

### 7.1 Documento expirado

Detetar quando:

```text
existe campo de validade;
validade é anterior à data atual;
tipo documental exige validade.
```

Não reprovar automaticamente.

Marcar revisão manual.

### 7.2 Documento ilegível

Detetar quando:

```text
OCR falha;
texto extraído é insuficiente;
qualidade OCR muito baixa;
imagem tem baixa legibilidade, se houver métrica disponível.
```

### 7.3 Página cortada

Detetar por heurísticas:

```text
texto truncado;
campos esperados ausentes;
layout incompleto;
proporção de página anormal;
OCR indica linhas incompletas.
```

Não depender de visão avançada nesta sprint se não existir suporte.

### 7.4 OCR insuficiente

Detetar quando:

```text
número de caracteres abaixo do limiar;
poucas palavras reconhecidas;
OCR falha em campos obrigatórios;
documento é imagem/PDF digitalizado com baixa qualidade.
```

### 7.5 NIF diferente

Detetar a partir das validações da Sprint 30.

Se NIF extraído e NIF declarado forem diferentes:

```text
severidade crítica;
revisão manual obrigatória;
sugestão de aperfeiçoamento.
```

### 7.6 Nome diferente

Detetar a partir das validações da Sprint 30.

Usar:

```text
normalização;
comparação parcial;
limiares configuráveis.
```

Evitar linguagem acusatória.

### 7.7 Rendimento incompatível

Detetar quando:

```text
IRS superior ao declarado;
recibo incompatível;
Segurança Social incompatível;
diferença monetária acima de limiar configurável.
```

Não recalcular elegibilidade.

### 7.8 Documento duplicado

Detetar por:

```text
hash do ficheiro;
hash textual;
mesmo tipo documental;
mesma candidatura;
mesmos campos críticos;
```

Não apagar duplicados automaticamente.

### 7.9 Documento vazio

Detetar quando:

```text
ficheiro sem texto;
imagem sem conteúdo útil;
PDF sem páginas processáveis;
OCR vazio;
ficheiro com tamanho incompatível.
```

### 7.10 Campos obrigatórios ausentes

Detetar a partir dos schemas da Sprint 29:

```text
campo obrigatório sem valor;
campo crítico com baixa confiança;
campo ilegível;
campo incompatível com tipo documental.
```

---

## 8. Apoio ao Aperfeiçoamento

Criar geração automática de sugestões para pedidos de aperfeiçoamento documental.

Exemplo obrigatório:

```text
Foi identificada divergência entre o NIF declarado e o documento submetido. Solicita-se o envio de documentação atualizada.
```

### 8.1 Regras das sugestões

As sugestões devem:

```text
ser claras;
ser neutras;
ser não acusatórias;
ser editáveis pelo técnico;
não enviar automaticamente ao candidato nesta sprint;
não conter dados pessoais desnecessários;
não tomar decisão administrativa;
referir apenas a necessidade documental.
```

As sugestões não devem dizer:

```text
fraude comprovada;
documento falso;
candidato mentiu;
candidatura excluída;
indeferimento automático;
```

Preferir:

```text
Foi identificada uma divergência...
Não foi possível confirmar...
Solicita-se o envio de documento atualizado...
Solicita-se a clarificação...
O documento submetido aparenta estar expirado...
```

### 8.2 Sugestões obrigatórias

Criar templates para:

```text
document_expired
document_unreadable
page_cropped
insufficient_ocr
nif_mismatch
name_mismatch
income_incompatible
duplicate_document
empty_document
missing_required_fields
```

Exemplos:

```text
Documento expirado:
O documento submetido aparenta estar fora do prazo de validade. Solicita-se o envio de documentação atualizada.

Documento ilegível:
Não foi possível ler com segurança o documento submetido. Solicita-se o envio de uma cópia mais legível.

Página cortada:
O documento submetido aparenta estar incompleto ou cortado. Solicita-se o envio do documento completo.

OCR insuficiente:
Não foi possível extrair informação suficiente do documento submetido. Solicita-se o envio de nova cópia em melhor qualidade.

NIF diferente:
Foi identificada divergência entre o NIF declarado e o documento submetido. Solicita-se o envio de documentação atualizada.

Nome diferente:
Foi identificada divergência entre o nome declarado e o documento submetido. Solicita-se a confirmação ou o envio de documentação atualizada.

Rendimento incompatível:
Foi identificada divergência entre os rendimentos declarados e a documentação submetida. Solicita-se a clarificação ou documentação complementar.

Documento duplicado:
Foi identificado um documento aparentemente duplicado. Solicita-se a confirmação da documentação pretendida.

Documento vazio:
O ficheiro submetido não apresenta conteúdo documental legível. Solicita-se o envio de novo documento.

Campos obrigatórios ausentes:
Não foi possível identificar todos os campos necessários no documento submetido. Solicita-se documentação complementar ou nova cópia.
```

### 8.3 Estado das sugestões

Sugestões devem ter estado:

```text
draft
accepted
edited
dismissed
sent, apenas se já existir fluxo real de pedidos de aperfeiçoamento e for explicitamente integrado sem envio automático
```

Nesta sprint, por defeito:

```text
sugestões ficam em draft para revisão técnica.
```

---

## 9. Interface Obrigatória

Criar ou completar interface no backoffice técnico.

Secção sugerida:

```text
Backoffice > Documentos > Assistente IA
```

Ou integrar no detalhe técnico da candidatura/documento se esse for o padrão do projeto.

Interface mínima:

```text
Documento
Score IA: 96%
✔ OCR Excelente
✔ Classificação correta
⚠ Divergência rendimento
⚠ Rever manualmente
```

Elementos obrigatórios:

```text
Score IA 0-100
Label do score
Componentes do score
Flags
Severidade das flags
Sugestões de aperfeiçoamento
Estado de revisão manual
Histórico técnico minimizado
```

Não mostrar por defeito:

```text
raw_ai_json
ocr_text integral
extraction_json integral
valores pessoais sem permissão
dados de saúde sem permissão explícita
path privado
documento original inline
```

---

## 10. Modelo de Dados

Reutilizar tabelas das Sprints 27-30 sempre que possível.

Criar tabela:

```text
document_ai_scores
```

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
application_id nullable foreign key
document_submission_id nullable foreign key, se existir
score unsignedTinyInteger indexed
score_label string indexed
ocr_score unsignedTinyInteger nullable
classification_score unsignedTinyInteger nullable
extraction_score unsignedTinyInteger nullable
consistency_score unsignedTinyInteger nullable
risk_score unsignedTinyInteger nullable
requires_manual_review boolean default false
summary text nullable
explanation json nullable
calculated_at timestamp nullable
created_at
updated_at
```

Criar tabela:

```text
document_ai_suggestions
```

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
application_id nullable foreign key
document_ai_score_id nullable foreign key
flag_code string indexed
severity string indexed
status string indexed
suggestion text
edited_suggestion text nullable
accepted_at timestamp nullable
accepted_by foreign key nullable
dismissed_at timestamp nullable
dismissed_by foreign key nullable
sent_at timestamp nullable
metadata json nullable
created_at
updated_at
```

Completar `document_ai_flags`, se necessário:

```text
score_impact integer nullable
suggestion_template string nullable
detected_by string nullable
confidence decimal nullable
```

Regras RGPD:

```text
summary e suggestion não devem conter dados pessoais desnecessários.
Não guardar acusações de fraude.
Não guardar linguagem conclusiva de falsificação.
Não indexar valores pessoais.
Auditoria não deve conter o texto integral quando incluir dados sensíveis.
```

Índices recomendados:

```text
document_ai_analysis_id
application_id
score
score_label
requires_manual_review
flag_code
severity
status
calculated_at
```

---

## 11. Enums Obrigatórios

Criar:

```text
App\Enums\DocumentAiScoreLabel
App\Enums\DocumentAiRiskFlagCode
App\Enums\DocumentAiRiskSeverity
App\Enums\DocumentAiSuggestionStatus
```

### 11.1 DocumentAiScoreLabel

Valores:

```text
muito_confiavel
confiavel_com_atencao
requer_revisao
baixa_confianca
critico
```

### 11.2 DocumentAiRiskFlagCode

Valores:

```text
document_expired
document_unreadable
page_cropped
insufficient_ocr
nif_mismatch
name_mismatch
income_incompatible
duplicate_document
empty_document
missing_required_fields
```

### 11.3 DocumentAiRiskSeverity

Valores:

```text
info
low
medium
high
critical
```

### 11.4 DocumentAiSuggestionStatus

Valores:

```text
draft
accepted
edited
dismissed
sent
```

Usar casts nos models.

Evitar strings soltas fora de migrations e testes específicos.

---

## 12. Models Obrigatórios

Criar:

```text
App\Models\DocumentAiScore
App\Models\DocumentAiSuggestion
```

Completar, se existirem:

```text
App\Models\DocumentAiAnalysis
App\Models\DocumentAiFlag
```

Relações mínimas:

```text
DocumentAiScore belongsTo DocumentAiAnalysis
DocumentAiScore belongsTo Application, se existir
DocumentAiScore hasMany DocumentAiSuggestion
DocumentAiSuggestion belongsTo DocumentAiAnalysis
DocumentAiSuggestion belongsTo DocumentAiScore
DocumentAiSuggestion belongsTo Application, se existir
DocumentAiSuggestion belongsTo acceptedBy User
DocumentAiSuggestion belongsTo dismissedBy User
DocumentAiAnalysis hasOne/latest DocumentAiScore
DocumentAiAnalysis hasMany DocumentAiSuggestion
```

Requisitos:

```text
casts para enum, boolean, integer, json e datetime;
fillable conservador;
PHPDoc generics para relações;
scopes para score_label, requires_manual_review, severity e status;
```

Exemplo PHPStan:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiScore> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

---

## 13. Services Obrigatórios

Criar ou completar:

```text
App\Services\DocumentIntelligence\DocumentAiAssistantPipeline
App\Services\DocumentIntelligence\DocumentAiScoreCalculator
App\Services\DocumentIntelligence\DocumentAiScoreExplainer
App\Services\DocumentIntelligence\DocumentRiskFlagDetector
App\Services\DocumentIntelligence\DocumentDuplicateDetector
App\Services\DocumentIntelligence\DocumentQualityAnalyzer
App\Services\DocumentIntelligence\DocumentSuggestionGenerator
App\Services\DocumentIntelligence\DocumentSuggestionTemplateRegistry
App\Services\DocumentIntelligence\DocumentAiAssistantPersister
App\Services\DocumentIntelligence\DocumentAiAssistantDashboardService
```

### 13.1 DocumentAiAssistantPipeline

Responsável por:

```text
Receber DocumentAiAnalysis.
Carregar OCR, classificação, extração e validações.
Detetar flags técnicas e de consistência.
Calcular score.
Gerar explicação do score.
Gerar sugestões de aperfeiçoamento.
Persistir score, flags e sugestões.
Emitir eventos.
Auditar.
Não alterar candidatura.
Não enviar pedidos ao candidato automaticamente.
```

### 13.2 DocumentAiScoreCalculator

Responsável por:

```text
Calcular score 0-100.
Aplicar pesos configuráveis.
Garantir score mínimo/máximo.
Aplicar penalizações por flags.
Aplicar penalizações por divergências.
Gerar sub-scores.
Retornar resultado estruturado.
```

### 13.3 DocumentAiScoreExplainer

Responsável por:

```text
Explicar composição do score.
Listar fatores positivos.
Listar fatores negativos.
Listar recomendações de revisão.
Não incluir dados pessoais desnecessários.
```

### 13.4 DocumentRiskFlagDetector

Responsável por:

```text
Detetar documento expirado.
Detetar documento ilegível.
Detetar página cortada.
Detetar OCR insuficiente.
Detetar NIF diferente.
Detetar nome diferente.
Detetar rendimento incompatível.
Detetar documento vazio.
Detetar campos obrigatórios ausentes.
Delegar duplicados ao DocumentDuplicateDetector.
```

### 13.5 DocumentDuplicateDetector

Responsável por:

```text
Comparar hash de ficheiro.
Comparar hash textual.
Comparar tipo documental.
Comparar campos críticos.
Detetar duplicados na mesma candidatura.
Não apagar documentos.
Não substituir documentos.
```

### 13.6 DocumentQualityAnalyzer

Responsável por:

```text
Avaliar OCR suficiente.
Avaliar texto mínimo.
Avaliar páginas processadas.
Avaliar sinais de corte.
Avaliar documento vazio.
Avaliar ausência de campos obrigatórios.
```

### 13.7 DocumentSuggestionGenerator

Responsável por:

```text
Gerar sugestões em português de Portugal.
Usar templates neutros.
Evitar acusações.
Criar sugestões em draft.
Evitar dados pessoais desnecessários.
Permitir edição posterior.
```

### 13.8 DocumentAiAssistantPersister

Responsável por:

```text
Guardar DocumentAiScore.
Guardar DocumentAiSuggestion.
Atualizar/criar DocumentAiFlag.
Garantir idempotência.
Evitar duplicação de sugestões iguais.
Não escrever nas tabelas funcionais da candidatura.
```

### 13.9 DocumentAiAssistantDashboardService

Responsável por:

```text
Construir payload do dashboard.
Agregar por candidatura/documento.
Evitar N+1.
Aplicar filtros.
Minimizar dados pessoais.
Respeitar policies no controller.
```

---

## 14. Configuração Obrigatória

Criar:

```text
config/document-ai-score.php
```

Estrutura recomendada:

```php
return [
    'enabled' => env('DOCUMENT_AI_SCORE_ENABLED', true),
    'weights' => [
        'ocr' => 20,
        'classification' => 20,
        'extraction' => 20,
        'consistency' => 25,
        'risk' => 15,
    ],
    'labels' => [
        'muito_confiavel' => ['min' => 90, 'max' => 100],
        'confiavel_com_atencao' => ['min' => 75, 'max' => 89],
        'requer_revisao' => ['min' => 60, 'max' => 74],
        'baixa_confianca' => ['min' => 40, 'max' => 59],
        'critico' => ['min' => 0, 'max' => 39],
    ],
    'penalties' => [
        'document_expired' => 20,
        'document_unreadable' => 35,
        'page_cropped' => 20,
        'insufficient_ocr' => 25,
        'nif_mismatch' => 45,
        'name_mismatch' => 35,
        'income_incompatible' => 25,
        'duplicate_document' => 15,
        'empty_document' => 60,
        'missing_required_fields' => 25,
    ],
    'suggestions' => [
        'default_status' => 'draft',
        'auto_send' => false,
    ],
];
```

Regras:

```text
Soma dos pesos deve ser validada.
Penalizações devem ser configuráveis.
auto_send deve ser false por defeito.
Não colocar credenciais.
```

---

## 15. Jobs e Pipeline

Reutilizar:

```text
ProcessDocumentAiJob
DocumentAiPipeline
DocumentCandidateValidationPipeline
```

Criar job, se a arquitetura separar etapas:

```text
App\Jobs\CalculateDocumentAiScoreJob
```

Requisitos:

```text
Job recebe apenas document_ai_analysis_id.
Job não serializa dados pessoais.
Job não serializa OCR integral.
Job não serializa extraction_json.
Job não serializa raw_ai_json.
Job usa afterCommit quando aplicável.
Job falha de forma controlada.
Job cria score, flags e sugestões.
Job nunca altera candidatura.
Job nunca envia aperfeiçoamento automaticamente.
Job emite eventos.
```

Execução recomendada:

```text
Após validação cruzada concluída, calcular score e gerar sugestões.
Permitir reprocessamento manual autorizado.
Garantir idempotência.
```

---

## 16. Eventos Recomendados

Criar:

```text
App\Events\DocumentAiScoreCalculationStarted
App\Events\DocumentAiScoreCalculated
App\Events\DocumentAiScoreCalculationFailed
App\Events\DocumentAiRiskFlagDetected
App\Events\DocumentAiSuggestionGenerated
App\Events\DocumentAiManualReviewRecommended
```

Eventos devem transportar:

```text
ID da análise
ID da candidatura, se seguro
ID do score
score
score_label
flag_code, se aplicável
status
```

Eventos não devem transportar:

```text
valores declarados;
valores extraídos;
texto de OCR integral;
extraction_json;
raw_ai_json;
dados pessoais;
dados de saúde;
path privado.
```

---

## 17. Auditoria e RGPD

Auditar:

```text
Início de cálculo de score.
Conclusão de cálculo de score.
Falha no cálculo de score.
Deteção de flag crítica.
Geração de sugestão de aperfeiçoamento.
Consulta do assistente IA.
Aceitação/edição/descarte de sugestão.
Recomendação de revisão manual.
```

Não auditar:

```text
Nome completo.
NIF.
Data de nascimento.
Morada.
Valores de rendimento.
Dados de saúde.
OCR integral.
JSON bruto.
Texto integral de sugestões se contiver dados pessoais.
```

Auditoria deve conter:

```text
ID da análise
ID da candidatura
ID do score
score
score_label
flag_code
severity
status
utilizador responsável, quando existir
timestamp
```

Dados pessoais devem ser minimizados.

Sugestões devem evitar incluir dados pessoais no texto.

---

## 18. Policies e Permissões

Criar ou completar:

```text
DocumentAiScorePolicy
DocumentAiSuggestionPolicy
DocumentAiAssistantPolicy
```

Permissões mínimas:

```text
viewAny
view
viewSensitiveDetails
viewHealthRelatedDetails
acceptSuggestion
editSuggestion
dismissSuggestion
recalculateScore
markManualReview
```

Regras:

```text
Guest não acede.
Candidato não acede ao assistente IA nesta sprint.
Técnico autorizado vê score e flags.
Técnico com permissão reforçada vê detalhes sensíveis.
Dados de saúde exigem permissão explícita.
Auditor pode consultar sem alterar, se perfil existir.
Admin pode recalcular score e gerir sugestões.
```

Nunca confiar apenas no frontend.

---

## 19. Backoffice — Interface do Assistente IA

Criar ou completar:

```text
App\Http\Controllers\Backoffice\DocumentAiAssistantController
```

Métodos recomendados:

```php
index()
show(DocumentAiAnalysis $analysis)
score(DocumentAiScore $score)
acceptSuggestion(DocumentAiSuggestion $suggestion)
editSuggestion(DocumentAiSuggestion $suggestion)
dismissSuggestion(DocumentAiSuggestion $suggestion)
recalculate(DocumentAiAnalysis $analysis)
```

Rotas sugeridas, adaptar ao projeto:

```php
Route::middleware(['auth'])
    ->prefix('backoffice/documentos/ia')
    ->name('backoffice.document-ai-assistant.')
    ->group(function (): void {
        Route::get('/assistente', [DocumentAiAssistantController::class, 'index'])
            ->name('index');
        Route::get('/assistente/{analysis}', [DocumentAiAssistantController::class, 'show'])
            ->name('show');
        Route::post('/assistente/{analysis}/recalcular', [DocumentAiAssistantController::class, 'recalculate'])
            ->name('recalculate');
        Route::post('/sugestoes/{suggestion}/aceitar', [DocumentAiAssistantController::class, 'acceptSuggestion'])
            ->name('suggestions.accept');
        Route::put('/sugestoes/{suggestion}', [DocumentAiAssistantController::class, 'editSuggestion'])
            ->name('suggestions.update');
        Route::post('/sugestoes/{suggestion}/descartar', [DocumentAiAssistantController::class, 'dismissSuggestion'])
            ->name('suggestions.dismiss');
    });
```

Se o projeto já tiver grupo backoffice, usar o grupo real.

Não duplicar prefixos.

Não criar rotas públicas.

Não criar envio automático ao candidato nesta sprint.

---

## 20. Form Requests

Criar:

```text
App\Http\Requests\Backoffice\FilterDocumentAiAssistantRequest
App\Http\Requests\Backoffice\UpdateDocumentAiSuggestionRequest
App\Http\Requests\Backoffice\AcceptDocumentAiSuggestionRequest
App\Http\Requests\Backoffice\DismissDocumentAiSuggestionRequest
App\Http\Requests\Backoffice\RecalculateDocumentAiScoreRequest
```

Filtros recomendados:

```php
'application_id' => ['nullable', 'integer'],
'document_type' => ['nullable', 'string'],
'score_min' => ['nullable', 'integer', 'min:0', 'max:100'],
'score_max' => ['nullable', 'integer', 'min:0', 'max:100'],
'score_label' => ['nullable', 'string'],
'flag_code' => ['nullable', 'string'],
'severity' => ['nullable', 'string'],
'requires_manual_review' => ['nullable', 'boolean'],
'suggestion_status' => ['nullable', 'string'],
'created_from' => ['nullable', 'date'],
'created_until' => ['nullable', 'date', 'after_or_equal:created_from'],
```

Validação de sugestão editada:

```php
'suggestion' => ['required', 'string', 'min:10', 'max:3000'],
```

Validar enums com `Rule::enum()` quando disponível.

---

## 21. Views / Páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/document-ai/assistant/index.blade.php
resources/views/backoffice/document-ai/assistant/show.blade.php
resources/views/backoffice/document-ai/assistant/_score-card.blade.php
resources/views/backoffice/document-ai/assistant/_flags.blade.php
resources/views/backoffice/document-ai/assistant/_suggestions.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes na stack real.

Não mudar stack frontend.

Index:

```text
Listagem por documento/candidatura.
Filtros por score, label, flag, severidade e revisão manual.
Indicadores agregados.
```

Show:

```text
Documento
Score IA: 96%
Componentes do score
Flags
Sugestões
Estado de revisão
Histórico técnico
```

Não mostrar por defeito:

```text
raw_ai_json
ocr_text integral
extraction_json integral
valores pessoais sem permissão
dados de saúde sem permissão explícita
path privado
```

---

## 22. UX Obrigatória

Mostrar:

```text
Documento

Score IA: 96%

✔ OCR Excelente
✔ Classificação correta
⚠ Divergência rendimento
⚠ Rever manualmente
```

Estados visuais:

```text
score alto: verde
score médio: amarelo/laranja
score baixo: vermelho
flag crítica: vermelho
flag média: laranja
flag ligeira: amarelo
informativo: cinzento/azul
```

Texto obrigatório de contexto:

```text
O score IA e as flags são auxiliares à análise técnica e não produzem decisão automática sobre a candidatura.
```

Evitar linguagem acusatória:

```text
fraude confirmada
documento falso
candidato mentiu
exclusão automática
```

Preferir:

```text
indicador de risco
divergência
inconsistência
necessita revisão manual
evidência insuficiente
```

---

## 23. Testes Obrigatórios

Criar ou completar testes.

### 23.1 Unit — Score

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentAiScoreCalculatorTest.php
tests/Unit/DocumentIntelligence/DocumentAiScoreExplainerTest.php
tests/Unit/DocumentIntelligence/DocumentAiAssistantPipelineTest.php
```

Cobrir:

```text
Score fica entre 0 e 100.
Score alto quando OCR/classificação/extração/consistência são bons.
Score baixa com OCR insuficiente.
Score baixa com divergência crítica.
Score baixa com documento vazio.
Score label é calculado corretamente.
Explicação lista fatores positivos e negativos.
Pipeline não altera candidatura.
Pipeline não envia aperfeiçoamento automaticamente.
```

### 23.2 Unit — Flags

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentRiskFlagDetectorTest.php
tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php
tests/Unit/DocumentIntelligence/DocumentQualityAnalyzerTest.php
```

Cobrir:

```text
Documento expirado gera flag.
Documento ilegível gera flag.
Página cortada gera flag.
OCR insuficiente gera flag.
NIF diferente gera flag crítica.
Nome diferente gera flag.
Rendimento incompatível gera flag.
Documento duplicado gera flag.
Documento vazio gera flag.
Campos obrigatórios ausentes geram flag.
```

### 23.3 Unit — Sugestões

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentSuggestionGeneratorTest.php
tests/Unit/DocumentIntelligence/DocumentSuggestionTemplateRegistryTest.php
```

Cobrir:

```text
Gera sugestão para NIF diferente.
Gera sugestão para documento expirado.
Gera sugestão para documento ilegível.
Gera sugestão para rendimento incompatível.
Sugestão é neutra e não acusatória.
Sugestão fica em draft.
Sugestão não contém dados pessoais desnecessários.
```

### 23.4 Feature — Assistente IA

Criar:

```text
tests/Feature/DocumentIntelligence/DocumentAiAssistantIntegrationTest.php
```

Cobrir:

```text
Após validação cruzada, score é calculado.
Flags são geradas.
Sugestões são geradas.
Score é persistido.
Sugestões ficam em draft.
Candidatura não é excluída.
Estado da candidatura não muda.
Pontuação não muda.
Elegibilidade não muda.
Workflow não muda.
```

### 23.5 Feature — Backoffice

Criar:

```text
tests/Feature/Backoffice/DocumentAiAssistantDashboardTest.php
```

Cobrir:

```text
Técnico autorizado vê assistente IA.
Guest não acede.
Candidato não acede.
Interface mostra Score IA.
Interface mostra OCR Excelente.
Interface mostra Classificação correta.
Interface mostra Divergência rendimento.
Interface mostra Rever manualmente.
raw_ai_json não aparece.
ocr_text integral não aparece.
extraction_json integral não aparece.
Valores sensíveis são mascarados sem permissão.
Dados de saúde não aparecem sem permissão explícita.
Sugestão pode ser editada por autorizado.
Sugestão pode ser aceite por autorizado.
Sugestão pode ser descartada por autorizado.
Recalcular score exige autorização.
Consulta é auditada quando aplicável.
```

### 23.6 Queue fake

Cobrir:

```text
CalculateDocumentAiScoreJob é despachado quando aplicável.
Job recebe apenas ID da análise.
Job não transporta dados pessoais.
Job não transporta valores extraídos.
Job não transporta JSON bruto.
```

### 23.7 Eventos

Cobrir:

```text
DocumentAiScoreCalculationStarted
DocumentAiScoreCalculated
DocumentAiScoreCalculationFailed
DocumentAiRiskFlagDetected
DocumentAiSuggestionGenerated
DocumentAiManualReviewRecommended
```

Usar:

```php
Event::fake();
Event::assertDispatched(DocumentAiScoreCalculated::class);
```

### 23.8 Auditoria

Cobrir:

```text
Score calculado é auditado.
Flag crítica é auditada.
Sugestão gerada é auditada.
Sugestão aceite/editada/descartada é auditada.
Consulta ao assistente é auditada.
Auditoria não contém NIF.
Auditoria não contém nome completo.
Auditoria não contém rendimentos.
Auditoria não contém morada.
Auditoria não contém dados de saúde.
Auditoria não contém raw_ai_json.
```

---

## 24. Fixtures de Teste

Criar fixtures sintéticas:

```text
tests/Fixtures/document-intelligence/assistant/high_confidence.json
tests/Fixtures/document-intelligence/assistant/insufficient_ocr.json
tests/Fixtures/document-intelligence/assistant/nif_mismatch.json
tests/Fixtures/document-intelligence/assistant/name_mismatch.json
tests/Fixtures/document-intelligence/assistant/income_incompatible.json
tests/Fixtures/document-intelligence/assistant/duplicate_document.json
tests/Fixtures/document-intelligence/assistant/empty_document.json
tests/Fixtures/document-intelligence/assistant/missing_required_fields.json
```

Fixtures devem usar apenas dados fictícios.

Não usar:

```text
NIF real;
nome real;
morada real;
documentos reais;
rendimentos reais;
dados de saúde reais;
dados de candidatos.
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
mixed desnecessário
```

Em relações Eloquent, usar PHPDoc generics:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiScore> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

Em arrays estruturados, usar PHPDoc:

```php
/**
 * @return array{
 *   score: int,
 *   label: string,
 *   components: array{ocr: int, classification: int, extraction: int, consistency: int, risk: int},
 *   flags: list<array{code: string, severity: string, score_impact: int, message: string}>,
 *   requires_manual_review: bool
 * }
 */
```

Não adicionar `mixed` sem necessidade.

Não silenciar erros com ignores genéricos.

---

## 26. Verificação PHPStan Antes de Publicar

Antes de considerar a sprint pronta para publicação, tentar executar PHPStan uma única vez usando `phpstan.neon`, se o ficheiro existir.

Executar apenas uma tentativa:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint31-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint31-before-publish.json
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
Erros introduzidos em ficheiros novos/alterados pela Sprint 31
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
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint31-before-publish.json
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
docs/backlog/sprint-31-score-confianca-detecao-fraude-aperfeicoamento.md
docs/document-intelligence/ai-score.md
docs/document-intelligence/risk-flags.md
docs/document-intelligence/improvement-suggestions.md
docs/document-intelligence/assistant-dashboard.md
docs/document-intelligence/fraud-risk-language-and-gdpr.md
docs/qa/sprint-31-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 28.1 docs/document-intelligence/ai-score.md

Incluir:

```text
Objetivo
Componentes do score
Pesos
Labels
Penalizações
Exemplos
Limitações
Sem decisão automática
```

### 28.2 docs/document-intelligence/risk-flags.md

Incluir:

```text
Flags suportadas
Critérios de deteção
Severidades
Impacto no score
Revisão manual
Linguagem não acusatória
```

### 28.3 docs/document-intelligence/improvement-suggestions.md

Incluir:

```text
Objetivo
Templates
Estados
Edição por técnico
Sem envio automático
Exemplos de redação
Limites RGPD
```

### 28.4 docs/document-intelligence/assistant-dashboard.md

Incluir:

```text
Objetivo do assistente
Permissões
Filtros
Score
Flags
Sugestões
Mascaramento
Ações disponíveis
```

### 28.5 docs/document-intelligence/fraud-risk-language-and-gdpr.md

Incluir:

```text
Indicadores de risco
Proibição de acusação automática
Dados pessoais
Dados de saúde
Auditoria
Logs minimizados
Sem exclusão automática
Sem decisão automática
```

### 28.6 docs/qa/sprint-31-quality-report.md

Incluir:

```text
Comandos executados
Resultado das migrations
Resultado dos testes
Resultado do PHPStan antes de publicar
Confirmação de tentativa única com phpstan.neon
Erros legados identificados
Erros novos introduzidos: sim/não
Cobertura de score
Cobertura de flags
Cobertura de sugestões
Cobertura de dashboard
Cobertura de queue fake
Cobertura de eventos
Cobertura de auditoria
Confirmação de que não há exclusão automática
Riscos RGPD
Riscos funcionais
Riscos técnicos
Recomendação de publicação
```

---

## 29. Critérios de Aceitação

A Sprint 31 está concluída quando:

```text
Score IA 0-100 é calculado.
Score usa OCR.
Score usa consistência.
Score usa classificação.
Score usa campos extraídos.
Score usa divergências.
Componentes do score são explicáveis.
Labels do score são calculadas.
Documento expirado gera flag.
Documento ilegível gera flag.
Página cortada gera flag.
OCR insuficiente gera flag.
NIF diferente gera flag.
Nome diferente gera flag.
Rendimento incompatível gera flag.
Documento duplicado gera flag.
Documento vazio gera flag.
Campos obrigatórios ausentes geram flag.
Sugestões de aperfeiçoamento são geradas.
Sugestões ficam em draft por defeito.
Sugestões são neutras e não acusatórias.
Sugestão para divergência de NIF usa texto semelhante ao exemplo obrigatório.
Interface mostra Documento.
Interface mostra Score IA.
Interface mostra OCR Excelente quando aplicável.
Interface mostra Classificação correta quando aplicável.
Interface mostra Divergência rendimento quando aplicável.
Interface mostra Rever manualmente quando aplicável.
Assistente respeita auth, policies e auditoria.
Valores sensíveis são mascarados sem permissão reforçada.
Dados de saúde são protegidos.
raw_ai_json não aparece por defeito.
ocr_text integral não aparece por defeito.
extraction_json integral não aparece por defeito.
A IA nunca exclui automaticamente uma candidatura.
Nenhum score altera estado/pontuação/elegibilidade/rendimentos/agregado/workflow.
Nenhuma sugestão é enviada automaticamente ao candidato.
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
Exclusão automática de candidaturas.
Aprovação automática de candidaturas.
Reprovação automática de documentos.
Validação documental final automática.
Acusação automática de fraude.
Marcação definitiva de documento falso.
Alteração automática de pontuação.
Alteração automática de elegibilidade.
Alteração automática de tipologia.
Alteração automática de renda.
Alteração automática de listas.
Alteração automática de estado do processo.
Envio automático de pedido de aperfeiçoamento ao candidato.
Envio de decisão automática ao candidato.
Consulta externa à AT.
Consulta externa à Segurança Social.
Consulta bancária.
Verificação de autenticidade documental por entidade externa.
Reconhecimento facial.
Biometria.
Assinatura digital.
Integração CMD/autenticacao.gov.
APIs pagas.
Treino/fine-tuning de modelos.
```

---

## 31. Riscos e Mitigações

### 31.1 Risco de linguagem acusatória

Mitigação:

```text
Usar indicadores de risco.
Usar divergência/inconsistência.
Evitar fraude confirmada/documento falso.
Templates neutros.
Testes para sugestões não acusatórias.
```

### 31.2 Risco de decisão automática indevida

Mitigação:

```text
Não alterar candidatura.
Não alterar estados.
Não alterar pontuação.
Não alterar elegibilidade.
Não enviar sugestões automaticamente.
Testes explícitos contra alterações funcionais.
```

### 31.3 Risco RGPD

Mitigação:

```text
Mascaramento.
Policies.
Auditoria sem valores pessoais.
Logs minimizados.
Dados de saúde protegidos.
Sem APIs pagas/externas.
Sugestões sem dados pessoais desnecessários.
```

### 31.4 Risco de score opaco

Mitigação:

```text
Sub-scores.
Explicação do score.
Pesos configuráveis.
Flags com impacto.
Documentação.
```

---

## 32. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Dependências das Sprints 27/28/29/30 reaproveitadas
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
17. Estado do Score IA 0-100
18. Estado dos componentes OCR/classificação/extração/consistência/risco
19. Estado da explicação do score
20. Estado da flag Documento expirado
21. Estado da flag Documento ilegível
22. Estado da flag Página cortada
23. Estado da flag OCR insuficiente
24. Estado da flag NIF diferente
25. Estado da flag Nome diferente
26. Estado da flag Rendimento incompatível
27. Estado da flag Documento duplicado
28. Estado da flag Documento vazio
29. Estado da flag Campos obrigatórios ausentes
30. Estado das sugestões de aperfeiçoamento
31. Estado dos templates de sugestão
32. Estado da sugestão obrigatória para divergência de NIF
33. Estado da interface do assistente IA
34. Estado do mascaramento de dados sensíveis
35. Estado da proteção de dados de saúde
36. Confirmação de que a IA nunca exclui candidatura
37. Confirmação de que score/flags/sugestões não alteram estado/pontuação/elegibilidade
38. Confirmação de que sugestões não são enviadas automaticamente
39. Estado da auditoria
40. Estado dos logs minimizados
41. Confirmação de ausência de APIs pagas
42. Testes Unit criados ou alterados
43. Testes Feature criados ou alterados
44. Testes Queue fake criados ou alterados
45. Testes Eventos criados ou alterados
46. Testes Auditoria criados ou alterados
47. Resultado de php artisan route:list
48. Resultado de php artisan migrate, se aplicável
49. Resultado de php artisan test
50. Resultado de ./vendor/bin/pint, se aplicável
51. Resultado de npm run build, se aplicável
52. Resultado PHPStan antes de publicar
53. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
54. Erros PHPStan legados considerados
55. Novos erros PHPStan introduzidos pela Sprint 31: sim/não
56. Documentação criada ou atualizada
57. Riscos RGPD ainda existentes
58. Riscos técnicos ainda existentes
59. Pendências técnicas
60. Confirmação de que não foram usados dados pessoais reais
61. Confirmação de que não foram usadas credenciais
62. Confirmação de que não foram implementadas funcionalidades fora de âmbito
63. Recomendação objetiva para publicar ou não publicar
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 33. Definition of Done

A Sprint 31 só está concluída quando o sistema calcular score IA 0-100 com componentes explicáveis, detetar flags técnicas e de risco, gerar sugestões neutras de aperfeiçoamento em draft, apresentar interface técnica autorizada, proteger dados pessoais e de saúde, auditar ações relevantes, cobrir Unit/Feature/Queue/Eventos/Auditoria, documentar riscos e executar PHPStan antes de publicação com uma única tentativa usando `phpstan.neon` quando disponível, garantindo por testes e implementação que a IA nunca exclui automaticamente uma candidatura e que nenhum score, flag ou sugestão altera automaticamente estado, pontuação, elegibilidade, agregado, rendimentos, listas, contratos ou workflows.

---

## 34. Execução Imediata

Executa agora apenas:

```text
Sprint 31 — Score de Confiança, Deteção de Fraude e Apoio ao Aperfeiçoamento
```

Fim da master prompt da Sprint 31.

---

## 35. Execução Sprint 31

Estado: implementada.

Criado:

- `document_ai_scores`;
- `document_ai_suggestions`;
- extensão de `document_ai_flags`;
- `DocumentAiScoreLabel`;
- `DocumentAiRiskFlagCode`;
- `DocumentAiRiskSeverity`;
- `DocumentAiSuggestionStatus`;
- `DocumentAiAssistantPipeline`;
- `DocumentAiScoreCalculator`;
- `DocumentAiScoreExplainer`;
- `DocumentRiskFlagDetector`;
- `DocumentDuplicateDetector`;
- `DocumentQualityAnalyzer`;
- `DocumentSuggestionGenerator`;
- `DocumentSuggestionTemplateRegistry`;
- `DocumentAiAssistantPersister`;
- `DocumentAiAssistantDashboardService`;
- `CalculateDocumentAiScoreJob`;
- painel backoffice Assistente IA;
- policies, Form Requests, events, factories, fixtures e testes.

Decisões:

- o score é sempre assistivo;
- sugestões ficam em rascunho e não são enviadas automaticamente;
- linguagem neutra sem acusação automática de fraude;
- jobs transportam apenas IDs;
- painel não expõe OCR bruto, JSON bruto, extração bruta ou paths internos.

Teste focado inicial:

- 14 testes / 91 asserções OK.
