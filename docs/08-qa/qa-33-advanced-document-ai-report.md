# QA-33 — IA Documental Avançada

## 1. Sumário executivo

A QA-33 reforçou a infraestrutura existente de Document Intelligence sem criar um módulo paralelo e sem introduzir decisão automática por IA.

O trabalho validou e reforçou:

- OCR/fallback controlado;
- validação assistiva documental;
- score documental indicativo;
- sugestões para o técnico com justificação obrigatória;
- auditoria dos eventos assistivos;
- integração segura com Work Tasks;
- proteção RGPD e minimização de dados.

A IA continua limitada a leitura, estruturação, comparação, sinalização e sugestão. Não altera candidatura, elegibilidade, scoring, ranking, listas, contratos, rendas ou qualquer decisão administrativa.

Decisão final: **PASS**

## 2. Dependências QA-30/QA-31/QA-32

Dependências validadas na base da branch:

- QA-30: roles, perfis, equipas, MFA e auditoria de acessos existem e foram preservados.
- QA-31: Work Tasks, SLA, histórico e caixa de trabalho existem e foram reutilizados.
- QA-32: auditoria sensível, RGPD, sessões, exports e hardening transversal existem e foram preservados.

A integração QA-33 usa os serviços existentes em vez de criar arquitetura paralela.

## 3. Inventário Document Intelligence existente

Ficheiros e módulos analisados:

- `app/Models/DocumentAiAnalysis.php`
- `app/Models/DocumentAiField.php`
- `app/Models/DocumentAiFlag.php`
- `app/Models/DocumentAiProcessingLog.php`
- `app/Models/DocumentAiValidationRun.php`
- `app/Models/DocumentAiValidation.php`
- `app/Models/DocumentAiScore.php`
- `app/Models/DocumentAiSuggestion.php`
- `app/Jobs/ProcessDocumentAiJob.php`
- `app/Jobs/ValidateDocumentAiAgainstApplicationJob.php`
- `app/Jobs/CalculateDocumentAiScoreJob.php`
- `app/Services/DocumentIntelligence/DocumentAiPipeline.php`
- `app/Services/DocumentIntelligence/DocumentOcrExtractor.php`
- `app/Services/DocumentIntelligence/DocumentClassificationPipeline.php`
- `app/Services/DocumentIntelligence/DocumentFieldExtractionPipeline.php`
- `app/Services/DocumentIntelligence/DocumentCandidateValidationPipeline.php`
- `app/Services/DocumentIntelligence/DocumentRiskFlagDetector.php`
- `app/Services/DocumentIntelligence/DocumentAiScoreCalculator.php`
- `app/Services/DocumentIntelligence/DocumentSuggestionGenerator.php`
- `app/Services/DocumentIntelligence/DocumentAiAssistantPipeline.php`
- `app/Services/DocumentIntelligence/DocumentAiAssistantPersister.php`
- `app/Policies/DocumentAiAnalysisPolicy.php`
- `app/Policies/DocumentAiAssistantPolicy.php`
- `app/Policies/DocumentAiSuggestionPolicy.php`
- `resources/views/backoffice/document-ai/assistant/_suggestions.blade.php`
- `routes/web.php`
- `tests/Feature/DocumentIntelligence/*`
- `tests/Unit/DocumentIntelligence/*`

Documentação lida:

- `AGENTS.md`
- `docs/02-arquitetura/domain-boundaries.md`
- `docs/09-seguranca-rgpd/security-rgpd-guardrails.md`
- `docs/08-qa/query-and-export-guardrails.md`
- `docs/08-qa/enterprise-quality-gate.md`
- `docs/08-qa/critical-flows-test-map.md`
- `docs/08-qa/pre-release-checklist.md`
- `docs/08-qa/qa-30-user-role-competency-management-report.md`
- `docs/08-qa/qa-31-work-task-competency-workflow-report.md`
- `docs/08-qa/qa-32-security-rgpd-hardening-report.md`

Documento não encontrado:

- `docs/08-qa/deep-research-report.md`

## 4. OCR

Validação efetuada:

- PDF/texto pesquisável ou ficheiro com texto extraível continua suportado pelo pipeline existente.
- Ficheiro sintético válido gera OCR/texto através do extractor local/fallback.
- OCR indisponível falha de forma controlada e devolve estado técnico sem bloquear upload documental.
- Texto OCR não é escrito em logs de teste.

Testes reforçados:

- `tests/Feature/DocumentIntelligence/AdvancedOcrValidationTest.php`

## 5. Validação assistiva

Foram reforçados os eventos de início e conclusão de validação assistiva nos pipelines existentes:

- `document_ai_validation_started`
- `document_ai_validation_completed`

A validação deteta sinais de risco sem decidir:

- documento expirado;
- NIF inválido ou divergente;
- duplicação suspeita;
- campos em falta;
- divergência entre dados extraídos e dados declarados.

Estados administrativos proibidos para IA continuam fora do pipeline. A IA não aprova, rejeita, exclui, torna elegível nem torna inelegível.

## 6. Score documental

Foi criado `DocumentAiRiskScoringService` para traduzir o score existente em semáforo assistivo:

- `green`: documento legível, tipo provável correto e sem incoerências relevantes;
- `yellow`: baixa confiança, campos incompletos ou divergência menor;
- `red`: risco crítico, documento ilegível, expirado, incoerente ou divergência relevante.

O score é indicativo e não altera qualquer decisão administrativa.

Testes:

- `tests/Feature/DocumentIntelligence/DocumentAiRiskScoreTest.php`
- `tests/Unit/DocumentIntelligence/DocumentAiScoreServiceTest.php`

## 7. Assistente técnico

Foram reforçadas sugestões para revisão humana:

- criação de sugestão auditada com `document_ai_suggestion_created`;
- aceitação exige justificação técnica;
- rejeição/ignorar exige justificação técnica;
- justificação fica na metadata da sugestão, sem guardar dados pessoais desnecessários;
- aceitar uma sugestão não altera automaticamente candidatura, elegibilidade, classificação ou decisão.

Ficheiros alterados:

- `app/Http/Requests/Backoffice/AcceptDocumentAiSuggestionRequest.php`
- `app/Http/Requests/Backoffice/DismissDocumentAiSuggestionRequest.php`
- `app/Http/Controllers/Backoffice/DocumentAiAssistantController.php`
- `resources/views/backoffice/document-ai/assistant/_suggestions.blade.php`

Testes:

- `tests/Feature/DocumentIntelligence/DocumentAiAssistantSuggestionTest.php`
- `tests/Feature/Backoffice/DocumentAiAssistantDashboardTest.php`

## 8. Integração Work Tasks

Foi criado `DocumentAiWorkTaskService` para criar tarefa de revisão documental quando o score ou flags exigem revisão humana.

Regras validadas:

- criação via `WorkTaskCreationService`;
- idempotência herdada do serviço de Work Tasks;
- tipo `document_review`;
- origem técnica `document_ai_risk:analysis:{id}`;
- entidade relacionada: análise IA documental;
- prioridade baseada em severidade;
- metadata minimizada.

Metadata permitida na tarefa:

- identificadores técnicos da análise e score;
- score numérico;
- cor assistiva;
- flag codes;
- contagem de flags;
- indicação de revisão manual.

Metadata excluída:

- documentos;
- NIF em claro;
- morada em claro;
- rendimentos em claro;
- conteúdo OCR bruto.

Ficheiro criado:

- `app/Services/DocumentIntelligence/DocumentAiWorkTaskService.php`

Teste:

- `tests/Feature/QA33AdvancedDocumentAiTest.php`

## 9. Segurança/RGPD

Validações efetuadas:

- candidato não acede à análise técnica interna;
- técnico sem permissão não acede por policy;
- auditoria não contém conteúdo bruto do documento;
- view do assistente não expõe caminho interno nem payload bruto;
- Work Tasks recebem apenas metadata minimizada;
- OCR indisponível não quebra submissão documental;
- nenhum documento real foi adicionado ao repositório;
- nenhum serviço externo foi chamado.

Teste:

- `tests/Feature/DocumentIntelligence/DocumentAiSecurityRgpdTest.php`

## 10. Auditoria

Eventos reforçados:

- `document_ai_validation_started`
- `document_ai_validation_completed`
- `document_ai_score_calculated`
- `document_ai_suggestion_created`
- `document_ai_suggestion_accepted`
- `document_ai_suggestion_dismissed`
- `document_ai_manual_review_required`

Eventos existentes preservados:

- `document_ai_score_calculation_started`
- `document_ai_risk_flag_detected`
- `document_ai_suggestion_generated`
- `work_task_created`

Não foram registados passwords, tokens, documentos completos, conteúdo OCR bruto ou caminhos internos privados.

## 11. Backoffice

O backoffice existente foi reforçado sem redesign amplo.

Rotas existentes relacionadas:

- `/backoffice/documentos/ia/classificacoes`
- `/backoffice/documentos/ia/extracoes`
- `/backoffice/documentos/ia/assistente`
- `/backoffice/documentos/ia/validacoes`

Alteração de UX:

- as ações de aceitar e ignorar sugestão IA exigem justificação textual curta;
- mantém-se a convenção visual Blade/Tailwind existente.

## 12. Testes executados

Comandos executados e registados:

- `composer validate --strict`
- `php artisan optimize:clear`
- `./vendor/bin/pint --test`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA33`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DocumentAi`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter DocumentIntelligence`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Security`
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rgpd`
- `php artisan route:list --except-vendor`
- `./vendor/bin/phpstan analyse --memory-limit=1G -v`
- `npm run build`
- `git diff --check`

Resultados:

- Composer: PASS
- Optimize clear: PASS
- Pint: PASS
- QA33: PASS, 1 teste, 14 assertions
- DocumentAi: PASS, 34 testes, 193 assertions
- DocumentIntelligence: PASS, 62 testes, 310 assertions
- Security: PASS, 38 testes, 236 assertions
- Rgpd: PASS, 12 testes, 82 assertions
- Route list: PASS, 1114 rotas
- PHPStan: PASS, 0 erros
- Build: PASS
- Diff check: PASS

## 13. Evidências

Artefactos locais criados:

- `storage/qa/qa-33-composer.txt`
- `storage/qa/qa-33-optimize-clear.txt`
- `storage/qa/qa-33-pint.txt`
- `storage/qa/qa-33-qa33-tests.txt`
- `storage/qa/qa-33-document-ai-tests.txt`
- `storage/qa/qa-33-document-intelligence-tests.txt`
- `storage/qa/qa-33-security-tests.txt`
- `storage/qa/qa-33-rgpd-tests.txt`
- `storage/qa/qa-33-route-list.txt`
- `storage/qa/qa-33-phpstan.txt`
- `storage/qa/qa-33-build.txt`
- `storage/qa/qa-33-diff-check.txt`

## 14. Riscos residuais

- OCR depende de disponibilidade local do motor configurado; quando indisponível, o comportamento validado é fallback controlado para revisão manual.
- IA local opcional não foi assumida como disponível.
- A precisão documental continua dependente da qualidade do documento submetido.
- O score `green/yellow/red` é assistivo e exige validação humana nos casos sinalizados.
- `docs/08-qa/deep-research-report.md` não existe na base atual.

## 15. Decisão final

**PASS**

A QA-33 cumpre os critérios de aceitação:

- OCR funciona ou falha controladamente;
- documentos inválidos são sinalizados;
- score documental assistivo existe;
- sugestões exigem validação humana;
- IA não decide;
- dados sensíveis permanecem protegidos;
- Work Tasks recebem metadata minimizada;
- auditoria está reforçada;
- PHPUnit, Pint, PHPStan, build, route:list e diff-check passaram.
