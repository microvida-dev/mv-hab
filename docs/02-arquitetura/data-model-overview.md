# Modelo de dados alvo

## Atualização Sprint 26 — Pós-atribuição

A Sprint 26 acrescenta uma camada pós-atribuição sem substituir contratos, financeiro, manutenção e vistorias existentes.

Novas entidades: `tenant_profiles`, `tenant_contract_accesses`, `tenant_invoices`, `tenant_payments`, `tenant_charge_runs`, `tenant_charge_run_items`, `tenant_communications`, `tenant_communication_messages` e `landlord_dashboard_snapshots`.

Entidades reutilizadas: `contracts`, `tenant_financial_accounts`, `maintenance_requests`, `maintenance_interventions`, `maintenance_attachments`, `property_inspections` e `property_inspection_reports`.

Não foram criadas entidades duplicadas para manutenção ou vistorias, porque o modelo existente já suporta contrato, habitação, inquilino, visibilidade e storage privado.

Este documento descreve o modelo alvo. As entidades de fundação e da Sprint 3 indicadas abaixo já possuem migrations e models; as restantes continuam previstas.

## Estado implementado na Sprint 3

| Entidade | Estado | Notas |
| --- | --- | --- |
| `programs` | Implementada | Slug público, estado formal, período, publicação, autoria e soft delete. |
| `program_rules` | Implementada | Regras ordenadas e períodos de vigência; versionamento histórico completo continua pendente. |
| `contests` | Implementada | Código e slug únicos, estado formal, janela de candidatura, publicação, autoria e soft delete. |
| `contest_deadlines` | Implementada | Tipo formal, intervalo, descrição e ordenação. |
| `contest_jury_members` | Implementada | Associação única entre concurso e utilizador com função no júri. |

As relações com candidaturas, documentos, habitações e decisões não foram criadas nesta sprint.

## Estado implementado na Sprint 4

| Entidade | Estado | Notas |
| --- | --- | --- |
| `adhesion_registrations` | Implementada | Um registo por utilizador, estados formais, dados base, preferências, timestamps de aceitação e soft delete. |
| `adhesion_registration_status_histories` | Implementada | Transição anterior/seguinte, autor, motivo opcional e timestamp. |

Decisões:

- O `User` tem uma relação `hasOne` com inclusão de soft deletes para preservar o estado `removed`.
- `user_id`, `status` e timestamps de estado não são mass assignable.
- O model legado `Citizen` não foi reutilizado para evitar duplicação de ownership e mistura entre CRM interno e identidade do candidato.
- O model legado `HousingApplication` não foi associado artificialmente ao registo; a relação será criada no modelo processual da Sprint 8.

## Estado implementado na Sprint 5

| Entidade | Estado | Notas |
| --- | --- | --- |
| `households` | Adaptada | Mantém `citizen_id` para o CRM legado e adiciona `adhesion_registration_id`, tipo e soft delete. A FK de adesão é anulável apenas para preservar registos legados; novos agregados candidatos exigem-na no service. |
| `household_members` | Implementada | Ownership redundante por agregado/registo, requerente, relação, situação profissional, nível de qualificação, dependência, incapacidade/multideficiência, gravidez, dispensa de IRS e resumos de rendimento. |
| `income_sources` | Implementada | 11 fontes de referência idempotentes, configuráveis e ordenadas. |
| `income_records` | Implementada | Valores mensal/anual normalizados, período, fonte, ownership e soft delete. |
| `current_housing_situations` | Implementada | Uma situação por adesão, encargos, residência/trabalho e indicadores habitacionais sensíveis. |

Decisões:

- Não foi criada uma segunda entidade de agregado; o model legado foi evoluído com compatibilidade.
- `household_id` e `adhesion_registration_id` não são mass assignable nas entidades do candidato.
- Os montantes agregados são recalculados pelos services após cada alteração.
- Os indicadores apresentados são declarativos e não constituem decisão de elegibilidade.
- Dados sensíveis não foram disponibilizados em listagens públicas ou administrativas específicas.

## Estado implementado na Sprint 6

| Entidade | Estado | Notas |
| --- | --- | --- |
| `document_types` | Implementada | Código único, categoria, entidade alvo, sensibilidade, formatos permitidos, tamanho máximo, validade, retenção prevista, ativo/inativo e soft delete. |
| `required_documents` | Implementada | Regra configurável por tipo documental, programa, concurso, entidade alvo, condição, obrigatoriedade, ordem e ativo/inativo. |
| `document_submissions` | Implementada | Documento lógico por registo, utilizador, tipo, regra, alvo e estado atual; inclui referência à versão atual sem expor storage path. |
| `document_versions` | Implementada | Histórico de ficheiros, path privado, nome original, MIME, tamanho, checksum, metadados e autor da submissão. |
| `document_reviews` | Implementada | Decisão administrativa, motivo público, notas internas, técnico revisor e data de revisão. |
| `document_access_logs` | Implementada | Registo de download, visualização/revisão lógica, upload, substituição e alteração de estado, com ator, IP, user agent e contexto. |

Decisões:

- O model legado `Document` e a tabela `documents` foram preservados para compatibilidade com o CRM existente; a gestão processual usa as novas entidades `DocumentSubmission` e relacionadas.
- Os ficheiros são guardados no disk `local`, configurado como storage privado em `storage/app/private`.
- `application_id` em `document_submissions` fica preparado para a Sprint 8, sem FK para evitar acoplamento antes de a entidade `applications` processual existir.
- `current_version_id` é referência lógica para evitar FK circular entre submissão e versão.
- `user_id`, `adhesion_registration_id`, `status`, `storage_path`, `checksum` e campos críticos são atribuídos pelos services, não por mass assignment do formulário.
- A checklist é calculada em service e não grava linhas `missing`; apenas submissões reais persistem.

## Estado implementado na Sprint 8

| Entidade | Estado | Notas |
| --- | --- | --- |
| `applications` | Implementada | Candidatura processual separada do CRM legado, com UUID público, número único, ownership, estados, declarações resumidas, timestamps e soft delete. |
| `application_status_histories` | Implementada | Transições, ator, motivo e timestamps. |
| `application_snapshots` | Implementada | Sete snapshots tipados e únicos por candidatura, sem paths internos de storage. |
| `application_documents` | Implementada | Documentos associados e respetivo estado no momento da submissão. |
| `application_declarations` | Implementada | Cinco aceitações versionadas com timestamp. |
| `application_preferences` | Estrutura preparada | Tabela e model existem, mas não há UI enquanto o concurso não tiver fogos associados de forma processual. |

Decisões:

- O model legado `HousingApplication` e a tabela `housing_applications` foram preservados. A candidatura processual usa `Application`/`applications`, porque o legado pertence a `Citizen` e não suporta concurso, snapshots, declarações ou ownership do candidato autenticado.
- `document_submissions.application_id` recebeu a FK prevista na Sprint 6.
- A submissão não decide elegibilidade; a Sprint 7 passou a fornecer checks separados e auditáveis.
- O comprovativo é HTML/impressão, sem nova dependência PDF.
- O isolamento candidato/backoffice é aplicado por middleware, permissions e policies.

## Estado implementado na Sprint 7

| Entidade | Estado | Notas |
| --- | --- | --- |
| `eligibility_rule_sets` | Implementada | Associação a programa/concurso, estados, vigência, regra padrão, autoria e soft delete. |
| `eligibility_criteria` | Implementada | Código único por rule set, categoria, alvo, operador, limites, mensagens, obrigatoriedade, análise manual, ordem e ativo/inativo. |
| `eligibility_checks` | Implementada | Contexto, tipo, estado, resultado global, dados em falta, alertas, executor, timestamp e soft delete. |
| `eligibility_check_results` | Implementada | Snapshot lógico do critério, valor agregado seguro, resultado e mensagens simples/técnicas. |
| `eligibility_snapshots` | Implementada | Oito snapshots mínimos por execução, sem ficheiros, paths internos ou identificadores pessoais diretos. |

Decisões:

- `eligibility_rule_set_id` é anulável no check para registar `not_applicable` quando não existem regras ativas.
- Rule sets usados por checks não são eliminados; o fluxo privilegia arquivo e duplicação.
- O check formal referencia `Application`, mas não altera o seu estado.
- Resultado e estado do check são protegidos contra mass assignment e escritos pelo motor transacional.
- O model legado `HousingApplication` permanece separado.

## Estado implementado na Sprint 11

| Entidade | Estado | Notas |
| --- | --- | --- |
| `provisional_lists` | Implementada | Lista provisória por snapshot de ranking, programa e concurso; inclui estado, versão, modo de anonimização, prazos de reclamação, publicação e auditoria. |
| `provisional_list_entries` | Implementada | Linhas classificadas, excluídas ou condicionais, com posição, pontuação, identificador público e motivo público/técnico. |
| `definitive_lists` | Implementada | Lista definitiva derivada de lista provisória, com versão, estado e prontidão para atribuição futura. |
| `definitive_list_entries` | Implementada | Linhas finais preservando posição original, posição final, tipo de alteração e referência à entrada provisória. |
| `list_publications` | Implementada | Publicação versionada por canal e tipo, com payload anonimizado, janela de visibilidade e estado. |
| `complaints` | Implementada | Reclamação própria do candidato associada à lista provisória, entrada, candidatura e prazo ativo. |
| `complaint_attachments` | Implementada | Referências a documentos já submetidos; não duplica ficheiros nem paths. |
| `complaint_reviews` | Implementada | Análise administrativa com resultado, notas e técnico responsável. |
| `complaint_decisions` | Implementada | Decisão fundamentada, proposta/aprovada, com efeitos registáveis sobre lista definitiva. |
| `additional_information_requests` | Implementada | Pedido complementar no contexto de reclamação, com prazo configurável e visibilidade ao candidato. |
| `additional_information_responses` | Implementada | Resposta do candidato com texto e documento opcional já existente. |
| `hearings` | Implementada | Audiência de interessados associada a candidatura/lista/reclamação, com prazo e estados formais. |
| `hearing_submissions` | Implementada | Pronúncia do candidato e decisão de revisão administrativa. |
| `official_notifications` | Implementada | Registo interno/in-app de notificação oficial; não envia email/SMS real. |
| `list_change_logs` | Implementada | Histórico dos efeitos de reclamação/audiência e alterações refletidas na lista definitiva. |

Decisões:

- `RankingSnapshot` continua interno; a publicação pública passa sempre por lista e publicação própria.
- Identificadores públicos são pseudonimizados e diferentes de IDs internos ou números formais de candidatura.
- Reclamações e audiências não recalculam ranking automaticamente; registam efeitos e geram lista definitiva auditável.
- Notificações oficiais são persistidas como registo processual interno, deixando templates, canais externos e prova de envio para Sprint 16.

Prioridades:

- P0: fundacao ou requisito critico do ciclo processual.
- P1: necessario para operacao completa.
- P2: melhoria, automatizacao ou consolidacao avancada.

| Entidade | Objetivo | Campos principais previstos | Relacoes principais | Sprint prevista | Prioridade | Notas tecnicas |
| --- | --- | --- | --- | --- | --- | --- |
| `municipalities` | Representar municipio ou entidade gestora. | `id`, `name`, `tax_number`, `code`, `contact_email`, `settings`, `active` | tem `users`, `programs`, `housing_units` | Sprint 1 | P0 | Preparar multi-municipio sem obrigar multi-tenant completo na primeira fase. |
| `users` | Autenticar operadores, candidatos e auditores. | `id`, `municipality_id`, `name`, `email`, `password`, `email_verified_at`, `status`, `last_login_at` | pertence a `municipalities`; tem roles; liga a `candidate_profiles` | Sprint 1 | P0 | Estender modelo atual sem quebrar Breeze. |
| `roles` | Agrupar responsabilidades de acesso. | `id`, `name`, `label`, `scope`, `is_system` | muitos-para-muitos com `users`; tem `permissions` | Sprint 1 | P0 | Preferir roles de sistema versionadas. |
| `permissions` | Definir acoes autorizadas por modulo. | `id`, `module`, `action`, `description` | muitos-para-muitos com `roles` | Sprint 1 | P0 | Acoes normalizadas: `view`, `create`, `update`, `delete`, `approve`, `reject`, `publish`, `export`, `audit`. |
| `audit_logs` | Registar acessos, alteracoes, decisoes e exportacoes. | `id`, `user_id`, `event`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `occurred_at` | pertence a `users`; polimorfico para entidades auditadas | Sprint 1 | P0 | Imutabilidade logica; sem guardar segredos. |
| `programs` | Representar programa municipal de arrendamento. | `id`, `municipality_id`, `name`, `description`, `legal_basis`, `status`, `starts_at`, `ends_at` | pertence a `municipalities`; tem `program_rules`, `contests` | Sprint 3 | P0 | Separar programa permanente de concursos especificos. |
| `program_rules` | Guardar regras gerais do programa. | `id`, `program_id`, `rule_type`, `value`, `description`, `effective_from`, `effective_until` | pertence a `programs` | Sprint 3 | P0 | Versionar regras para auditabilidade. |
| `contests` | Representar concurso/aviso de abertura. | `id`, `program_id`, `code`, `title`, `status`, `opens_at`, `closes_at`, `published_at` | pertence a `programs`; tem prazos, juri e candidaturas | Sprint 3 | P0 | Estados de publicacao e encerramento devem ser formais. |
| `contest_deadlines` | Controlar prazos do concurso. | `id`, `contest_id`, `type`, `starts_at`, `ends_at`, `description` | pertence a `contests` | Sprint 3 | P0 | Usado para submissao, reclamacao, audiencia e recursos. |
| `contest_jury_members` | Associar membros do juri ao concurso. | `id`, `contest_id`, `user_id`, `role_in_jury`, `appointed_at` | pertence a `contests` e `users` | Sprint 3 | P1 | Permitir declaracao futura de impedimentos. |
| `candidate_profiles` | Perfil unico do candidato. | `id`, `user_id`, `municipality_id`, `document_number`, `birth_date`, `phone`, `address`, `status` | pertence a `users`; tem agregados, adesoes e candidaturas | Sprint 4 | P0 | Dados pessoais sujeitos a minimizacao e retencao. |
| `adhesion_registrations` | Registar adesao inicial ao programa. | `id`, `candidate_profile_id`, `program_id`, `status`, `submitted_at`, `validated_at`, `rejected_reason` | pertence a `candidate_profiles` e `programs` | Sprint 4 | P0 | Deve suportar pre-validacao sem candidatura formal. |
| `households` | Representar agregado familiar. | `id`, `candidate_profile_id`, `name`, `members_count`, `declared_monthly_income`, `status` | pertence a `candidate_profiles`; tem membros e rendimentos | Sprint 5 | P0 | Evoluir o modelo atual para historico e composicao detalhada. |
| `household_members` | Detalhar membros do agregado. | `id`, `household_id`, `name`, `relationship`, `birth_date`, `document_number`, `qualification_level`, `dependent`, `disability_status`, `has_multiple_disabilities`, `is_pregnant`, `is_exempt_from_irs` | pertence a `households` | Sprint 5 | P0 | Qualificação suporta a matriz de classificação; deficiência, multideficiência e gravidez são dados sensíveis e devem ser justificados, minimizados e sujeitos a acesso restrito. |
| `income_records` | Registar rendimentos declarados/comprovados. | `id`, `household_id`, `member_id`, `source_id`, `amount`, `period_start`, `period_end`, `verified_at` | pertence a agregado, membro e fonte | Sprint 5 | P0 | Guardar periodo e origem para recalculo auditavel. |
| `income_sources` | Normalizar tipos de rendimento. | `id`, `name`, `code`, `description`, `active` | usado por `income_records` | Sprint 5 | P1 | Tabela configuravel por regras municipais/legais. |
| `current_housing_situations` | Registar situacao habitacional atual. | `id`, `candidate_profile_id`, `type`, `address`, `monthly_cost`, `risk_flags`, `notes` | pertence a `candidate_profiles` | Sprint 5 | P0 | Pode conter dados vulneraveis; acesso restrito. |
| `applications` | Candidatura formal a concurso. | `id`, `contest_id`, `candidate_profile_id`, `household_id`, `status`, `submitted_at`, `locked_at` | pertence a concurso, candidato e agregado | Sprint 8 | P0 | Estados devem ser maquina formal, nao texto livre. |
| `application_status_history` | Historico de estados da candidatura. | `id`, `application_id`, `from_status`, `to_status`, `changed_by`, `reason`, `changed_at` | pertence a `applications` e `users` | Sprint 8 | P0 | Obrigatorio para auditabilidade processual. |
| `application_preferences` | Preferencias do candidato. | `id`, `application_id`, `preference_type`, `value`, `priority` | pertence a `applications` | Sprint 8 | P1 | Evitar prometer preferencias que nao sejam vinculativas. |
| `document_types` | Catalogar tipos documentais. | `id`, `code`, `name`, `category`, `applies_to`, `sensitivity`, `allowed_mime_types`, `max_size_kb`, `validity_months`, `retention_months`, `is_active` | usado por documentos obrigatorios e submissões | Sprint 6 | P0 | Implementado; sensibilidade orienta acesso e retencao. |
| `required_documents` | Definir documentos exigidos por fase/regra. | `id`, `program_id`, `contest_id`, `document_type_id`, `required_for`, `condition_key`, `condition_operator`, `condition_value`, `is_required`, `is_active` | pertence opcionalmente a programa/concurso e sempre a tipo documental | Sprint 6 | P0 | Implementado; suporta obrigatoriedade condicional declarativa. |
| `document_submissions` | Guardar documentos submetidos. | `id`, `application_id`, `adhesion_registration_id`, `user_id`, `document_type_id`, `required_document_id`, `target_type`, `target_id`, `status`, `submitted_at`, `validated_at`, `rejected_at`, `expires_at` | pertence a adesão, tipo, regra, utilizador e alvo documental | Sprint 6 | P0 | Implementado; ficheiros ficam em versões privadas. |
| `document_versions` | Guardar histórico físico dos ficheiros. | `id`, `document_submission_id`, `version_number`, `storage_disk`, `storage_path`, `original_filename`, `mime_type`, `size_bytes`, `checksum`, `uploaded_by` | pertence a submissão documental | Sprint 6 | P0 | Implementado; path privado nunca é apresentado ao candidato. |
| `document_reviews` | Registar validação documental. | `id`, `document_submission_id`, `reviewed_by`, `decision`, `reason`, `internal_notes`, `reviewed_at` | pertence a documento e técnico | Sprint 6 | P0 | Implementado; motivo público separado de notas internas. |
| `document_access_logs` | Registar acessos e eventos documentais. | `id`, `document_submission_id`, `document_version_id`, `user_id`, `action`, `ip_address`, `user_agent`, `context`, `accessed_at` | pertence a submissão, versão e utilizador | Sprint 6 | P0 | Implementado; base para auditoria documental e RGPD. |
| `eligibility_checks` | Executar verificacao de elegibilidade. | `id`, `eligibility_rule_set_id`, `program_id`, `contest_id`, `application_id`, `adhesion_registration_id`, `user_id`, `check_type`, `status`, `result`, `missing_data`, `warnings`, `executed_by`, `executed_at` | pertence ao contexto avaliado; tem resultados e snapshots | Sprint 7 | P0 | Implementado; cada reexecução cria novo check. |
| `eligibility_check_results` | Detalhar resultado por regra. | `id`, `eligibility_check_id`, `eligibility_criterion_id`, `code`, `category`, `result`, `actual_value`, `expected_value`, `message`, `technical_message` | pertence a check e critério | Sprint 7 | P0 | Implementado; mensagem técnica restrita ao backoffice. |
| `administrative_processes` | Representar o processo administrativo associado à candidatura submetida. | `id`, `process_number`, `application_id`, `program_id`, `contest_id`, `user_id`, `status`, `assigned_to`, datas de análise/decisão, `current_correction_request_id`, `summary`, `internal_notes` | pertence a candidatura, programa, concurso, candidato e técnico; tem histórico, análises, pedidos, respostas, decisões, tarefas e notas | Sprint 9 | P0 | Implementado; `process_number` é chave de rota, `status` e ownership são escritos por services. |
| `administrative_process_status_histories` | Preservar mudanças de estado processual. | `id`, `administrative_process_id`, `from_status`, `to_status`, `changed_by`, `reason`, `created_at` | pertence a processo e utilizador | Sprint 9 | P0 | Implementado; criado por transition service. |
| `application_reviews` | Registar ciclos de análise administrativa. | `id`, `administrative_process_id`, `application_id`, `review_type`, `status`, `reviewed_by`, `result`, `summary`, `internal_notes` | pertence a processo/candidatura; tem itens | Sprint 9 | P0 | Implementado; itens técnicos não são expostos ao candidato. |
| `application_review_items` | Detalhar pontos analisados. | `id`, `application_review_id`, `code`, `name`, `category`, `target_type`, `target_id`, `result`, `message`, `technical_message`, `requires_correction` | pertence a análise; pode referenciar alvo polimórfico | Sprint 9 | P1 | Implementado para análise manual inicial. |
| `correction_requests` | Registar pedidos de aperfeiçoamento. | `id`, `administrative_process_id`, `application_id`, `user_id`, `request_number`, `status`, `subject`, `message`, `instructions`, `response_deadline_at`, `candidate_visible` | pertence a processo/candidatura/candidato; tem itens e respostas | Sprint 9 | P0 | Implementado; não envia email/SMS real. |
| `correction_request_items` | Registar cada elemento a aperfeiçoar. | `id`, `correction_request_id`, `issue_type`, `title`, `description`, `required_action`, `status`, `is_required`, `document_type_id`, `required_document_id` | pertence a pedido; pode apontar para tipo/regra documental | Sprint 9 | P0 | Implementado; documentos continuam no sistema documental. |
| `correction_responses` | Guardar resposta do candidato ao aperfeiçoamento. | `id`, `correction_request_id`, `correction_request_item_id`, `application_id`, `user_id`, `response_text`, `document_submission_id`, `status`, `review_result` | pertence a pedido, item, candidatura, candidato e documento opcional | Sprint 9 | P0 | Implementado; não guarda ficheiros nem paths. |
| `administrative_decisions` | Registar proposta/aprovação de admissão ou não admissão. | `id`, `administrative_process_id`, `application_id`, `decision_type`, `decision_result`, `status`, `summary`, `grounds`, `decided_by`, `approved_by` | pertence a processo/candidatura e utilizadores decisores | Sprint 9 | P0 | Implementado; decisão aprovada altera apenas estado administrativo. |
| `administrative_tasks` | Gerir tarefas internas do processo. | `id`, `administrative_process_id`, `application_id`, `title`, `description`, `status`, `priority`, `assigned_to`, `due_at` | pertence a processo/candidatura e técnico | Sprint 9 | P1 | Implementado para fila operacional. |
| `administrative_process_notes` | Registar notas internas/cautelosamente visíveis. | `id`, `administrative_process_id`, `application_id`, `user_id`, `visibility`, `note_type`, `body` | pertence a processo/candidatura/utilizador | Sprint 9 | P1 | Implementado; notas internas não aparecem ao candidato. |
| `administrative_workflow_configs` | Configurar prazos e regras simples por programa/concurso. | `id`, `program_id`, `contest_id`, `name`, `is_active`, `default_correction_deadline_days`, `requires_decision_approval` | pertence opcionalmente a programa/concurso | Sprint 9 | P1 | Implementado; fallback de 10 dias permanece pendente de validação jurídica. |
| `scoring_rule_sets` | Versionar matrizes de classificação. | `id`, `program_id`, `contest_id`, `name`, `status`, `is_default`, `starts_at`, `ends_at`, `created_by`, `updated_by` | pertence opcionalmente a programa/concurso; tem critérios, desempates e execuções | Sprint 10 | P0 | Implementado; matriz ativa do concurso prevalece sobre matriz do programa. |
| `scoring_criteria` | Definir critérios de classificação. | `id`, `scoring_rule_set_id`, `code`, `name`, `category`, `target`, `calculation_type`, `operator`, `points`, `max_points`, `weight`, `requires_manual_review`, `is_exclusionary`, `is_active` | pertence a matriz; tem regras e detalhes de pontuação | Sprint 10 | P0 | Implementado; códigos únicos por matriz e critérios inativos ignorados. |
| `scoring_rules` | Regras de pontuação por critério. | `id`, `scoring_criterion_id`, `label`, `operator`, `value`, `minimum_value`, `maximum_value`, `points`, `weight`, `is_active` | pertence a critério | Sprint 10 | P0 | Implementado; regras ativas permitem intervalos, limites e valores configuráveis. |
| `tie_breaker_rules` | Configurar desempates internos. | `id`, `scoring_rule_set_id`, `code`, `target`, `direction`, `priority_order`, `is_active` | pertence a matriz | Sprint 10 | P0 | Implementado; aplicado após `total_score desc`. |
| `scoring_runs` | Preservar cada execução de classificação. | `id`, `scoring_rule_set_id`, `program_id`, `contest_id`, `status`, `started_by`, counters, `started_at`, `completed_at`, `failure_reason` | pertence a matriz/programa/concurso; tem pontuações e snapshots | Sprint 10 | P0 | Implementado; execuções falhadas preservam estado e erro. |
| `application_scores` | Pontuação calculada por candidatura e execução. | `id`, `scoring_run_id`, `application_id`, `total_score`, `automatic_score`, `manual_score`, `tie_breaker_values`, `rank_position`, `status`, `locked_at` | pertence a execução, candidatura, matriz, programa, concurso e candidato | Sprint 10 | P0 | Implementado; campos críticos escritos por services e protegidos contra mass assignment. |
| `application_score_details` | Detalhar pontuação por critério. | `id`, `application_score_id`, `scoring_criterion_id`, `scoring_rule_id`, `code`, `result`, `points_awarded`, `raw_value`, `technical_message`, `manual_points`, `reviewed_by` | pertence a pontuação, critério e regra opcional | Sprint 10 | P0 | Implementado; `raw_value` contém apenas valores agregados/seguros. |
| `ranking_snapshots` | Congelar ranking interno em momento processual. | `id`, `scoring_run_id`, `program_id`, `contest_id`, `snapshot_number`, `status`, `generated_by`, `generated_at`, `published_at` | pertence a execução/programa/concurso; tem entradas | Sprint 10 | P0 | Implementado; `published_at` permanece nulo até Sprint 11. |
| `ranking_entries` | Linhas do ranking interno. | `id`, `ranking_snapshot_id`, `application_score_id`, `application_id`, `rank_position`, `previous_rank_position`, `total_score`, `tie_breaker_values`, `status` | pertence a snapshot, pontuação e candidatura | Sprint 10 | P0 | Implementado; prepara listas provisórias sem as publicar. |
| `provisional_lists` | Publicar lista provisória derivada do ranking interno. | `id`, `ranking_snapshot_id`, `program_id`, `contest_id`, `title`, `status`, `version_number`, `anonymization_mode`, prazos de reclamação, `approved_at`, `published_at` | pertence a snapshot, programa e concurso; tem entradas, publicações e reclamações | Sprint 11 | P0 | Implementado; exige aprovação antes de publicação. |
| `provisional_list_entries` | Linhas da lista provisória. | `id`, `provisional_list_id`, `application_id`, `user_id`, `rank_position`, `total_score`, `public_identifier`, `entry_status`, `entry_type`, motivos | pertence a lista, candidatura, utilizador e ranking entry | Sprint 11 | P0 | Implementado; usa identificador público pseudonimizado. |
| `definitive_lists` | Lista definitiva após reclamações/audiência. | `id`, `provisional_list_id`, `program_id`, `contest_id`, `title`, `status`, `version_number`, `approved_at`, `published_at`, `locked_at` | pertence à lista provisória; tem entradas e publicações | Sprint 11 | P0 | Implementado; bloqueia geração com pendências abertas. |
| `definitive_list_entries` | Linhas finais e alterações face à provisória. | `id`, `definitive_list_id`, `provisional_list_entry_id`, `application_id`, `final_rank_position`, `original_rank_position`, `change_type`, `public_identifier` | pertence a lista definitiva, entrada provisória e candidatura | Sprint 11 | P0 | Implementado; preserva origem para auditoria. |
| `list_publications` | Versionar publicações por canal. | `id`, `publishable_type`, `publishable_id`, `publication_type`, `channel`, `status`, `payload`, `published_at`, `visible_from`, `visible_until` | polimórfico para listas provisórias/definitivas | Sprint 11 | P0 | Implementado; payload público é minimizado. |
| `complaints` | Registar reclamações. | `id`, `provisional_list_id`, `provisional_list_entry_id`, `application_id`, `user_id`, `complaint_number`, `status`, `subject`, `grounds`, `submitted_at` | pertence a lista, entrada, candidatura e candidato | Sprint 11 | P0 | Implementado; só reclamação própria e durante prazo ativo. |
| `complaint_attachments` | Associar documentos já existentes à reclamação. | `id`, `complaint_id`, `document_submission_id`, `description`, `uploaded_by` | pertence a reclamação e documento opcional | Sprint 11 | P1 | Implementado; não cria storage paralelo. |
| `complaint_reviews` | Registar análise administrativa da reclamação. | `id`, `complaint_id`, `reviewed_by`, `review_result`, `summary`, `internal_notes`, `reviewed_at` | pertence a reclamação e técnico | Sprint 11 | P0 | Implementado; notas internas não são públicas. |
| `complaint_decisions` | Registar decisão fundamentada da reclamação. | `id`, `complaint_id`, `decision_number`, `decision_result`, `status`, `summary`, `grounds`, `approved_by`, `requires_list_update` | pertence a reclamação, candidatura e lista provisória | Sprint 11 | P0 | Implementado; decisão aprovada pode gerar log de alteração. |
| `additional_information_requests` | Solicitar informação complementar. | `id`, `complaint_id`, `application_id`, `user_id`, `request_number`, `status`, `subject`, `message`, `response_deadline_at` | pertence a reclamação, candidatura e candidato | Sprint 11 | P1 | Implementado; sem comunicação externa real. |
| `additional_information_responses` | Guardar resposta complementar do candidato. | `id`, `additional_information_request_id`, `application_id`, `user_id`, `response_text`, `document_submission_id`, `status`, `review_result` | pertence a pedido, candidatura e candidato | Sprint 11 | P1 | Implementado; documento opcional é referência a submissão existente. |
| `hearings` | Gerir audiência de interessados. | `id`, `application_id`, `user_id`, `provisional_list_id`, `complaint_id`, `hearing_number`, `hearing_type`, `status`, `response_deadline_at` | pertence a candidatura, candidato, lista e reclamação opcional | Sprint 11 | P1 | Implementado; resposta própria e revisão backoffice. |
| `hearing_submissions` | Guardar pronúncia do candidato. | `id`, `hearing_id`, `application_id`, `user_id`, `status`, `submission_text`, `submitted_at`, `review_result` | pertence a audiência, candidatura e candidato | Sprint 11 | P1 | Implementado; sem upload físico próprio. |
| `official_notifications` | Registar notificações oficiais internas. | `id`, `user_id`, `application_id`, `notifiable_type`, `notifiable_id`, `notification_type`, `channel`, `status`, `subject`, `body`, `sent_at`, `read_at` | pertence a utilizador e entidade notificável | Sprint 11 | P1 | Implementado; canal externo fica para Sprint 16. |
| `list_change_logs` | Auditar alterações entre listas. | `id`, `application_id`, `provisional_list_id`, `definitive_list_id`, `source_type`, `source_id`, `change_type`, `reason`, `changed_by` | pertence a candidatura e listas; referencia fonte polimórfica | Sprint 11 | P0 | Implementado; não altera ranking original. |
| `housing_units` | Catalogar fogos disponiveis. | `id`, `municipality_id`, `housing_unit_type_id`, `code`, `address`, `typology`, `status`, `monthly_base_rent` | pertence a municipio e tipo; participa em concursos | Sprint 12 | P0 | Evoluir tabela atual com dados tecnicos e disponibilidade historica. |
| `housing_unit_types` | Normalizar tipologias e caracteristicas. | `id`, `code`, `name`, `bedrooms`, `max_occupancy`, `accessibility_features` | usado por `housing_units` | Sprint 12 | P1 | Suporta compatibilidade agregado/fogo. |
| `contest_housing_units` | Associar fogos a concurso. | `id`, `contest_id`, `housing_unit_id`, `available_from`, `status` | pertence a concurso e fogo | Sprint 12 | P0 | Evita atribuir fogos fora do concurso. |
| `allocation_runs` | Executar processo de atribuicao. | `id`, `contest_id`, `type`, `status`, `run_by`, `run_at`, `notes` | pertence a concurso; tem resultados | Sprint 12 | P0 | Deve ser reproduzivel e auditavel. |
| `allocation_results` | Guardar resultado da atribuicao. | `id`, `allocation_run_id`, `application_id`, `housing_unit_id`, `status`, `accepted_at`, `declined_at` | pertence a run, candidatura e fogo | Sprint 12 | P0 | Atribuicao nao deve alterar ranking original. |
| `lottery_runs` | Sorteios quando aplicavel. | `id`, `contest_id`, `seed`, `method`, `status`, `performed_by`, `performed_at` | pertence a concurso; pode alimentar atribuicao | Sprint 12 | P1 | Seed/metodo devem ser auditaveis e publicados se legalmente exigido. |
| `contracts` | Contrato de arrendamento. | `id`, `contract_number`, `allocation_id`, `application_id`, `user_id`, `housing_unit_id`, `rent_calculation_id`, `status`, `monthly_rent`, `deposit_amount`, datas de emissão/assinatura/ativação | pertence a atribuição, candidatura, candidato, agregado, habitação, cálculo e minuta | Sprint 13 | P0 | Implementado por evolução compatível da tabela/model legado `Contract`; contratos processuais distinguem-se por `contract_number`. |
| `rent_calculations` | Calcular renda. | `id`, `rent_rule_set_id`, `allocation_id`, `application_id`, `user_id`, `status`, `calculation_method`, rendimentos, renda base/aplicável, caução, `snapshot`, aprovação | pertence a regra, atribuição, candidatura, candidato, agregado, habitação e contrato | Sprint 13 | P0 | Implementado; guarda snapshot, detalhes e estado formal. |
| `rent_reviews` | Rever renda periodicamente. | `id`, `contract_id`, `previous_rent`, `new_rent`, `reason`, `effective_from`, `approved_by` | pertence a contrato | Sprint 14 | P1 | Requer notificacao e direito de contraditorio quando aplicavel. |
| `contract_deposits` | Registar caucao. | `id`, `lease_contract_id`, `allocation_id`, `application_id`, `user_id`, `status`, `amount`, `requested_at`, `paid_at`, `waived_at`, referências manuais | pertence a contrato, atribuição, candidatura e candidato | Sprint 13 | P1 | Implementado; sem cobrança real nem movimentos financeiros. |
| `payments` | Registar prestacoes/rendas. | `id`, `contract_id`, `amount`, `due_date`, `paid_at`, `status`, `reference`, `source` | pertence a contrato | Sprint 14 | P0 | Evoluir tabela atual para conciliacao e incumprimento. |
| `payment_plans` | Plano de pagamentos/acordos. | `id`, `contract_id`, `type`, `starts_at`, `ends_at`, `status`, `approved_by` | pertence a contrato; tem pagamentos | Sprint 14 | P1 | Usado para regularizacao de divida. |
| `arrears` | Registar incumprimentos. | `id`, `contract_id`, `amount`, `period`, `status`, `notified_at`, `resolved_at` | pertence a contrato | Sprint 14 | P0 | Alimenta relatorios e workflow de recuperacao. |
| `maintenance_requests` | Pedidos de manutencao. | `id`, `housing_unit_id`, `candidate_profile_id`, `title`, `description`, `priority`, `status`, `reported_at` | pertence a fogo e requerente | Sprint 15 | P1 | Evoluir tabela atual com SLA, categorias e anexos. |
| `inspections` | Agendar vistorias. | `id`, `housing_unit_id`, `application_id`, `contract_id`, `scheduled_at`, `inspector_id`, `status` | pertence a fogo, contrato/candidatura e tecnico | Sprint 15 | P1 | Pode suportar vistoria antes/depois de contrato. |
| `inspection_reports` | Relatorio de vistoria. | `id`, `inspection_id`, `summary`, `findings`, `photos_path`, `signed_at`, `signed_by` | pertence a vistoria | Sprint 15 | P1 | Anexos e assinaturas exigem storage privado e auditoria. |
| `notifications` | Registar comunicacoes enviadas. | `id`, `recipient_user_id`, `template_id`, `channel`, `subject`, `status`, `sent_at`, `read_at` | pertence a utilizador e template | Sprint 16 | P0 | Deve guardar prova de envio sem expor conteudo sensivel em excesso. |
| `notification_templates` | Modelos de comunicacao. | `id`, `code`, `name`, `channel`, `subject_template`, `body_template`, `active` | usado por `notifications` | Sprint 16 | P0 | Versionar modelos oficiais. |
| `privacy_consents` | Registar consentimentos quando aplicavel. | `id`, `candidate_profile_id`, `purpose_id`, `status`, `given_at`, `withdrawn_at` | pertence a candidato e finalidade | Sprint 18 | P0 | Nem todo tratamento depende de consentimento; distinguir base legal. |
| `data_processing_purposes` | Catalogar finalidades de tratamento. | `id`, `code`, `name`, `legal_basis`, `description`, `retention_policy_id` | usado por consentimentos e politicas | Sprint 18 | P0 | Base para RGPD e informacao ao titular. |
| `data_subject_requests` | Pedidos de titulares de dados. | `id`, `candidate_profile_id`, `type`, `status`, `submitted_at`, `resolved_at`, `resolution_notes` | pertence a candidato; tratado por utilizadores autorizados | Sprint 18 | P0 | Suportar acesso, retificacao, apagamento, limitacao e portabilidade. |
| `data_retention_policies` | Politicas de retencao e eliminacao. | `id`, `code`, `entity`, `retention_period`, `legal_basis`, `action_after_expiry` | usado por tipos documentais e finalidades | Sprint 18 | P0 | Deve orientar anonimização, eliminacao e bloqueio. |

## Estado implementado na Sprint 12

| Entidade | Estado | Notas |
| --- | --- | --- |
| `contest_housing_units` | Implementada | Liga habitação existente a programa/concurso, com estado operacional, tipologia, ocupação, acessibilidade, renda estimada e auditoria. |
| `typology_adequacy_rules` | Implementada | Regras configuráveis por programa/concurso para avaliar composição do agregado, quartos, tipologia e acessibilidade. |
| `housing_preferences` | Implementada | Preferências ordenadas do candidato por candidatura própria e habitação disponível do concurso. |
| `allocation_rule_sets` | Implementada | Define método ranking/preferências/sorteio, prazos de aceitação e chamada de suplentes. |
| `allocation_runs` | Implementada | Execução auditável baseada em lista definitiva, com counters, estado e método. |
| `allocations` | Implementada | Resultado operacional por candidatura e habitação, com estados de oferta, aceitação, recusa, expiração, desistência e prontidão para contrato. |
| `allocation_offers` | Implementada | Oferta enviada ao candidato via notificação interna, com prazo e resposta. |
| `lottery_runs` | Implementada | Sorteio auditável com seed, algoritmo, payload e hash. |
| `lottery_participants` | Implementada | Participantes elegíveis no sorteio, derivados das entradas da lista definitiva. |
| `lottery_draw_results` | Implementada | Ordem e resultado do sorteio por participante, com valor determinístico auditável. |
| `reserve_lists` | Implementada | Lista suplente gerada a partir de candidatos sem habitação atribuída na execução. |
| `reserve_list_entries` | Implementada | Estado de cada suplente e ligação a substituição/oferta quando chamado. |
| `allocation_reports` | Implementada | Ata/relatório preliminar com método, resultados, exceções, recusas e suplentes. |

Decisões:

- `allocation_results` previsto no modelo alvo foi implementado como `allocations`, por ser a nomenclatura usada no domínio e nas relações Eloquent.
- `housing_unit_types` ainda não foi criado; o sprint reutiliza `housing_units.typology` e campos técnicos da associação ao concurso.
- `official_notifications` foi reutilizada para ofertas, recusas, suplentes e prontidão para contrato.
- Contratos devem referenciar apenas atribuições `ready_for_contract`, sem recalcular ranking ou listas.

## Estado implementado na Sprint 13

| Entidade | Estado | Notas |
| --- | --- | --- |
| `contracts` | Adaptada | A tabela/model legado foi evoluída para contrato processual, preservando compatibilidade. Novos contratos têm `contract_number`, referência a `allocation`, `application`, `user`, `household`, `contest_housing_unit`, `rent_calculation` e `contract_template`. |
| `rent_rule_sets` | Implementada | Regras de renda por programa/concurso, estado, vigência, método de cálculo, taxa de esforço, mínimos, máximos, caução e parâmetros de revisão manual. |
| `rent_rules` | Implementada | Regras complementares por escalão/valor/percentagem, com prioridade e vigência. |
| `rent_calculations` | Implementada | Cálculo ligado à atribuição, candidatura, candidato, agregado e habitação, com rendimentos agregados, renda aplicável, caução, snapshot e aprovação. |
| `rent_calculation_details` | Implementada | Detalhe dos componentes aplicados no cálculo: rendimento, renda base, limites, taxa de esforço e caução. |
| `rent_manual_reviews` | Implementada | Revisão manual com motivo, valor proposto, justificação, aprovação/rejeição e auditoria. |
| `contract_templates` | Implementada | Minutas parametrizáveis por programa/concurso, versão, estado, vigência e corpo com placeholders. |
| `contract_clauses` | Implementada | Cláusulas reutilizáveis por programa/concurso, categoria, obrigatoriedade, estado e vigência. |
| `contract_template_clauses` | Implementada | Associação opcional e ordenada entre minuta e cláusulas. |
| `lease_contract_parties` | Implementada | Snapshot das partes do contrato, incluindo arrendatário e senhorio. |
| `lease_contract_clauses` | Implementada | Snapshot das cláusulas aplicadas ao contrato. |
| `lease_contract_documents` | Implementada | Documento contratual HTML gerado em storage privado, com versão, checksum e metadados. |
| `lease_contract_validations` | Implementada | Validação interna aprovada/rejeitada por tipo jurídico, financeiro, administrativo, técnico ou final. |
| `lease_contract_signatures` | Implementada | Registo manual/interno de assinatura, sem assinatura digital externa. |
| `lease_contract_status_histories` | Implementada | Histórico de transições de estado contratual. |
| `contract_deposits` | Implementada | Caução associada ao contrato, com estados pendente, solicitada, paga, dispensada e cancelada. |

Decisões:

- Não foi criada entidade paralela `lease_contracts`; o model existente `Contract` foi adaptado com campos processuais e scope `processual()`.
- Renda e caução do contrato são derivadas de `rent_calculations` aprovadas; valores enviados no formulário não alteram esses campos críticos.
- Documento contratual é HTML privado porque não existe biblioteca PDF instalada no projeto.
- `official_notifications` foi reutilizada para eventos contratuais internos/in-app, sem envio externo.
- Cobrança real, faturação, recibos, reconciliação e revisão periódica de renda ficam para Sprint 14.

## Estado implementado na Sprint 14

| Entidade | Estado | Notas |
| --- | --- | --- |
| `tenant_financial_accounts` | Implementada | Conta corrente por contrato ativo, ligada a contrato, candidato, agregado e habitação, com saldo e totais agregados. |
| `rent_schedules` | Implementada | Plano de rendas ativo/fechado/cancelado, com período, renda mensal, dia de pagamento e prestações geradas. |
| `rent_installments` | Implementada | Prestação mensal com referência única, vencimento, valor devido, pago, dispensado e em aberto. |
| `lease_payments` | Implementada | Pagamento de renda manual/importado, com confirmação, imputação, estorno e referência externa opcional. |
| `payment_allocations` | Implementada | Associação entre pagamento e prestação, preservando histórico de imputação e estorno. |
| `payment_import_batches` | Implementada | Lote CSV interno com estado, ficheiro privado e contadores de processamento. |
| `payment_import_rows` | Implementada | Linha de importação com referência, valor, data, correspondência e erro quando aplicável. |
| `payment_receipts` | Implementada | Comprovativo interno HTML em storage privado; não é recibo fiscal oficial. |
| `financial_transactions` | Implementada | Extrato financeiro por conta com tipo, valor, saldo e referência polimórfica ao evento. |
| `arrears` | Implementada | Incumprimento por prestação vencida em aberto, com dias de atraso e estado. |
| `default_notices` | Implementada | Aviso de incumprimento com emissão interna e visibilidade controlada ao candidato. |
| `regularization_agreements` | Implementada | Acordo de regularização associado a conta/contrato e incumprimentos. |
| `regularization_installments` | Implementada | Prestações do acordo, independentes das prestações mensais de renda. |
| `rent_reviews` | Implementada | Revisão pós-contrato com cálculo manual, aprovação, aplicação e novo plano de renda. |
| `income_change_declarations` | Implementada | Declaração do candidato para alteração de rendimentos, podendo originar revisão de renda. |
| `annual_document_update_requests` | Implementada | Pedido anual de atualização documental por contrato/conta. |
| `annual_document_update_submissions` | Implementada | Submissões do candidato ligadas a documentos já existentes. |

Decisões:

- `payments` legado foi preservado; pagamentos processuais usam `lease_payments` para evitar colisão semântica.
- `payment_plans` previsto no modelo alvo foi implementado como `regularization_agreements` e `regularization_installments`, com nomenclatura mais explícita.
- Recibos são comprovativos internos HTML privados, sem valor fiscal oficial.
- Importação de pagamentos é CSV interno sem integração bancária, SEPA, MB, SIBS ou gateway.
- Revisão de renda pós-contrato não reutiliza o cálculo inicial como decisão automática; exige aprovação antes de aplicar.

## Estado implementado na Sprint 15

| Entidade | Estado | Notas |
| --- | --- | --- |
| `maintenance_categories` | Implementada | Catálogo de categorias com prioridade padrão e flags operacionais. |
| `maintenance_suppliers` | Implementada | Registo operacional de fornecedores; sem portal externo, faturação ou pagamentos. |
| `maintenance_requests` | Adaptada | Tabela/model legado evoluído com número único, contrato, candidato, categoria, origem, urgência, estado, triagem, agendamento e fecho. |
| `maintenance_request_status_histories` | Implementada | Histórico formal de transições com motivo, ator e timestamp. |
| `maintenance_assignments` | Implementada | Atribuição a técnico interno ou fornecedor registado. |
| `maintenance_interventions` | Implementada | Registo de intervenção, agendamento, execução, conclusão e notas técnicas. |
| `maintenance_attachments` | Implementada | Anexos privados de pedidos/intervenções, com checksum, visibilidade e download autorizado. |
| `maintenance_costs` | Implementada | Custos operacionais aprováveis por pedido/intervenção/habitação/contrato; sem liquidação financeira. |
| `inspection_checklist_templates` | Implementada | Templates de checklist versionáveis para vistorias. |
| `inspection_checklist_template_items` | Implementada | Itens ordenados por área para copiar para vistorias. |
| `property_inspections` | Implementada | Vistorias por habitação/contrato com número único, tipo, estado, técnico, datas, condição e visibilidade ao arrendatário. |
| `property_inspection_items` | Implementada | Itens preenchidos da vistoria com condição, observações e indicação de manutenção. |
| `property_inspection_attachments` | Implementada | Fotografias/anexos privados de vistorias. |
| `property_inspection_reports` | Implementada | Auto HTML privado, com checksum, estado e download autorizado. |
| `property_history_events` | Implementada | Histórico técnico consolidado por habitação, contrato, pedido, intervenção e vistoria. |

Decisões:

- `inspections` previsto no modelo alvo foi implementado como `property_inspections` para evitar ambiguidade com outros tipos de inspeção.
- `inspection_reports` previsto foi implementado como `property_inspection_reports`.
- `maintenance_requests` legado foi preservado e evoluído em vez de criar tabela paralela.
- Autos de vistoria são HTML privados porque não existe infraestrutura PDF instalada.
- Custos de manutenção alimentam relatório operacional, mas não geram pagamentos a fornecedores.

## Estado implementado na Sprint 16

| Entidade | Estado | Notas |
| --- | --- | --- |
| `official_notifications` | Adaptada | Preserva os registos anteriores e acrescenta número, comunicação de origem, evento, prioridade, contactos snapshot, leitura, tomada de conhecimento, arquivo e expiração. |
| `notification_templates` | Implementada | Template por canal/contexto, com estado, conteúdo base, flags oficiais e versão ativa. |
| `notification_template_versions` | Implementada | Snapshot versionado, aprovável e ativável; versões usadas por comunicações não são mutadas. |
| `template_variables` | Implementada | Catálogo de placeholders, tipo, origem, exemplo fictício, obrigatoriedade e sensibilidade. |
| `notification_event_rules` | Implementada | Liga evento, destinatário, canal e template, com prioridade, atraso e contexto municipal/programa/concurso. |
| `communication_logs` | Implementada | Comunicação numerada com destinatário, evento, referência polimórfica, template/versão e snapshots imutáveis. |
| `communication_deliveries` | Implementada | Entrega por canal, destino, provider, estado, timestamps, falha e referência postal. |
| `communication_attempts` | Implementada | Tentativas numeradas, provider, resultado resumido e erro limitado. |
| `communication_receipts` | Implementada | Comprovativos privados de envio, leitura, tomada de conhecimento e postal. |
| `notification_preferences` | Implementada | Preferências opcionais de email/SMS/postal; in-app permanece obrigatório. |
| `document_templates` | Implementada | Minuta documental parametrizável, contextual, aprovável e com versão ativa. |
| `document_template_versions` | Implementada | Conteúdo, cabeçalho, rodapé e esquema de variáveis por versão. |
| `generated_official_documents` | Implementada | Documento HTML privado, numerado, com snapshot, checksum, destinatário, emissão e cancelamento. |

Decisões:

- `official_notifications` continua a ser a caixa de entrada in-app; `communication_logs` é o registo transversal multicanal.
- Email, SMS e postal são entregas do mesmo registo, não sistemas paralelos.
- Os documentos oficiais usam HTML privado porque não existe biblioteca PDF instalada.
- Os comprovativos provam a operação interna registada; não equivalem a notificação eletrónica certificada.

## Estado implementado na Sprint 17

| Entidade | Estado | Notas |
| --- | --- | --- |
| `indicator_definitions` | Implementada | Catálogo, categoria, tipo de valor, serviço/método permitido, permissão e sensibilidade. |
| `indicator_snapshots` | Implementada | Valor, filtros normalizados/hash, estado, data e utilizador de cálculo. |
| `dashboard_definitions` | Implementada | Dashboard por tipo, permissão, estado e filtros predefinidos. |
| `dashboard_widgets` | Implementada | Widget ordenado ligado a indicador e permissão específica. |
| `report_definitions` | Implementada | Tipo, sensibilidade, query allowlisted, formatos, âmbitos e schema de filtros. |
| `report_filter_presets` | Implementada | Filtros guardados por utilizador e relatório. |
| `report_runs` | Implementada | Execução auditável com filtros, âmbito, formato, estado e contagem. |
| `report_exports` | Implementada | Ficheiro privado, formato pedido/real, âmbito, expiração e download. |
| `report_download_logs` | Implementada | Utilizador, exportação, IP, user agent e timestamp. |
| `report_access_logs` | Implementada | Consulta, execução, exportação e download de dashboard/relatório. |

Decisões:

- definições configuráveis não executam classes arbitrárias; registries aplicam allowlists;
- resultados detalhados não são persistidos em `report_runs`;
- exportações usam storage privado `local` e identificadores UUID;
- CSV é nativo; XLSX e PDF usam fallback até existir infraestrutura própria.

## Observacoes de evolucao

- As entidades atuais podem ser migradas ou adaptadas gradualmente, mas nao devem ser expandidas sem desenho de autorizacao e auditoria.
- O modelo alvo deve evitar guardar dados pessoais quando uma referencia, estado ou comprovativo for suficiente.
- Todas as entidades que participam em decisoes administrativas devem ter historico ou audit log.
- Tabelas de regras, criterios e templates devem ser versionadas para preservar a decisao tomada no momento.

## Estado implementado na Sprint 18

| Entidade | Estado | Notas |
| --- | --- | --- |
| `audit_events` | Implementada | Audit trail append-only com mascaramento de dados sensíveis e contexto de request. |
| `access_logs` | Implementada | Regista login, logout, falhas, backoffice, downloads e exportações. |
| `sensitive_data_access_logs` | Implementada | Regista acessos a documentos, relatórios/exportações e dados sensíveis. |
| `permission_reviews` | Implementada | Revisão formal de permissões por scope e estado. |
| `permission_review_items` | Implementada | Findings por utilizador, role, permissão ou módulo. |
| `mfa_devices` | Implementada | Dispositivo TOTP com secret encriptado. |
| `mfa_recovery_codes` | Implementada | Recovery codes apenas hashed e com uso único. |
| `consent_purposes` | Implementada | Finalidades, base legal, obrigatoriedade e retenção prevista. |
| `user_consents` | Implementada | Consentimentos com snapshot de texto, origem, timestamps e retirada opcional. |
| `retention_policies` | Implementada | Políticas por entidade, ação, período e aprovação manual. |
| `retention_executions` | Implementada | Simulação/execução auditável; execução real conservadora nesta sprint. |
| `data_subject_requests` | Implementada | Pedido RGPD do titular com tipo, estado, prazo, titular e atribuição. |
| `data_subject_request_actions` | Implementada | Histórico de ações do pedido RGPD. |
| `data_export_packages` | Implementada | Exportação JSON privada com checksum, expiração e download auditado. |
| `anonymization_requests` | Implementada | Pedido de anonimização com scope, aprovação e execução. |
| `encrypted_field_registries` | Implementada | Inventário de campos sensíveis e estratégia de encriptação/pesquisa. |
| `security_alert_rules` | Implementada | Regras de alerta por evento, severidade, threshold e janela temporal. |
| `security_alerts` | Implementada | Alertas gerados, analisados e resolvidos. |
| `backup_reviews` | Implementada | Revisão operacional de backup/restore. |
| `security_checklists` | Implementada | Checklist pré-produção por ambiente. |
| `security_checklist_items` | Implementada | Itens de checklist com estado, evidência e recomendação. |

Decisões:

- `audit_logs` não foi removido; `AuditLogger` espelha eventos no novo `audit_events` quando a tabela existe.
- MFA é interno/TOTP para esta sprint, sem integração externa.
- Encriptação física de campos existentes fica em registry para execução posterior controlada.
- Retenção real não apaga dados automaticamente nesta sprint para reduzir risco operacional.
# Atualização Sprint 19 — Testabilidade do modelo

A Sprint 19 não alterou o modelo processual de domínio. Foram acrescentadas factories e seeder de QA para melhorar a testabilidade de entidades já existentes:

- `TenantFinancialAccountFactory`;
- `RentScheduleFactory`;
- `RentInstallmentFactory`;
- `LeasePaymentFactory`;
- `ArrearFactory`;
- `Database\Seeders\Testing\IntegratedWorkflowTestSeeder`.

Os models financeiros correspondentes passaram a suportar `HasFactory`. Esta alteração é de testabilidade e não altera regras de negócio nem schema.

## Estado implementado na Sprint 20

| Entidade/Campo | Estado | Notas |
| --- | --- | --- |
| `housing_units.public_*` | Implementado | Ficha pública, slug, resumo, descrição, localização, estado, SEO e publicação. |
| `housing_unit_features` | Implementada | Características editoriais públicas por habitação. |
| `housing_unit_images` | Implementada | Galeria pública com capa, aprovação e soft deletes. |
| `housing_unit_public_documents` | Implementada | Brochuras/fichas públicas com download controlado e contador. |
| `public_portal_settings` | Implementada | Configurações públicas do mapa e da página. |
| `public_portal_links` | Implementada | Ligações institucionais configuráveis. |

Decisões:

- documentos públicos não reutilizam documentos de candidatura;
- a localização pública é controlada por precisão e flag de morada completa;
- a publicação usa campos editoriais em `housing_units` para evitar nova entidade processual nesta sprint;
- mapa e filtros usam queries paginadas/eager loading e não expõem paths internos.
## Atualização Sprint 21 — Simulador avançado

Entidades adicionadas:

- `simulation_sessions`: sessão pública/anónima ou autenticada, estado, escopo, hashes técnicos e conversão.
- `simulation_input_snapshots`: snapshot dos dados mínimos usados na simulação.
- `simulation_results`: resultado indicativo, completude, tipologia, renda e contadores.
- `simulation_impediments`: impedimentos, avisos e bloqueios indicativos.
- `simulation_recommended_contests`: concursos publicados recomendados pela simulação.
- `candidate_data_reuse_profiles`: snapshots privados para reaproveitamento de dados.
- `application_prefills`: pacotes de pré-preenchimento confirmáveis pelo candidato.
- `registration_renewals`: renovação simplificada do Registo de Adesão.
- `simulator_configurations`: parâmetros configuráveis do simulador.

Estas entidades não substituem `applications`, `eligibility_checks`, `rent_calculations` nem decisões administrativas formais.

## Atualização Sprint 24 — Backoffice operacional

Entidades adicionadas:

- `backoffice_dashboard_snapshots`: snapshots agregados de dashboards.
- `application_reports`: relatórios operacionais por candidatura.
- `document_dossiers`: dossier documental padronizado por candidatura.
- `document_dossier_items`: itens normalizados do dossier documental.
- `internal_alerts`: alertas internos de prazos, ações e pendências.
- `procedure_templates`: minutas de procedimento versionáveis.
- `generated_procedure_documents`: documentos gerados a partir de minutas.
- `list_automation_runs`: execuções auditáveis de automação assistida de listas.
- `procedure_minutes`: atas de procedimento geradas por minuta.
- `process_confirmations`: confirmações automáticas e número de processo.

Decisões:

- os novos artefactos não substituem relatórios, listas, notificações ou auditoria existentes;
- os ficheiros gerados ficam em storage privado;
- listas e atas preservam validação humana obrigatória;
- os modelos usam route keys técnicos (`report_number`, `dossier_number`, `alert_number`, `template_number`, `document_number`, `run_number`, `minute_number`, `confirmation_number`).

## Atualização Sprint 28 — OCR e classificação documental

Campos acrescentados a `document_ai_analyses`:

- `ocr_status`, `ocr_available`, `ocr_engine`, `ocr_language`, `ocr_text`, `ocr_quality_score`, `ocr_pages_count`, `ocr_processed_at`;
- `classification_status`, `detected_document_type`, `detected_document_label`, `classification_confidence`, `classification_source`, `classification_model`, `classification_prompt_version`, `classification_signals`, `classification_requires_manual_review`, `classified_at`.

Entidades existentes reutilizadas:

- `document_ai_fields` guarda o tipo documental classificado como field estruturado.
- `document_ai_flags` guarda falhas de OCR, baixa confiança e revisão manual.
- `document_ai_processing_logs` guarda logs técnicos minimizados.
- `document_submissions` e `document_versions` continuam como fonte documental privada.

Decisão arquitetural:

- classificação automática fica acoplada ao módulo Document Intelligence, não ao core de candidatura;
- nenhuma entidade de candidatura, elegibilidade, ranking ou documento funcional é alterada automaticamente pela classificação IA.

## Atualização Sprint 29 — Extração estruturada documental

Campos acrescentados a `document_ai_analyses`:

- `extraction_status`, `extraction_schema_version`, `extraction_json`, `extraction_confidence`;
- `extraction_model`, `extraction_prompt_version`;
- `extraction_started_at`, `extraction_completed_at`, `extraction_failed_at`;
- `extraction_requires_manual_review`.

Campos acrescentados a `document_ai_fields`:

- `document_type`;
- `source`;
- `requires_review`.

Decisão arquitetural:

- `extraction_json` guarda o payload bruto estruturado por schema;
- `document_ai_fields` guarda a versão normalizada e pesquisável dos campos extraídos;
- `document_ai_flags` guarda flags técnicas sem valores pessoais;
- `document_ai_processing_logs` guarda apenas metadados de execução;
- a extração não escreve em `applications`, `households`, `income_records`, `eligibility_checks`, `application_scores`, contratos ou workflows.

## Atualização Sprint 30 — Validação IA contra candidatura

Entidades adicionadas:

- `document_ai_validation_runs`: execução de cruzamento por candidatura, com estado, contadores, necessidade de revisão, timestamps e autor.
- `document_ai_validations`: resultado granular por análise IA, candidatura, submissão documental, grupo, chave, estado, severidade, confiança, valores opcionais, hashes, método, mensagem, recomendação e revisão manual.

Relações adicionadas:

- `Application::documentAiValidationRuns()`;
- `Application::documentAiValidations()`;
- `DocumentAiAnalysis::validations()`.

Decisão arquitetural:

- a validação IA fica no módulo Document Intelligence e não no core de candidatura;
- `document_ai_validations` guarda evidência assistiva, não decisão administrativa;
- hashes permitem rastreabilidade sem depender apenas do valor em claro;
- flags críticas reutilizam `document_ai_flags`;
- processing logs continuam minimizados;
- nenhuma validação escreve em estados de candidatura, documentos funcionais, elegibilidade, pontuação, ranking, listas, contratos ou workflow.
## Sprint 31 — Assistente IA Documental

Novas entidades:

- `document_ai_scores`: score de confiança por análise documental, com componentes, explicação, resumo, label e indicação de revisão manual.
- `document_ai_suggestions`: sugestões internas de aperfeiçoamento por análise e flag.

Entidade atualizada:

- `document_ai_flags`: adiciona `score_impact`, `suggestion_template`, `detected_by` e `confidence`.

Relações principais:

- `document_ai_scores.document_ai_analysis_id` referencia `document_ai_analyses`.
- `document_ai_scores.document_submission_id` referencia `document_submissions`.
- `document_ai_scores.application_id` referencia `applications`.
- `document_ai_suggestions.document_ai_analysis_id` referencia `document_ai_analyses`.
- `document_ai_suggestions.document_ai_score_id` referencia `document_ai_scores`.
- `document_ai_suggestions.application_id` referencia `applications`.

Notas:

- scores e sugestões não substituem validações administrativas;
- não armazenam OCR bruto nem JSON bruto da IA.
