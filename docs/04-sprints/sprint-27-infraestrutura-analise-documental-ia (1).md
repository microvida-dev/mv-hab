# Sprint 27 — Infraestrutura Base de Análise Documental por IA

## 1. Objetivo da Sprint

Implementar a infraestrutura base do módulo **Document Intelligence** para análise documental assistida por IA, sem alterar a lógica funcional existente da plataforma municipal de habitação.

Esta sprint deve criar apenas a fundação técnica para análises futuras:

- registo da análise;
- estado de processamento;
- extração técnica local;
- armazenamento do resultado bruto;
- flags para revisão manual;
- logs de processamento;
- eventos;
- queue;
- auditoria;
- testes.

Não deve haver decisões automáticas de elegibilidade, pontuação, validação documental, exclusão de candidatos, alteração de estados de candidatura ou alteração de workflows existentes.

---

## 2. Âmbito Obrigatório

Executar apenas:

```text
Sprint 27 — Infraestrutura Base de Análise Documental por IA
```

Não avançar para qualquer sprint seguinte sem validação explícita.

Criar um output final apenas para esta sprint.

Ignorar qualquer verificação de branch Git. Não executar comandos para validar branch atual, mudar branch, criar branch ou bloquear execução por causa da branch.

---

## 3. Princípios Técnicos

Preservar todas as funcionalidades existentes.

Integrar de forma incremental com os documentos privados já existentes.

Manter documentos privados por defeito.

Não expor dados pessoais em logs técnicos, mensagens de erro, eventos públicos ou responses não autorizadas.

Usar Eloquent, migrations reversíveis, casts tipados, enums, policies existentes quando aplicável, services, jobs, events e testes.

Controllers existentes devem continuar magros.

Toda a lógica crítica deve ficar em services ou jobs.

Não criar dependência obrigatória de serviços externos pagos.

Não usar dados pessoais reais em testes, seeders, logs ou documentação.

Não guardar credenciais no código, `.env.example`, documentação ou testes.

---

## 4. Stack e IA Gratuita Permitida

Usar apenas ferramentas locais/gratuitas:

```text
OCR: Tesseract OCR
PDF: Poppler
Conversão imagem: ImageMagick
NLP inicial: Ollama local
Modelo recomendado: Gemma 3 4B ou Qwen 2.5 7B Instruct
```

Não efetuar chamadas para APIs pagas.

Não integrar OpenAI, Anthropic, Google Vision, Azure AI, AWS Textract, Mistral Cloud ou qualquer fornecedor pago nesta sprint.

Se alguma ferramenta local não estiver instalada, o sistema deve:

- falhar de forma controlada;
- marcar a análise como `failed` ou `manual_review`, conforme o caso;
- registar log técnico sem dados pessoais;
- manter o upload documental funcional;
- documentar a limitação.

---

## 5. Entregáveis Obrigatórios

Criar o módulo:

```text
Document Intelligence
```

Criar tabelas:

```text
document_ai_analyses
document_ai_fields
document_ai_flags
document_ai_processing_logs
```

Criar enum:

```text
App\Enums\DocumentAiStatus
```

Valores obrigatórios:

```text
pending
processing
completed
failed
manual_review
```

Criar service:

```text
App\Services\DocumentIntelligence\DocumentAiPipeline
```

Criar queue job:

```text
App\Jobs\ProcessDocumentAiJob
```

Criar eventos:

```text
App\Events\DocumentAnalysisStarted
App\Events\DocumentAnalysisCompleted
App\Events\DocumentAnalysisFailed
```

Criar auditoria completa, usando o mecanismo de auditoria existente no projeto quando disponível.

Armazenar o JSON bruto produzido pela IA.

Integrar com documentos privados já existentes.

---

## 6. Leitura Inicial do Projeto

Antes de implementar, analisar a estrutura existente:

```bash
rg "class .*Document|DocumentSubmission|RequiredDocument|UploadedDocument|document" app database routes tests
rg "Audit|audit|Activity|DocumentAccess|DocumentAccessAction" app database tests
rg "ShouldQueue|Queue::fake|Event::fake" app tests
rg "enum .*Status|Status" app/Enums app
rg "StoreDocument|ReplaceDocument|DocumentSubmission" app/Http app/Models tests
```

Identificar:

```text
Modelo real usado para documentos submetidos
Controller/Service real usado no upload
Tabela real dos documentos privados
Mecanismo real de auditoria
Padrão real de eventos/jobs
Padrão real de testes
Padrão real de enums
```

Adaptar os nomes abaixo à estrutura real do projeto.

Não criar um segundo modelo de documentos se já existir um modelo correto para submissões documentais.

---

## 7. Modelo de Dados Recomendado

### 7.1 document_ai_analyses

Tabela principal de execução da análise.

Campos recomendados:

```text
id
document_submission_id nullable foreign key, se existir este modelo
documentable_type nullable
documentable_id nullable
status string/enum
engine string nullable
model string nullable
source_disk string nullable
source_path string nullable
source_mime string nullable
source_size_bytes unsignedBigInteger nullable
source_sha256 string nullable indexed
raw_text longText nullable
raw_ai_json json nullable
summary text nullable
confidence decimal nullable
started_at timestamp nullable
completed_at timestamp nullable
failed_at timestamp nullable
manual_review_at timestamp nullable
failure_reason text nullable
created_by foreign key nullable
updated_by foreign key nullable
timestamps
softDeletes, se o projeto usar SoftDeletes para dados sensíveis
```

Regras:

```text
Cada submissão documental deve poder ter pelo menos uma análise.
Evitar duplicação acidental de análises pendentes para o mesmo documento.
Não guardar conteúdo sensível em colunas usadas para pesquisa pública.
raw_text e raw_ai_json devem ser tratados como dados sensíveis.
```

Índices recomendados:

```text
document_submission_id
documentable_type + documentable_id
status
source_sha256
created_at
```

### 7.2 document_ai_fields

Tabela para campos extraídos de forma estruturada.

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
key string indexed
label string nullable
value text nullable
normalized_value text nullable
value_type string nullable
confidence decimal nullable
page unsignedInteger nullable
bbox json nullable
metadata json nullable
timestamps
```

Regras:

```text
Não usar estes campos para decisões automáticas nesta sprint.
Campos com dados pessoais devem respeitar policies e auditoria quando expostos no futuro.
```

### 7.3 document_ai_flags

Tabela para alertas técnicos ou necessidades de revisão.

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
code string indexed
severity string indexed
message string
details json nullable
requires_manual_review boolean default false
resolved_at timestamp nullable
resolved_by foreign key nullable
timestamps
```

Severidades recomendadas:

```text
info
low
medium
high
critical
```

Regras:

```text
Flags não devem reprovar automaticamente documentos.
Flags servem apenas para revisão e rastreabilidade.
```

### 7.4 document_ai_processing_logs

Tabela para logs técnicos da pipeline.

Campos recomendados:

```text
id
document_ai_analysis_id foreign key cascade
step string indexed
level string indexed
message string
context json nullable
duration_ms unsignedInteger nullable
created_at timestamp nullable
```

Regras:

```text
Não guardar texto integral do documento nos logs.
Não guardar NIF, IBAN, moradas completas, contactos ou dados pessoais em context.
Context deve conter apenas metadados técnicos minimizados.
```

---

## 8. Models Obrigatórios

Criar models:

```text
App\Models\DocumentAiAnalysis
App\Models\DocumentAiField
App\Models\DocumentAiFlag
App\Models\DocumentAiProcessingLog
```

Requisitos:

```text
Usar casts adequados para enum, json, datetime, decimal e boolean.
Definir fillable conservador.
Definir relações Eloquent com PHPDoc generics corretos.
Evitar mass assignment de campos sensíveis como status final, raw_ai_json e failure_reason fora dos services.
```

Relações mínimas:

```text
DocumentAiAnalysis hasMany DocumentAiField
DocumentAiAnalysis hasMany DocumentAiFlag
DocumentAiAnalysis hasMany DocumentAiProcessingLog
DocumentAiField belongsTo DocumentAiAnalysis
DocumentAiFlag belongsTo DocumentAiAnalysis
DocumentAiProcessingLog belongsTo DocumentAiAnalysis
```

Se existir modelo real de submissão documental, adicionar:

```text
DocumentAiAnalysis belongsTo DocumentSubmission
DocumentSubmission hasMany DocumentAiAnalysis
DocumentSubmission hasOne latestDocumentAiAnalysis, se útil e seguro
```

Se a estrutura documental for polimórfica, usar relação morphTo/morphMany em vez de forçar FK incorreta.

Exemplo de PHPDoc para PHPStan:

```php
/** @return \Illuminate\Database\Eloquent\Relations\HasMany<DocumentAiField, DocumentAiAnalysis> */
public function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(DocumentAiField::class);
}
```

---

## 9. Enum Obrigatório

Criar:

```php
namespace App\Enums;

enum DocumentAiStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case ManualReview = 'manual_review';
}
```

Usar o enum nos casts do model e nas transições de estado.

Evitar strings soltas fora de migrations e testes muito específicos.

---

## 10. DocumentAiPipeline

Criar:

```text
App\Services\DocumentIntelligence\DocumentAiPipeline
```

Responsabilidades:

```text
Criar análise pendente para documento submetido.
Despachar ProcessDocumentAiJob após commit, quando possível.
Marcar análise como processing.
Extrair texto por ferramenta local.
Converter PDF/imagem quando necessário.
Executar OCR local quando necessário.
Executar NLP local via Ollama, se disponível e configurado.
Guardar raw_text quando apropriado e seguro.
Guardar raw_ai_json.
Criar fields extraídos.
Criar flags técnicas.
Criar processing logs minimizados.
Emitir eventos.
Marcar completed, failed ou manual_review.
Registar auditoria.
```

Métodos mínimos recomendados:

```php
public function createPendingForDocument(Model $document, ?User $actor = null): DocumentAiAnalysis;

public function dispatch(DocumentAiAnalysis $analysis): void;

public function process(DocumentAiAnalysis $analysis): DocumentAiAnalysis;

public function markFailed(DocumentAiAnalysis $analysis, Throwable $exception): DocumentAiAnalysis;
```

Se o projeto preferir contratos/interfaces, criar interface interna:

```text
App\Contracts\DocumentIntelligence\DocumentAiPipelineContract
```

Não criar abstrações excessivas se o projeto não usar esse padrão.

---

## 11. Extração Local e Ollama

Criar configuração:

```text
config/document-ai.php
```

Chaves recomendadas:

```php
return [
    'enabled' => env('DOCUMENT_AI_ENABLED', true),
    'queue' => env('DOCUMENT_AI_QUEUE', 'default'),
    'ocr' => [
        'driver' => env('DOCUMENT_AI_OCR_DRIVER', 'tesseract'),
        'binary' => env('DOCUMENT_AI_TESSERACT_BINARY', 'tesseract'),
        'language' => env('DOCUMENT_AI_TESSERACT_LANG', 'por+eng'),
    ],
    'pdf' => [
        'pdftotext_binary' => env('DOCUMENT_AI_PDFTOTEXT_BINARY', 'pdftotext'),
        'pdfimages_binary' => env('DOCUMENT_AI_PDFIMAGES_BINARY', 'pdfimages'),
    ],
    'image' => [
        'magick_binary' => env('DOCUMENT_AI_MAGICK_BINARY', 'magick'),
    ],
    'ollama' => [
        'enabled' => env('DOCUMENT_AI_OLLAMA_ENABLED', false),
        'base_url' => env('DOCUMENT_AI_OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('DOCUMENT_AI_OLLAMA_MODEL', 'gemma3:4b'),
        'timeout' => (int) env('DOCUMENT_AI_OLLAMA_TIMEOUT', 120),
    ],
];
```

Não adicionar credenciais.

Não tornar Ollama obrigatório para upload documental funcionar.

Se Ollama não estiver ativo, a pipeline pode guardar OCR/texto extraído e criar flag:

```text
ollama_unavailable
```

com estado `manual_review` ou `completed`, conforme a política definida no service e documentada.

---

## 12. Job Obrigatório

Criar:

```text
App\Jobs\ProcessDocumentAiJob
```

Requisitos:

```text
Implementar ShouldQueue.
Receber apenas o ID da análise, não o conteúdo do documento.
Recarregar DocumentAiAnalysis da base de dados.
Usar DocumentAiPipeline::process().
Definir tries/backoff conforme padrão do projeto.
Não serializar dados pessoais.
Não escrever conteúdo documental em logs do Laravel.
Emitir eventos através do service.
```

Exemplo de assinatura:

```php
public function __construct(public readonly int $documentAiAnalysisId) {}
```

---

## 13. Eventos Obrigatórios

Criar:

```text
DocumentAnalysisStarted
DocumentAnalysisCompleted
DocumentAnalysisFailed
```

Requisitos:

```text
Eventos devem transportar apenas ID da análise ou modelo sem conteúdo bruto sensível.
Não expor raw_text nem raw_ai_json.
Não enviar notificações externas nesta sprint.
Usar eventos para auditoria interna se o projeto seguir esse padrão.
```

---

## 14. Integração com Upload Existente

Identificar o ponto real onde documentos são submetidos.

Exemplos prováveis:

```text
StoreDocumentSubmissionRequest
ReplaceDocumentSubmissionRequest
Candidate document controller
DocumentSubmission model/service
```

Após a submissão documental ser gravada com sucesso:

```text
Criar DocumentAiAnalysis em estado pending.
Despachar ProcessDocumentAiJob.
Não bloquear o upload se a análise falhar.
Não alterar estado funcional do documento submetido.
Não alterar validações manuais existentes.
Não alterar permissões existentes.
```

Preferência:

```php
DB::afterCommit(fn () => ProcessDocumentAiJob::dispatch($analysis->id));
```

ou padrão equivalente existente no projeto.

Se a criação da análise falhar, o upload documental não deve ser perdido. Deve ser registado log/auditoria e o documento deve continuar disponível para validação manual.

---

## 15. Auditoria e RGPD

Auditar:

```text
Criação de análise pendente
Início de processamento
Conclusão de processamento
Falha de processamento
Marcação para revisão manual
Criação de flags relevantes
Acesso administrativo a resultados, se existir interface nesta sprint
```

Não auditar:

```text
Texto integral do documento
JSON bruto integral em mensagens de auditoria
Dados pessoais extraídos em mensagens de auditoria
```

Registar apenas:

```text
ID da análise
ID do documento
Estado anterior
Estado novo
Código técnico da ação
Utilizador responsável, quando existir
Timestamp
```

Se o projeto tiver enums de auditoria, criar ação própria seguindo o padrão existente.

Se não houver mecanismo de auditoria suficientemente claro, criar documentação da pendência e pelo menos `document_ai_processing_logs` minimizados.

---

## 16. Segurança

Regras obrigatórias:

```text
Documentos continuam em storage privado.
Não criar rota pública para raw_ai_json.
Não criar download direto por path.
Não expor raw_text ou raw_ai_json a candidatos nesta sprint.
Backoffice só deve consultar resultados se existir policy/permissão aplicável.
Não enviar dados para serviços pagos.
Não guardar credenciais.
Não usar dados reais em testes.
Não escrever conteúdo de documentos em laravel.log.
```

Se for criada alguma rota administrativa mínima, ela deve:

```text
usar auth;
usar policy/gate;
auditar acesso;
mostrar apenas metadados, estado, flags e campos minimizados;
não mostrar raw_json por defeito.
```

Interface visual não é obrigatória nesta sprint, salvo se o projeto já tiver padrão claro e a implementação for pequena.

---

## 17. Factories e Seeders

Criar factories:

```text
Database\Factories\DocumentAiAnalysisFactory
Database\Factories\DocumentAiFieldFactory
Database\Factories\DocumentAiFlagFactory
Database\Factories\DocumentAiProcessingLogFactory
```

Seeders são opcionais.

Se criar seeder demo, usar apenas dados fictícios:

```text
Documento fictício
Texto fictício
Campos fictícios
Flags fictícias
Sem NIF real
Sem IBAN real
Sem morada real completa
Sem nomes de candidatos reais
```

---

## 18. Testes Obrigatórios

Criar ou completar testes.

### 18.1 Unit

Criar:

```text
tests/Unit/DocumentIntelligence/DocumentAiPipelineTest.php
tests/Unit/DocumentIntelligence/DocumentAiStatusTest.php
```

Cobrir:

```text
Criação de análise pending.
Transição pending -> processing.
Transição processing -> completed.
Transição processing -> failed.
Transição processing -> manual_review quando ferramenta local indisponível.
Criação de processing logs minimizados.
Criação de fields.
Criação de flags.
raw_ai_json é guardado como JSON/cast.
```

### 18.2 Feature

Criar:

```text
tests/Feature/DocumentIntelligence/DocumentAiUploadIntegrationTest.php
```

Cobrir:

```text
Upload documental existente continua a funcionar.
Após upload, é criada uma análise pending.
Upload não falha se a queue estiver fake.
Upload não altera o estado funcional anterior do documento.
Documento privado continua privado.
```

### 18.3 Queue fake

Cobrir:

```text
ProcessDocumentAiJob é despachado quando documento é submetido.
Job recebe apenas ID da análise.
Job executa DocumentAiPipeline::process().
Falha controlada do job marca análise como failed ou manual_review.
```

Usar:

```php
Queue::fake();
Queue::assertPushed(ProcessDocumentAiJob::class);
```

### 18.4 Eventos

Cobrir:

```text
DocumentAnalysisStarted é emitido.
DocumentAnalysisCompleted é emitido.
DocumentAnalysisFailed é emitido quando há exceção controlada.
Eventos não transportam raw_text nem raw_ai_json.
```

Usar:

```php
Event::fake();
Event::assertDispatched(DocumentAnalysisStarted::class);
```

### 18.5 Auditoria

Cobrir, se existir módulo de auditoria:

```text
Criação de análise é auditada.
Início de processamento é auditado.
Conclusão é auditada.
Falha é auditada.
Auditoria não contém texto integral do documento.
Auditoria não contém JSON bruto da IA.
```

Se o mecanismo de auditoria não for claro, documentar a limitação no relatório de qualidade.

---

## 19. PHPStan e Tipagem

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
```

Em relações Eloquent, usar PHPDoc generics:

```php
/** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<DocumentAiAnalysis, DocumentAiField> */
public function analysis(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(DocumentAiAnalysis::class, 'document_ai_analysis_id');
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{engine: string|null, model: string|null, fields_count: int, flags_count: int} */
```

Não adicionar `mixed` sem necessidade.

Não silenciar erros com ignores genéricos.

---

## 20. Verificação PHPStan Antes de Publicar

Antes de considerar a sprint pronta para publicação, tentar executar PHPStan uma única vez usando `phpstan.neon`, se o ficheiro existir.

Executar apenas uma tentativa:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint27-before-publish.json
```

Se o comando falhar, não repetir automaticamente com outras configurações.

Depois gerar versão texto apenas se for possível sem nova análise pesada. Se não for possível, documentar que só foi gerado JSON.

Se `phpstan.neon` não existir, executar no máximo uma tentativa com a configuração padrão:

```bash
mkdir -p storage/phpstan
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint27-before-publish.json
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
Erros introduzidos em ficheiros novos/alterados pela Sprint 27
Erros bloqueantes
Erros não bloqueantes
```

---

## 21. Comandos Finais Obrigatórios

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
php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint27-before-publish.json
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

## 22. Documentação Obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-27-infraestrutura-analise-documental-ia.md
docs/document-intelligence/overview.md
docs/document-intelligence/local-ai-stack.md
docs/document-intelligence/security-and-gdpr.md
docs/qa/sprint-27-quality-report.md
docs/qa/test-coverage-matrix.md
docs/backlog/roadmap.md
```

### 22.1 docs/document-intelligence/overview.md

Incluir:

```text
Objetivo
Limites da Sprint 27
Modelos criados
Estados da análise
Pipeline
Queue
Eventos
Integração com documentos existentes
Limitações conhecidas
```

### 22.2 docs/document-intelligence/local-ai-stack.md

Incluir:

```text
Tesseract OCR
Poppler
ImageMagick
Ollama
Modelo recomendado: Gemma 3 4B ou Qwen 2.5 7B Instruct
Variáveis de configuração
Comportamento quando ferramentas locais não existem
Ausência de APIs pagas
```

### 22.3 docs/document-intelligence/security-and-gdpr.md

Incluir:

```text
Documentos privados
Dados pessoais
raw_text
raw_ai_json
Auditoria
Logs minimizados
Ausência de chamadas pagas/externas
Limites de acesso
Revisão manual
```

### 22.4 docs/qa/sprint-27-quality-report.md

Incluir:

```text
Comandos executados
Resultado das migrations
Resultado dos testes
Resultado do PHPStan antes de publicar
Erros legados identificados
Erros novos introduzidos: sim/não
Cobertura de queue fake
Cobertura de eventos
Cobertura de auditoria
Riscos RGPD
Riscos funcionais
Riscos técnicos
Recomendação de publicação
```

---

## 23. Critérios de Aceitação

A Sprint 27 está concluída quando:

```text
Upload documental existente continua a funcionar normalmente.
Sempre que um documento é submetido, é criada uma análise pending.
O processamento decorre em queue.
ProcessDocumentAiJob existe e é testado com Queue::fake.
DocumentAiPipeline existe e centraliza a lógica.
DocumentAiStatus existe e é usado.
Eventos DocumentAnalysisStarted, DocumentAnalysisCompleted e DocumentAnalysisFailed existem.
Toda a execução relevante fica auditada ou registada com limitação documentada.
JSON bruto produzido pela IA é armazenado.
Documentos privados continuam privados.
Não há chamadas para APIs pagas.
Ferramentas locais indisponíveis não quebram upload.
Falhas de análise ficam registadas e controladas.
Testes unitários foram criados.
Testes feature foram criados.
Testes de queue fake foram criados.
Testes de eventos foram criados.
Testes de auditoria foram criados quando o módulo existe.
PHPStan foi tentado antes de publicar, usando phpstan.neon uma única vez quando disponível.
php artisan route:list executa sem erro ou falha é documentada.
php artisan test executa sem erro ou falha é documentada.
php artisan migrate executa sem erro se houver migrations novas ou falha é documentada.
./vendor/bin/pint executa sem erro se existir ou alterações são documentadas.
Documentação foi criada/atualizada.
Não foram usados dados pessoais reais.
Não foram usadas credenciais.
Não foram implementadas funcionalidades fora de âmbito.
Não foram alteradas regras de elegibilidade, pontuação, validação ou decisão.
```

---

## 24. Fora de Âmbito

Não implementar nesta sprint:

```text
Decisão automática de elegibilidade.
Validação automática final de documentos.
Pontuação automática.
Reprovação automática de candidaturas.
Alteração de estados de candidatura.
Notificações externas por e-mail/SMS baseadas em IA.
Interface pública para resultados da IA.
APIs pagas.
Assinatura digital.
Integração bancária.
Treino de modelos.
Fine-tuning.
Reconhecimento facial.
Biometria.
Extração obrigatória de dados fiscais reais em produção.
```

---

## 25. Resposta Final Obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Confirmação de que a verificação de branch Git foi ignorada
4. Models criados ou alterados
5. Migrations criadas
6. Enum criado
7. Services criados ou alterados
8. Jobs criados ou alterados
9. Eventos criados ou alterados
10. Integração com upload documental existente
11. Estado do upload documental
12. Estado da criação de análise pending
13. Estado do processamento em queue
14. Estado do armazenamento de raw_ai_json
15. Estado da auditoria
16. Estado dos logs minimizados
17. Estado da integração com Tesseract/Poppler/ImageMagick/Ollama
18. Confirmação de ausência de APIs pagas
19. Testes Unit criados ou alterados
20. Testes Feature criados ou alterados
21. Testes Queue fake criados ou alterados
22. Testes Eventos criados ou alterados
23. Testes Auditoria criados ou alterados
24. Resultado de php artisan route:list
25. Resultado de php artisan migrate, se aplicável
26. Resultado de php artisan test
27. Resultado de ./vendor/bin/pint, se aplicável
28. Resultado de npm run build, se aplicável
29. Resultado PHPStan antes de publicar
30. Confirmação de que phpstan.neon foi tentado uma única vez, se existia
31. Erros PHPStan legados considerados
32. Novos erros PHPStan introduzidos pela Sprint 27: sim/não
33. Documentação criada ou atualizada
34. Riscos RGPD ainda existentes
35. Riscos técnicos ainda existentes
36. Pendências técnicas
37. Confirmação de que não foram usados dados pessoais reais
38. Confirmação de que não foram usadas credenciais
39. Confirmação de que não foram implementadas funcionalidades fora de âmbito
40. Recomendação objetiva para publicar ou não publicar
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

## 26. Definition of Done

A Sprint 27 só está concluída quando existir uma infraestrutura base de análise documental por IA, integrada com os documentos privados existentes, com análise pendente criada no upload, processamento em queue, eventos, auditoria/logs minimizados, armazenamento do JSON bruto, testes unitários/feature/queue/eventos/auditoria, documentação e verificação PHPStan antes de publicação, sem recurso a APIs pagas e sem alteração de regras funcionais de candidatura, elegibilidade, pontuação ou decisão.

---

## 27. Execução Imediata

Executa agora apenas:

```text
Sprint 27 — Infraestrutura Base de Análise Documental por IA
```

Fim da master prompt da Sprint 27.
