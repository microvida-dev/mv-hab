# Sprint 30 — Validação Automática e Cruzamento com a Candidatura

## 1. Objetivo da Sprint

Implementar validação automática assistida por IA e regras determinísticas para comparar os dados extraídos dos documentos com a informação introduzida pelo candidato na candidatura.

Esta sprint evolui o módulo **Document Intelligence** criado nas Sprints 27, 28 e 29:

```text
Sprint 27: infraestrutura base de análise documental por IA
Sprint 28: OCR e classificação automática do documento
Sprint 29: extração estruturada de campos
Sprint 30: validação automática e cruzamento com a candidatura
```

A validação automática desta sprint deve gerar apenas:

```text
comparações;
alertas;
níveis de divergência;
evidência técnica;
recomendações de revisão manual;
dashboard técnico.
```

Regra central:

```text
A IA nunca exclui automaticamente uma candidatura.
```

Regra funcional complementar:

```text
Nenhuma validação automática altera automaticamente candidatura, agregado, rendimentos, pontuação, elegibilidade, tipologia, renda, estado, lista, decisão, contrato ou workflow.
```

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 30 — Validação Automática e Cruzamento com a Candidatura
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Dependências das Sprints 27, 28 e 29

Assumir que já existem, ou devem ser reaproveitados se existirem:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
DocumentAiStatus
DocumentAiDocumentType
DocumentAiExtractionStatus
DocumentAiPipeline
DocumentClassificationPipeline
DocumentFieldExtractionPipeline
ProcessDocumentAiJob
OCR local
Classificação automática
Extração estruturada
raw_ai_json
ocr_text
extraction_json
detected_document_type
document_ai_fields
Auditoria
Backoffice de classificação/extração
```

Antes de implementar, confirmar a estrutura real existente no projeto.

Se alguma peça das Sprints 27/28/29 ainda não existir, criar apenas adaptação mínima compatível, sem duplicar módulos, services, jobs ou tabelas.

Não criar uma segunda infraestrutura paralela de análise documental.

---

## 4. Princípios Técnicos

Preservar todas as funcionalidades existentes.

Documentos continuam privados por defeito.

A candidatura e os dados declarados pelo candidato continuam a ser a fonte funcional introduzida pelo utilizador até revisão/validação humana.

Dados extraídos por IA são evidência auxiliar, não verdade administrativa.

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

Esta sprint cria uma camada de validação assistida:

```text
Dados da candidatura
versus
Dados extraídos dos documentos
```

O resultado deve ser apresentado como:

```text
coincide;
divergência ligeira;
divergência média;
divergência crítica;
inconclusivo;
não aplicável;
necessita revisão manual.
```

O sistema pode assinalar:

```text
✓ Nome coincide
⚠ IRS superior ao declarado
⚠ Recibo incompatível
✓ Documento válido
```

Mas não pode:

```text
excluir candidatura;
aprovar documento;
reprovar documento;
alterar pontuação;
alterar elegibilidade;
alterar estado do processo;
substituir decisão técnica;
notificar decisão final ao candidato.
```

---

## 6. Validações Obrigatórias

### 6.1 Identificação

Comparar dados extraídos de documentos de identificação com dados da candidatura/utilizador/agregado.

Validações obrigatórias:

```text
Nome coincide
NIF coincide
Data nascimento coincide
```

Regras recomendadas:

```text
Nome: usar comparação normalizada, sem acentos, case-insensitive, tolerância a nomes intermédios.
NIF: comparação exata após normalização.
Data nascimento: comparação exata após normalização ISO.
```

Resultados possíveis:

```text
match
partial_match
mismatch
missing_candidate_value
missing_document_value
inconclusive
```

Exemplos:

```text
✓ Nome coincide
✓ NIF coincide
⚠ Data de nascimento não coincide
⚠ Nome parcialmente compatível
```

### 6.2 Agregado

Comparar informação documental e candidatura/agregado.

Validações obrigatórias:

```text
Número de membros consistente
Dependentes compatíveis
```

Regras recomendadas:

```text
Comparar número de membros declarados com documentos esperados/submetidos quando possível.
Comparar dependentes apenas quando existirem documentos de suporte extraídos.
Não inferir dependentes sem evidência.
Não alterar agregado.
```

Resultados possíveis:

```text
consistent
partially_consistent
inconsistent
not_enough_evidence
not_applicable
```

Exemplos:

```text
✓ Número de membros consistente
⚠ Dependentes sem documentação suficiente
⚠ Documento sugere dependente não declarado
```

### 6.3 Rendimentos

Comparar rendimentos extraídos com rendimentos declarados no formulário.

Validações obrigatórias:

```text
IRS vs formulário
Recibos vs formulário
Segurança Social vs formulário
```

Regras recomendadas:

```text
IRS: comparar rendimento global/coletável com rendimento anual declarado quando existir mapeamento seguro.
Recibos: comparar salário base, ilíquido e líquido com rendimento mensal declarado quando aplicável.
Segurança Social: comparar prestação/valor com apoios ou rendimentos declarados quando aplicável.
Não somar automaticamente rendimentos ao agregado.
Não recalcular elegibilidade.
Não recalcular pontuação.
Não decidir incumprimento.
```

Alertas obrigatórios:

```text
IRS superior ao declarado
Recibo incompatível
Segurança Social incompatível
```

Exemplos:

```text
⚠ IRS superior ao declarado
⚠ Recibo incompatível
✓ Recibo compatível com rendimento declarado
⚠ Prestação Segurança Social não declarada ou divergente
```

### 6.4 Habitação

Comparar dados de habitação extraídos com a candidatura.

Validações obrigatórias:

```text
Morada consistente
Contrato consistente
```

Regras recomendadas:

```text
Morada: usar normalização leve, sem geocoding externo.
Contrato: comparar inquilino, renda, datas e morada quando existirem.
Não alterar morada declarada.
Não alterar situação habitacional.
Não validar juridicamente contrato.
```

Exemplos:

```text
✓ Morada consistente
⚠ Morada do contrato difere da morada declarada
✓ Contrato consistente
⚠ Renda no contrato superior à declarada
```

---

## 7. Alertas Obrigatórios

Criar níveis de alerta:

```text
divergencia_critica
divergencia_media
divergencia_ligeira
```

Labels:

```text
Divergência crítica
Divergência média
Divergência ligeira
```

### 7.1 Divergência crítica

Usar para:

```text
NIF extraído diferente do NIF declarado.
Data de nascimento diferente em documento de identificação.
Nome totalmente incompatível em documento de identificação.
Rendimento documental muito superior ao declarado acima de limiar configurável.
Documento associado a pessoa aparentemente diferente.
```

Consequência:

```text
marcar para revisão manual;
destacar no dashboard técnico;
auditar;
não excluir candidatura automaticamente.
```

### 7.2 Divergência média

Usar para:

```text
Nome parcialmente divergente.
Morada parcialmente divergente.
Renda do contrato diferente da declarada.
Recibo com valor fora de tolerância configurável.
Documento incompleto mas com sinais relevantes.
```

Consequência:

```text
revisão técnica recomendada;
não alterar candidatura automaticamente.
```

### 7.3 Divergência ligeira

Usar para:

```text
Diferenças de grafia.
Abreviaturas.
Pequenas diferenças de morada.
Arredondamentos monetários.
Campos ausentes mas não bloqueantes.
```

Consequência:

```text
assinalar como nota de validação;
pode não exigir intervenção imediata;
não alterar candidatura automaticamente.
```

---

## 8. Dashboard Técnico Obrigatório

Criar ou completar dashboard técnico de validação documental.

Nome sugerido:

```text
Backoffice > Candidaturas > Validação IA
```

Mostrar:

```text
✓ Nome coincide
⚠ IRS superior ao declarado
⚠ Recibo incompatível
✓ Documento válido
```

Campos mínimos:

```text
Candidatura
Candidato
Documento
Tipo documental
Validação
Resultado
Severidade
Confiança
Estado
Revisão manual
Data
```

Resumo por candidatura:

```text
Total de validações
Validações coincidentes
Divergências críticas
Divergências médias
Divergências ligeiras
Inconclusivas
Documentos pendentes
Última análise
```

Detalhe por validação:

```text
Campo comparado
Valor declarado, mascarado quando necessário
Valor extraído, mascarado quando necessário
Resultado
Severidade
Confiança
Fonte
Mensagem técnica
Recomendação de revisão
```

Não mostrar por defeito:

```text
raw_ai_json
ocr_text integral
extraction_json integral
documento original inline
path privado
dados de saúde sem permissão explícita
```

---

## 9. Modelo de Dados

Reutilizar tabelas das Sprints 27-29 sempre que possível.

Criar tabela:

```text
document_ai_validations
```

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
application_id nullable foreign key
document_submission_id nullable foreign key, se existir
validation_group string indexed
validation_key string indexed
label string
status string indexed
severity string nullable indexed
confidence decimal nullable
candidate_value text nullable
extracted_value text nullable
candidate_value_hash string nullable
extracted_value_hash string nullable
value_type string nullable
comparison_method string nullable
message string nullable
recommendation string nullable
requires_manual_review boolean default false
reviewed_at timestamp nullable
reviewed_by foreign key nullable
review_notes text nullable
metadata json nullable
created_at
updated_at
```

Criar tabela opcional, se útil para agregação:

```text
document_ai_validation_runs
```

Campos recomendados:

```text
id
application_id foreign key
status string indexed
total_checks unsignedInteger default 0
matches_count unsignedInteger default 0
critical_count unsignedInteger default 0
medium_count unsignedInteger default 0
light_count unsignedInteger default 0
inconclusive_count unsignedInteger default 0
requires_manual_review boolean default false
started_at timestamp nullable
completed_at timestamp nullable
failed_at timestamp nullable
failure_reason text nullable
created_by nullable
timestamps
```

Escolha recomendada:

```text
document_ai_validations é obrigatório.
document_ai_validation_runs é recomendado para dashboard técnico e idempotência.
```

Regras RGPD:

```text
candidate_value e extracted_value podem conter dados pessoais.
Mascarar no UI conforme policy.
Não indexar valores pessoais diretamente.
candidate_value_hash e extracted_value_hash podem apoiar comparação/auditoria sem expor valor.
Não guardar dados sensíveis em logs técnicos.
```

Índices recomendados:

```text
application_id
document_ai_analysis_id
validation_group
validation_key
status
severity
requires_manual_review
created_at
```

---

## 10. Enums Obrigatórios

Criar:

```text
App\Enums\DocumentAiValidationStatus
App\Enums\DocumentAiValidationSeverity
App\Enums\DocumentAiValidationGroup
App\Enums\DocumentAiComparisonMethod
```

### 10.1 DocumentAiValidationStatus

Valores:

```text
match
partial_match
mismatch
inconclusive
not_applicable
missing_candidate_value
missing_document_value
manual_review
failed
```

### 10.2 DocumentAiValidationSeverity

Valores:

```text
none
divergencia_ligeira
divergencia_media
divergencia_critica
```

### 10.3 DocumentAiValidationGroup

Valores:

```text
identificacao
agregado
rendimentos
habitacao
documento
```

### 10.4 DocumentAiComparisonMethod

Valores:

```text
exact
normalized_exact
fuzzy_name
date
money_tolerance
address_similarity
document_consistency
manual
```

Usar casts nos models.

Evitar strings soltas fora de migrations e testes específicos.

---

## 11. Models Obrigatórios

Criar:

```text
App\Models\DocumentAiValidation
App\Models\DocumentAiValidationRun, se criar tabela de runs
```

Relações mínimas:

```text
DocumentAiValidation belongsTo DocumentAiAnalysis
DocumentAiValidation belongsTo Application, se o model existir
DocumentAiValidation belongsTo DocumentSubmission, se o model existir
DocumentAiValidation belongsTo reviewedBy User
DocumentAiAnalysis hasMany DocumentAiValidation
Application hasMany DocumentAiValidation, se aplicável
```

Requisitos:

```text
casts para enums, boolean, decimal, json e datetime;
fillable conservador;
PHPDoc generics para relações;
scopes úteis para severity/status;
```

Exemplo PHPStan:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiValidation> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

---

## 12. Services Obrigatórios

Criar ou completar services:

```text
App\Services\DocumentIntelligence\DocumentCandidateValidationPipeline
App\Services\DocumentIntelligence\CandidateDeclaredDataResolver
App\Services\DocumentIntelligence\ExtractedDocumentDataResolver
App\Services\DocumentIntelligence\DocumentValidationRuleRegistry
App\Services\DocumentIntelligence\DocumentValidationComparator
App\Services\DocumentIntelligence\DocumentValidationSeverityResolver
App\Services\DocumentIntelligence\DocumentValidationPersister
App\Services\DocumentIntelligence\DocumentValidationDashboardService
```

### 12.1 DocumentCandidateValidationPipeline

Responsável por:

```text
Receber candidatura ou análise documental.
Carregar dados declarados.
Carregar campos extraídos.
Determinar regras aplicáveis.
Executar comparações.
Resolver severidades.
Persistir validações.
Criar alertas/flags.
Emitir eventos.
Auditar.
Não alterar candidatura.
```

### 12.2 CandidateDeclaredDataResolver

Responsável por:

```text
Ler dados declarados pelo candidato.
Ler dados do agregado.
Ler rendimentos declarados.
Ler situação habitacional declarada.
Normalizar valores para comparação.
Não modificar models.
Não persistir alterações funcionais.
```

### 12.3 ExtractedDocumentDataResolver

Responsável por:

```text
Ler document_ai_fields.
Ler extraction_json validado.
Filtrar campos por tipo documental.
Normalizar valores extraídos.
Respeitar dados sensíveis.
Não expor valores indevidamente.
```

### 12.4 DocumentValidationRuleRegistry

Responsável por:

```text
Definir regras por grupo.
Definir regras por tipo documental.
Definir tolerâncias.
Definir severidades base.
Definir mensagens técnicas.
Definir quando requer revisão manual.
```

### 12.5 DocumentValidationComparator

Responsável por:

```text
Comparação exata.
Comparação normalizada.
Comparação fuzzy de nome.
Comparação de datas.
Comparação monetária com tolerância.
Comparação leve de moradas.
Comparação de consistência documental.
Retornar resultado estruturado.
```

### 12.6 DocumentValidationSeverityResolver

Responsável por:

```text
Converter resultado em severidade.
Aplicar limiares configuráveis.
Marcar divergências críticas.
Marcar divergências médias.
Marcar divergências ligeiras.
Marcar inconclusivo quando faltam dados.
```

### 12.7 DocumentValidationPersister

Responsável por:

```text
Guardar document_ai_validations.
Atualizar validation_run, se existir.
Evitar duplicados.
Garantir idempotência.
Guardar hashes quando útil.
Não escrever nas tabelas funcionais da candidatura.
```

### 12.8 DocumentValidationDashboardService

Responsável por:

```text
Construir listagem técnica.
Agregações por candidatura.
Agregações por severidade.
Filtros por concurso/candidatura/estado/severidade.
Evitar N+1.
Minimizar dados pessoais.
Aplicar autorização no controller/policies.
```

---

## 13. Configuração Obrigatória

Criar:

```text
config/document-ai-validation.php
```

Estrutura recomendada:

```php
return [
    'enabled' => env('DOCUMENT_AI_VALIDATION_ENABLED', true),
    'thresholds' => [
        'name_similarity_match' => (float) env('DOCUMENT_AI_VALIDATION_NAME_MATCH', 0.92),
        'name_similarity_partial' => (float) env('DOCUMENT_AI_VALIDATION_NAME_PARTIAL', 0.80),
        'money_light_tolerance_percent' => (float) env('DOCUMENT_AI_VALIDATION_MONEY_LIGHT_TOLERANCE', 5),
        'money_medium_tolerance_percent' => (float) env('DOCUMENT_AI_VALIDATION_MONEY_MEDIUM_TOLERANCE', 15),
        'critical_income_difference_percent' => (float) env('DOCUMENT_AI_VALIDATION_CRITICAL_INCOME_DIFF', 25),
        'address_similarity_match' => (float) env('DOCUMENT_AI_VALIDATION_ADDRESS_MATCH', 0.85),
    ],
    'store_plain_values' => env('DOCUMENT_AI_VALIDATION_STORE_PLAIN_VALUES', true),
    'hash_values' => env('DOCUMENT_AI_VALIDATION_HASH_VALUES', true),
];
```

Regras:

```text
Não colocar credenciais.
Tolerâncias devem ser configuráveis.
Desativar plain values deve ser possível em ambiente mais restritivo.
```

---

## 14. Regras de Comparação

### 14.1 Nome

Normalizar:

```text
trim;
case-insensitive;
sem acentos;
remover espaços duplicados;
remover partículas opcionais apenas para scoring, sem alterar valor original.
```

Comparar:

```text
exact normalized;
similaridade por tokens;
ordem de nomes;
nomes intermédios em falta.
```

Não considerar match se:

```text
primeiro e último nome forem incompatíveis;
documento parecer pertencer a outra pessoa;
confiança da extração for baixa.
```

### 14.2 NIF

Normalizar:

```text
remover espaços;
remover pontuação;
manter apenas dígitos.
```

Comparar:

```text
exato.
```

Se diferente:

```text
divergência crítica.
```

### 14.3 Data de nascimento

Normalizar:

```text
YYYY-MM-DD.
```

Comparar:

```text
exato.
```

Se diferente em documento de identificação:

```text
divergência crítica ou média conforme confiança.
```

### 14.4 Número de membros

Comparar:

```text
número declarado no agregado;
documentos associados a membros;
dependentes documentados, quando aplicável.
```

Se informação insuficiente:

```text
inconclusive.
```

### 14.5 Rendimentos

Comparar:

```text
rendimento anual IRS vs rendimento anual declarado;
recibo mensal vs rendimento mensal declarado;
prestação Segurança Social vs apoios/prestações declaradas.
```

Tolerância:

```text
configurável por percentagem;
arredondamentos monetários não devem gerar crítica;
diferenças relevantes devem gerar média/crítica.
```

Não calcular elegibilidade.

Não atualizar rendimentos.

### 14.6 Morada

Normalizar levemente:

```text
case-insensitive;
sem acentos;
abreviaturas comuns;
espaços duplicados.
```

Não geocodificar.

Não chamar APIs externas.

### 14.7 Contrato

Comparar:

```text
inquilino;
morada;
renda;
data início;
data fim, quando declarada.
```

Não validar juridicamente o contrato.

---

## 15. Jobs e Pipeline

Reutilizar:

```text
ProcessDocumentAiJob
DocumentAiPipeline
DocumentFieldExtractionPipeline
```

Criar job, se a arquitetura separar etapas:

```text
App\Jobs\ValidateDocumentAiAgainstApplicationJob
```

Requisitos:

```text
Job recebe apenas application_id e/ou document_ai_analysis_id.
Job não serializa dados pessoais.
Job não serializa valores extraídos.
Job não serializa JSON bruto.
Job usa afterCommit quando aplicável.
Job falha de forma controlada.
Job cria validações e alertas.
Job nunca altera candidatura.
Job emite eventos.
```

Execução recomendada:

```text
Após extração estruturada concluída, despachar validação contra candidatura se existir vínculo seguro.
Permitir reprocessamento manual autorizado.
Garantir idempotência.
```

---

## 16. Eventos Recomendados

Criar:

```text
App\Events\DocumentCandidateValidationStarted
App\Events\DocumentCandidateValidationCompleted
App\Events\DocumentCandidateValidationFailed
App\Events\DocumentCandidateValidationRequiresReview
App\Events\DocumentCandidateCriticalDivergenceDetected
```

Eventos devem transportar:

```text
ID da candidatura
ID da análise
ID da validação ou run
contagens por severidade
estado
```

Eventos não devem transportar:

```text
valores declarados;
valores extraídos;
ocr_text;
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
Início de validação cruzada.
Conclusão de validação cruzada.
Falha de validação cruzada.
Divergência crítica detetada.
Consulta do dashboard técnico.
Consulta do detalhe de validação.
Marcação de validação para revisão manual.
```

Não auditar:

```text
Nome completo.
NIF.
Data de nascimento.
Morada.
Valores de rendimento.
Dados de saúde.
JSON bruto.
OCR integral.
Valores extraídos.
Valores declarados.
```

Auditoria deve conter:

```text
ID da candidatura
ID da análise
ID da validação/run
grupo
chave de validação
status
severidade
confiança
utilizador responsável, quando existir
timestamp
```

Dados pessoais devem ser minimizados.

Valores apresentados no dashboard devem respeitar policies e mascaramento.

---

## 18. Policies e Permissões

Criar ou completar:

```text
DocumentAiValidationPolicy
DocumentAiValidationRunPolicy
```

Permissões mínimas:

```text
viewAny
view
viewSensitiveValues
viewIncomeValues
viewHealthRelatedValues
markManualReview
rerunValidation
```

Regras:

```text
Guest não acede.
Candidato não acede ao dashboard técnico IA nesta sprint.
Técnico autorizado vê resultados resumidos.
Técnico com permissão reforçada vê valores sensíveis.
Dados de saúde exigem permissão explícita.
Auditor pode consultar sem alterar, se perfil existir.
Admin pode reprocessar e marcar revisão manual.
```

Nunca confiar apenas no frontend.

---

## 19. Backoffice — Dashboard Técnico

Criar ou completar:

```text
App\Http\Controllers\Backoffice\DocumentAiValidationController
```

Métodos recomendados:

```php
index()
show(DocumentAiValidationRun|Application $target)
validation(DocumentAiValidation $validation)
markManualReview(DocumentAiValidation $validation)
rerun(Application $application)
```

Rotas sugeridas, adaptar ao projeto:

```php
Route::middleware(['auth'])
    ->prefix('backoffice/candidaturas/ia')
    ->name('backoffice.document-ai-validations.')
    ->group(function (): void {
        Route::get('/validacoes', [DocumentAiValidationController::class, 'index'])
            ->name('index');
        Route::get('/validacoes/{application}', [DocumentAiValidationController::class, 'show'])
            ->name('show');
        Route::get('/validacoes/detalhe/{validation}', [DocumentAiValidationController::class, 'validation'])
            ->name('validation');
        Route::post('/validacoes/{validation}/revisao-manual', [DocumentAiValidationController::class, 'markManualReview'])
            ->name('manual-review');
        Route::post('/validacoes/{application}/reprocessar', [DocumentAiValidationController::class, 'rerun'])
            ->name('rerun');
    });
```

Se o projeto já tiver grupo backoffice, usar o grupo real.

Não duplicar prefixos.

Não criar rotas públicas.

---

## 20. Form Requests

Criar:

```text
App\Http\Requests\Backoffice\FilterDocumentAiValidationsRequest
App\Http\Requests\Backoffice\MarkDocumentAiValidationReviewRequest
App\Http\Requests\Backoffice\RerunDocumentAiValidationRequest
```

Filtros recomendados:

```php
'contest_id' => ['nullable', 'integer'],
'application_id' => ['nullable', 'integer'],
'candidate_id' => ['nullable', 'integer'],
'validation_group' => ['nullable', 'string'],
'status' => ['nullable', 'string'],
'severity' => ['nullable', 'string'],
'requires_manual_review' => ['nullable', 'boolean'],
'created_from' => ['nullable', 'date'],
'created_until' => ['nullable', 'date', 'after_or_equal:created_from'],
```

Validar enums com `Rule::enum()` quando disponível.

---

## 21. Views / Páginas

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/document-ai/validations/index.blade.php
resources/views/backoffice/document-ai/validations/show.blade.php
resources/views/backoffice/document-ai/validations/_summary.blade.php
resources/views/backoffice/document-ai/validations/_validation-table.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes na stack real.

Não mudar stack frontend.

Index:

```text
Listagem por candidatura.
Filtros por concurso, severidade, estado e revisão manual.
Badges por divergência.
Resumo de críticas/médias/ligeiras.
```

Show:

```text
Resumo da candidatura.
Resumo das validações.
Tabela por grupo.
Documentos analisados.
Alertas.
Ações autorizadas.
```

Detalhe:

```text
Campo comparado.
Valor declarado mascarado.
Valor extraído mascarado.
Status.
Severidade.
Confiança.
Método de comparação.
Mensagem.
Recomendação.
Auditoria, se autorizada.
```

Não criar botão para aplicar valor automaticamente à candidatura nesta sprint.

---

## 22. UX Obrigatória

Apresentar de forma clara:

```text
✓ Nome coincide
⚠ IRS superior ao declarado
⚠ Recibo incompatível
✓ Documento válido
```

Estados visuais:

```text
match: verde
divergência ligeira: amarelo claro
divergência média: laranja
divergência crítica: vermelho
inconclusivo: cinzento
manual_review: azul/cinza
```

Nota obrigatória no dashboard:

```text
As validações automáticas são auxiliares à análise técnica e não produzem decisão automática sobre a candidatura.
```

Evitar linguagem conclusiva como:

```text
fraude
falso
inválido definitivo
excluir
reprovar automaticamente
```

Preferir:

```text
divergência;
incompatibilidade;
revisão necessária;
evidência insuficiente;
comparação inconclusiva.
```

---

## 23. Testes Obrigatórios

Criar ou completar testes.

### 23.1 Unit — Comparadores

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentValidationComparatorTest.php
tests/Unit/DocumentIntelligence/DocumentValidationSeverityResolverTest.php
tests/Unit/DocumentIntelligence/DocumentValidationRuleRegistryTest.php
tests/Unit/DocumentIntelligence/CandidateDeclaredDataResolverTest.php
tests/Unit/DocumentIntelligence/ExtractedDocumentDataResolverTest.php
```

Cobrir:

```text
Nome igual normalizado gera match.
Nome parcialmente compatível gera partial_match.
Nome incompatível gera mismatch.
NIF igual gera match.
NIF diferente gera divergência crítica.
Data nascimento igual gera match.
Data nascimento diferente gera divergência crítica/média.
Dinheiro dentro da tolerância gera match ou divergência ligeira.
Dinheiro fora da tolerância gera média/crítica.
Morada semelhante gera match ou parcial.
Dados insuficientes geram inconclusive.
```

### 23.2 Unit — Pipeline

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentCandidateValidationPipelineTest.php
tests/Unit/DocumentIntelligence/DocumentValidationPersisterTest.php
tests/Unit/DocumentIntelligence/DocumentValidationDashboardServiceTest.php
```

Cobrir:

```text
Pipeline cria validações.
Pipeline é idempotente.
Pipeline cria alertas de severidade.
Pipeline marca revisão manual para crítica.
Pipeline não altera candidatura.
Pipeline não altera agregado.
Pipeline não altera rendimentos.
Pipeline não altera pontuação.
Pipeline não altera elegibilidade.
Dashboard evita N+1 quando possível.
```

### 23.3 Feature — Integração com candidatura

Criar:

```text
tests/Feature/DocumentIntelligence/DocumentCandidateValidationIntegrationTest.php
```

Cobrir:

```text
Validação compara identificação.
Validação compara agregado.
Validação compara IRS vs formulário.
Validação compara recibos vs formulário.
Validação compara Segurança Social vs formulário.
Validação compara morada.
Validação compara contrato.
Divergência crítica é guardada.
Divergência média é guardada.
Divergência ligeira é guardada.
A candidatura não é excluída.
Estado da candidatura não muda.
Pontuação não muda.
Elegibilidade não muda.
```

### 23.4 Feature — Dashboard técnico

Criar:

```text
tests/Feature/Backoffice/DocumentAiValidationDashboardTest.php
```

Cobrir:

```text
Técnico autorizado vê dashboard.
Guest não acede.
Candidato não acede.
Dashboard mostra Nome coincide.
Dashboard mostra IRS superior ao declarado.
Dashboard mostra Recibo incompatível.
Dashboard mostra Documento válido quando aplicável.
Valores sensíveis são mascarados sem permissão reforçada.
Dados de saúde não aparecem sem permissão explícita.
raw_ai_json não aparece.
ocr_text integral não aparece.
extraction_json integral não aparece.
Reprocessar exige autorização.
Marcar revisão manual exige autorização.
Consulta é auditada quando aplicável.
```

### 23.5 Queue fake

Cobrir:

```text
ValidateDocumentAiAgainstApplicationJob é despachado quando aplicável.
Job recebe apenas IDs.
Job não transporta dados pessoais.
Job não transporta valores extraídos.
Job não transporta JSON bruto.
```

### 23.6 Eventos

Cobrir:

```text
DocumentCandidateValidationStarted
DocumentCandidateValidationCompleted
DocumentCandidateValidationFailed
DocumentCandidateValidationRequiresReview
DocumentCandidateCriticalDivergenceDetected
```

Usar:

```php
Event::fake();
Event::assertDispatched(DocumentCandidateValidationCompleted::class);
```

### 23.7 Auditoria

Cobrir:

```text
Validação concluída é auditada.
Divergência crítica é auditada.
Consulta dashboard é auditada.
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
tests/Fixtures/document-intelligence/validation/identificacao_match.json
tests/Fixtures/document-intelligence/validation/identificacao_mismatch.json
tests/Fixtures/document-intelligence/validation/irs_superior_declarado.json
tests/Fixtures/document-intelligence/validation/recibo_incompativel.json
tests/Fixtures/document-intelligence/validation/seguranca_social_incompativel.json
tests/Fixtures/document-intelligence/validation/morada_consistente.json
tests/Fixtures/document-intelligence/validation/contrato_inconsistente.json
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
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiValidation> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

Em arrays estruturados, usar PHPDoc:

```php
/**
 * @return array{
 *   group: string,
 *   key: string,
 *   status: string,
 *   severity: string,
 *   confidence: float|null,
 *   requires_manual_review: bool,
 *   message: string|null
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
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint30-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint30-before-publish.json
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
Erros introduzidos em ficheiros novos/alterados pela Sprint 30
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
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint30-before-publish.json
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
docs/backlog/sprint-30-validacao-automatica-cruzamento-candidatura.md
docs/document-intelligence/candidate-validation.md
docs/document-intelligence/validation-rules.md
docs/document-intelligence/validation-dashboard.md
docs/document-intelligence/validation-security-and-gdpr.md
docs/qa/sprint-30-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 28.1 docs/document-intelligence/candidate-validation.md

Incluir:

```text
Objetivo
Fluxo de validação
Dados declarados
Dados extraídos
Comparações
Alertas
Revisão manual
Limite funcional: IA nunca exclui candidatura
```

### 28.2 docs/document-intelligence/validation-rules.md

Incluir:

```text
Identificação
Agregado
Rendimentos
Habitação
Métodos de comparação
Tolerâncias
Severidades
Exemplos
```

### 28.3 docs/document-intelligence/validation-dashboard.md

Incluir:

```text
Objetivo do dashboard
Permissões
Filtros
Campos apresentados
Estados visuais
Mascaramento
Ações disponíveis
Limitações
```

### 28.4 docs/document-intelligence/validation-security-and-gdpr.md

Incluir:

```text
Dados pessoais
Dados de rendimentos
Dados de saúde
Mascaramento
Auditoria
Logs minimizados
Sem decisão automática
Sem exclusão automática
```

### 28.5 docs/qa/sprint-30-quality-report.md

Incluir:

```text
Comandos executados
Resultado das migrations
Resultado dos testes
Resultado do PHPStan antes de publicar
Confirmação de tentativa única com phpstan.neon
Erros legados identificados
Erros novos introduzidos: sim/não
Cobertura de identificação
Cobertura de agregado
Cobertura de rendimentos
Cobertura de habitação
Cobertura de dashboard
Cobertura de queue fake
Cobertura de eventos
Cobertura de auditoria
Confirmação de que IA nunca exclui candidatura
Riscos RGPD
Riscos funcionais
Riscos técnicos
Recomendação de publicação
```

---

## 29. Critérios de Aceitação

A Sprint 30 está concluída quando:

```text
Validação automática cruza dados extraídos com candidatura.
Nome é comparado.
NIF é comparado.
Data de nascimento é comparada.
Número de membros do agregado é analisado.
Dependentes são analisados quando há evidência.
IRS é comparado com formulário.
Recibos são comparados com formulário.
Segurança Social é comparada com formulário.
Morada é comparada.
Contrato é comparado.
Divergência crítica é suportada.
Divergência média é suportada.
Divergência ligeira é suportada.
Dashboard técnico mostra Nome coincide.
Dashboard técnico mostra IRS superior ao declarado.
Dashboard técnico mostra Recibo incompatível.
Dashboard técnico mostra Documento válido quando aplicável.
Dashboard respeita auth, policies e auditoria.
Valores sensíveis são mascarados sem permissão reforçada.
Dados de saúde são protegidos.
raw_ai_json não aparece por defeito.
ocr_text integral não aparece por defeito.
extraction_json integral não aparece por defeito.
A IA nunca exclui automaticamente uma candidatura.
Estado da candidatura não muda automaticamente.
Pontuação não muda automaticamente.
Elegibilidade não muda automaticamente.
Rendimentos não mudam automaticamente.
Agregado não muda automaticamente.
Workflow não muda automaticamente.
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
Alteração automática de pontuação.
Alteração automática de elegibilidade.
Alteração automática de tipologia.
Alteração automática de renda.
Alteração automática de listas.
Alteração automática de estado do processo.
Envio de decisão automática ao candidato.
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

## 31. Riscos e Mitigações

### 31.1 Risco de decisão automática indevida

Mitigação:

```text
Não alterar candidatura.
Não alterar estados.
Não alterar pontuação.
Não alterar elegibilidade.
Testes explícitos contra alterações funcionais.
Mensagens de UI indicam apoio à análise técnica.
```

### 31.2 Risco de falso positivo

Mitigação:

```text
Severidades graduadas.
Tolerâncias configuráveis.
Resultado inconclusivo quando faltam dados.
Revisão manual obrigatória para divergências críticas.
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
```

### 31.4 Risco de acoplamento a modelos reais

Mitigação:

```text
Resolvers isolam a estrutura da candidatura.
Services não escrevem em models funcionais.
Comparadores recebem valores normalizados.
Persister só escreve tabelas de validação IA.
```

---

## 32. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Dependências das Sprints 27/28/29 reaproveitadas
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
17. Estado da validação de identificação
18. Estado da validação de nome
19. Estado da validação de NIF
20. Estado da validação de data de nascimento
21. Estado da validação de agregado
22. Estado da validação de dependentes
23. Estado da validação IRS vs formulário
24. Estado da validação recibos vs formulário
25. Estado da validação Segurança Social vs formulário
26. Estado da validação de morada
27. Estado da validação de contrato
28. Estado dos alertas de divergência crítica
29. Estado dos alertas de divergência média
30. Estado dos alertas de divergência ligeira
31. Estado do dashboard técnico
32. Estado do mascaramento de dados sensíveis
33. Estado da proteção de dados de saúde
34. Confirmação de que a IA nunca exclui candidatura
35. Confirmação de que estado/pontuação/elegibilidade não mudam automaticamente
36. Estado da auditoria
37. Estado dos logs minimizados
38. Confirmação de ausência de APIs pagas
39. Testes Unit criados ou alterados
40. Testes Feature criados ou alterados
41. Testes Queue fake criados ou alterados
42. Testes Eventos criados ou alterados
43. Testes Auditoria criados ou alterados
44. Resultado de php artisan route:list
45. Resultado de php artisan migrate, se aplicável
46. Resultado de php artisan test
47. Resultado de ./vendor/bin/pint, se aplicável
48. Resultado de npm run build, se aplicável
49. Resultado PHPStan antes de publicar
50. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
51. Erros PHPStan legados considerados
52. Novos erros PHPStan introduzidos pela Sprint 30: sim/não
53. Documentação criada ou atualizada
54. Riscos RGPD ainda existentes
55. Riscos técnicos ainda existentes
56. Pendências técnicas
57. Confirmação de que não foram usados dados pessoais reais
58. Confirmação de que não foram usadas credenciais
59. Confirmação de que não foram implementadas funcionalidades fora de âmbito
60. Recomendação objetiva para publicar ou não publicar
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 33. Definition of Done

A Sprint 30 só está concluída quando o sistema comparar dados extraídos com dados declarados na candidatura, gerar validações e alertas graduados por severidade, apresentar dashboard técnico autorizado, proteger dados pessoais e de saúde, auditar ações relevantes, cobrir Unit/Feature/Queue/Eventos/Auditoria, documentar riscos e executar PHPStan antes de publicação com uma única tentativa usando `phpstan.neon` quando disponível, garantindo por testes e implementação que a IA nunca exclui automaticamente uma candidatura e que nenhum estado, pontuação, elegibilidade, agregado, rendimento, lista, contrato ou workflow é alterado automaticamente.

---

## 34. Execução Imediata

Executa agora apenas:

```text
Sprint 30 — Validação Automática e Cruzamento com a Candidatura
```

Fim da master prompt da Sprint 30.

---

## 35. Execução técnica realizada em 22/06/2026

### Implementado

- Infraestrutura de validação assistida contra candidatura com `document_ai_validation_runs` e `document_ai_validations`.
- Enums de estado, severidade, grupo e método de comparação.
- DTOs para dados declarados, dados extraídos, regras e resultados de validação.
- Pipeline `DocumentCandidateValidationPipeline` para validar documento individual ou candidatura completa.
- Job `ValidateDocumentAiAgainstApplicationJob` disparada após extração estruturada quando existe candidatura associada.
- Regras determinísticas para identificação, rendimentos, habitação e atestado multiusos.
- Persistência de valores opcionais, hashes, severidade, flags e logs minimizados.
- Eventos de início, conclusão, falha, revisão manual e divergência crítica.
- Policies, Form Requests, controller e painel de backoffice.
- Mascaramento de dados sensíveis e ocultação de dados de saúde conforme permissões.
- Testes Unit, Feature, Queue fake, Eventos e Auditoria.

### Não implementado por estar fora de âmbito

- Exclusão automática de candidatura.
- Aprovação/rejeição automática de documentos.
- Alteração automática de estado, elegibilidade, pontuação, ranking, listas, contrato ou workflow.
- Integrações externas, APIs pagas, OCR adicional ou deteção de fraude da Sprint 31.

### Pendências

- Afinar thresholds com amostras documentais portuguesas anonimizadas.
- Validar retenção e exportação RGPD de `document_ai_validations`.
- Confirmar em produção se `DOCUMENT_AI_VALIDATION_STORE_PLAIN_VALUES` deve ficar ativo.
- Enriquecer validação de número de membros/dependentes quando houver evidência documental estruturada suficiente.
- Rever perfis autorizados a consultar dados de saúde com DPO/município.

### Ficheiros principais criados

- `database/migrations/2026_06_22_000030_create_document_ai_validation_tables.php`
- `app/Models/DocumentAiValidationRun.php`
- `app/Models/DocumentAiValidation.php`
- `app/Jobs/ValidateDocumentAiAgainstApplicationJob.php`
- `app/Services/DocumentIntelligence/DocumentCandidateValidationPipeline.php`
- `app/Http/Controllers/Backoffice/DocumentAiValidationController.php`
- `resources/views/backoffice/document-ai/validations/*`
- `tests/Unit/DocumentIntelligence/DocumentValidationServicesTest.php`
- `tests/Feature/DocumentIntelligence/DocumentCandidateValidationPipelineTest.php`
- `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php`

### Estado de validação

- Identificação: implementada para nome, NIF, data de nascimento e número documental quando existem campos extraídos.
- Agregado: implementada para grau de incapacidade em atestado multiusos; número de membros/dependentes permanece dependente de evidência documental futura.
- Rendimentos: implementada para IRS, nota de liquidação, recibo de vencimento e Segurança Social.
- Habitação: implementada para morada e renda em contrato/comprovativo.
- Alertas: divergências ligeiras, médias e críticas ficam persistidas e sinalizadas.
- Dashboard técnico: implementado no backoffice.
