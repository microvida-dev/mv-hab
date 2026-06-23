# Matriz de permissoes

## Atualização Sprint 26 — Área do inquilino

| Módulo | Administrador | Técnico municipal | Júri | Gestor financeiro | Gestor manutenção | Candidato/Inquilino | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Área do inquilino | view/audit | view/audit | none | view | view | view próprias | view/audit |
| Faturas inquilino | create/update/view/audit | create/update/view/audit | none | create/update/view/audit | view | view próprias | view/audit |
| Pagamentos inquilino | create/update/view/audit | create/update/view/audit | none | create/update/view/audit | none | view próprios | view/audit |
| Cobranças internas | create/view/audit | create/view/audit | none | create/view/audit | none | none | view/audit |
| Manutenção inquilino | view/update/audit | view/update/audit | none | none | create/update/view/audit | create/view próprios | view/audit |
| Vistorias inquilino | view/update/audit | view/update/audit | none | none | create/update/view/audit | view próprias visíveis | view/audit |
| Comunicações inquilino | create/update/view/audit | create/update/view/audit | none | create/update/view | create/update/view | create/view próprias | view/audit |

Permissoes permitidas: `view`, `create`, `update`, `delete`, `approve`, `reject`, `publish`, `export`, `audit`, `none`.

Esta matriz continua a representar o alvo global. A fundação de roles/permissions existe desde a Sprint 1 e a Sprint 3 aplica policies aos módulos `programs` e `contests`.

## Aplicação na Sprint 3

- Administrador: acesso integral a programas e concursos, incluindo publicação e eliminação controlada.
- Técnico municipal: consulta, criação e atualização; sem publicação ou eliminação.
- Candidato: sem acesso ao backoffice de programas/concursos.
- Guest: acesso apenas às páginas públicas e apenas a registos publicados.
- Auditor: leitura e auditoria, sem escrita operacional.
- Rotas de backoffice exigem autenticação; cada ação exige policy.
- Slugs são usados nas rotas públicas; IDs internos não fazem parte dos URLs públicos.

## Aplicação na Sprint 4

- A middleware `role` separa rotas de candidatos e roles internas.
- Novas contas recebem a role `candidate`.
- Candidatos não acedem ao CRM legado, programas administrativos ou concursos administrativos.
- A policy de adesão exige ownership para consultar, atualizar, finalizar, cancelar ou remover.
- O formulário não aceita `user_id`, `status` nem timestamps processuais.
- Administradores podem consultar por policy para suporte futuro, mas não existe interface administrativa de edição nesta sprint.
- A eliminação da conta fica bloqueada enquanto existir histórico de adesão.

## Aplicação na Sprint 5

- Candidato consulta e altera apenas agregado, membros, rendimentos e habitação ligados ao próprio Registo de Adesão.
- Criação e alteração de ownership são exclusivas dos services; IDs enviados pelo formulário são ignorados ou rejeitados.
- Remoção de membros e rendimentos é soft delete e exige ownership; o último requerente não pode ser removido.
- Técnico municipal não recebe interface de edição destes dados nesta sprint.
- Auditoria regista entidade, ação e campos alterados, sem copiar NIF, morada, incapacidade, violência doméstica, notas ou montantes.
- Estados `cancelled`, `removed`, `blocked` e `expired` bloqueiam escrita.

## Aplicação na Sprint 6

- Candidato consulta, submete, substitui, descarrega e remove apenas documentos ligados ao próprio Registo de Adesão.
- Candidato não consegue alterar diretamente `status`, `user_id`, `adhesion_registration_id`, `storage_path`, `checksum`, `validated_by` ou `rejected_by`.
- Uploads passam por Form Request e service; os alvos permitidos são resolvidos pelo registo do candidato.
- Documentos ficam em storage privado e downloads passam por controller autorizado.
- Administrador gere tipos documentais e regras obrigatórias.
- Técnico municipal pode consultar fila documental, marcar em análise, validar, rejeitar e descarregar documentos para revisão.
- Auditor tem permissão de leitura/auditoria, sem ações de revisão ou escrita operacional.
- `DocumentSubmissionPolicy`, `DocumentTypePolicy`, `RequiredDocumentPolicy` e `DocumentReviewPolicy` aplicam permissions e ownership.
- Cada download, upload, substituição e decisão documental cria log de acesso e evento de auditoria quando aplicável.

## Aplicação na Sprint 8

- Candidato pode listar, criar, atualizar e submeter apenas as próprias candidaturas.
- Atualização e submissão exigem estado `draft`; o comprovativo exige número formal atribuído.
- Desistência é permitida apenas ao titular e nos estados `draft` ou `submitted`.
- IDs de ownership, concurso, programa, agregado, habitação, estado, número e timestamps são definidos pelos services.
- Rotas do candidato usam `public_id` UUID; IDs internos não são usados no URL da candidatura.
- Backoffice exige role interna e permissão `applications.view`; nesta sprint é apenas leitura.
- Júri, gestor financeiro e auditor mantêm apenas a leitura definida pela matriz; não foram criadas ações administrativas de decisão.
- Acesso ao comprovativo e detalhe administrativo gera auditoria.

## Aplicação na Sprint 7

- Guest não acede a pré-checks, checks, configuração ou snapshots.
- Candidato tem `eligibility.view` e `eligibility.create` apenas para pré-check dos próprios dados e consulta dos próprios checks.
- Candidato não acede a rule sets, critérios, mensagens técnicas ou snapshots.
- Administrador gere rule sets e critérios e executa/reexecuta checks.
- Técnico municipal consulta, configura e executa checks conforme `eligibility.*`.
- Júri consulta resultados conforme a matriz, sem interface de configuração nesta sprint.
- Auditor consulta histórico e auditoria, sem alterar ou executar verificações.
- Cinco policies aplicam ownership e separação candidato/backoffice.

## Aplicação na Sprint 9

- Backoffice administrativo exige role interna e permissão `administrative_processes.view/create/update/approve`.
- Candidato consulta apenas processos associados às próprias candidaturas e apenas pedidos de aperfeiçoamento `candidate_visible`.
- Candidato responde apenas aos próprios pedidos, dentro do prazo e sem alterar estados administrativos.
- Auditor mantém leitura/auditoria, sem criação de pedidos, respostas, decisões, tarefas ou notas.
- Notas internas (`visibility=internal`) e mensagens técnicas de análise não aparecem na área do candidato.
- Campos críticos como `status`, `process_number`, `request_number`, ownership, decisão e timestamps são definidos por services.
- Documentos associados a respostas continuam protegidos por `DocumentSubmissionPolicy` e não expõem paths internos.
- Policies criadas: `AdministrativeProcessPolicy`, `ApplicationReviewPolicy`, `CorrectionRequestPolicy`, `CorrectionResponsePolicy`, `AdministrativeDecisionPolicy`, `AdministrativeTaskPolicy`, `AdministrativeProcessNotePolicy`, `AdministrativeWorkflowConfigPolicy`.

## Aplicação na Sprint 10

- Backoffice de classificação exige role interna e permissão `scoring.view/create/update/approve/reject`.
- Técnico municipal tem permissões operacionais de scoring para configurar, executar, rever e bloquear quando autorizado.
- Júri consulta pontuações e pode participar em avaliação manual via permissão de aprovação, sem publicar listas nesta sprint.
- Auditor consulta resultados e histórico, sem criar matrizes, executar classificação ou alterar pontuação.
- Candidato não acede a `/backoffice/scoring`, não vê ranking interno nem pontuação.
- Campos críticos de pontuação (`total_score`, `automatic_score`, `manual_score`, ranking, lock e exclusão) são escritos por services.
- Policies criadas: `ScoringRuleSetPolicy`, `ScoringCriterionPolicy`, `ScoringRulePolicy`, `TieBreakerRulePolicy`, `ScoringRunPolicy`, `ApplicationScorePolicy`, `RankingSnapshotPolicy`, `RankingEntryPolicy`.

## Aplicação na Sprint 11

- Backoffice de listas exige role interna e permissões `public_lists.view/create/update/approve/publish`.
- Backoffice de reclamações/audiência exige permissões `complaints.view/create/update/approve/reject`.
- Candidato consulta apenas resultados publicados relacionados com as próprias candidaturas e só reclama sobre a própria entrada.
- Reclamações, respostas complementares, pronúncias e notificações oficiais da área candidata usam ownership por `user_id` e `application_id`.
- Auditor mantém leitura/auditoria, sem aprovar, publicar, decidir ou enviar notificações.
- Dados públicos de listas passam por anonimização; a view pública não recebe NIF, email, telefone, morada, documentos, paths ou números internos.
- Campos críticos como estado, posição final, publicação, versionamento, notificações e efeitos de decisão são definidos por services.
- Policies criadas: `ProvisionalListPolicy`, `ProvisionalListEntryPolicy`, `DefinitiveListPolicy`, `DefinitiveListEntryPolicy`, `ListPublicationPolicy`, `ComplaintPolicy`, `ComplaintReviewPolicy`, `ComplaintDecisionPolicy`, `AdditionalInformationRequestPolicy`, `AdditionalInformationResponsePolicy`, `HearingPolicy`, `HearingSubmissionPolicy`, `OfficialNotificationPolicy`, `ListChangeLogPolicy`.

## Aplicação na Sprint 12

- Backoffice de atribuição exige role interna e permissões `allocations.view/create/update/approve/audit`.
- Candidato consulta apenas as próprias preferências, ofertas e atribuições.
- Candidato não acede a `/backoffice/allocation`.
- Auditor consulta execuções, sorteios, listas suplentes e relatórios, incluindo vista de auditoria do sorteio, sem escrita operacional.
- Campos críticos como estados, números, ownership, timestamps, hash do sorteio e prontidão para contrato são definidos por services.
- Preferências ficam bloqueadas quando existe atribuição para a candidatura.
- Ofertas de outro candidato são bloqueadas por policy.
- Policies criadas: `ContestHousingUnitPolicy`, `TypologyAdequacyRulePolicy`, `HousingPreferencePolicy`, `AllocationRuleSetPolicy`, `AllocationRunPolicy`, `AllocationPolicy`, `AllocationOfferPolicy`, `LotteryRunPolicy`, `LotteryParticipantPolicy`, `LotteryDrawResultPolicy`, `ReserveListPolicy`, `ReserveListEntryPolicy`, `AllocationReportPolicy`.

## Aplicação na Sprint 13

- Backoffice contratual exige role interna e permissões `contracts.view/create/update/approve`.
- Candidato consulta apenas os próprios contratos, documentos contratuais e caução.
- Candidato não acede a `/backoffice/contracts`.
- Auditor mantém leitura/auditoria, sem criar cálculos, minutas, contratos, validações, assinaturas ou cauções.
- Técnico municipal pode configurar e preparar contratos quando possui `contracts.create/update`, mas ativação/validação dependem de `contracts.approve`.
- Gestor financeiro pode consultar contratos e gerir caução quando possui permissões contratuais/financeiras.
- Renda, caução, número de contrato, estados, timestamps, validações, assinaturas e ownership são definidos por services, não por mass assignment.
- Documento contratual fica em storage privado e download passa por policy/controller.
- Policies criadas/adaptadas: `ContractPolicy`, `LeaseContractPolicy`, `RentRuleSetPolicy`, `RentRulePolicy`, `RentCalculationPolicy`, `RentManualReviewPolicy`, `ContractTemplatePolicy`, `ContractClausePolicy`, `LeaseContractDocumentPolicy`, `LeaseContractValidationPolicy`, `LeaseContractSignaturePolicy`, `ContractDepositPolicy`.

## Aplicação na Sprint 14

- Backoffice financeiro exige role interna e permissões `finance.view/create/update/approve` ou permissões de `payments` quando aplicável.
- Gestor financeiro gere contas, planos, pagamentos, imputações, comprovativos, incumprimentos, avisos, acordos e revisões.
- Técnico municipal pode consultar/operar apenas quando tiver permissões financeiras configuradas.
- Auditor mantém leitura/auditoria, sem criar pagamentos, emitir avisos, aprovar revisões ou alterar estados.
- Candidato consulta apenas contas, prestações, pagamentos, comprovativos, avisos emitidos, acordos e revisões associados ao próprio `user_id`.
- Candidato pode criar/submeter apenas declarações próprias de alteração de rendimentos e respostas a pedidos documentais próprios.
- Outro candidato é bloqueado por policy em contas, comprovativos, avisos, acordos, revisões e pedidos documentais alheios.
- Campos críticos como saldos, números, estados, timestamps, imputações, estornos, aplicação de revisão e visibilidade de avisos são definidos por services.
- Comprovativos usam storage privado e download autorizado; paths internos não são expostos nas views.
- Policies criadas: `TenantFinancialAccountPolicy`, `RentSchedulePolicy`, `RentInstallmentPolicy`, `LeasePaymentPolicy`, `PaymentReceiptPolicy`, `ArrearPolicy`, `DefaultNoticePolicy`, `RegularizationAgreementPolicy`, `RentReviewPolicy`, `IncomeChangeDeclarationPolicy`, `AnnualDocumentUpdateRequestPolicy`, `PaymentImportBatchPolicy`.

## Aplicação na Sprint 15

- Backoffice de manutenção exige role interna e permissões `maintenance_requests.view/create/update/approve/reject` conforme a ação.
- Backoffice de vistorias exige role interna e permissões `inspections.view/create/update/approve`.
- Gestor de manutenção tem operação completa de pedidos, atribuições, intervenções, custos, vistorias, relatórios e histórico técnico.
- Técnico municipal pode operar quando possuir as permissões configuradas; auditor mantém leitura/auditoria sem escrita operacional.
- Candidato consulta e cria apenas pedidos próprios associados a contrato ativo próprio.
- Candidato consulta apenas vistorias, autos e histórico técnico marcados como visíveis ao arrendatário.
- Outro candidato é bloqueado em pedidos, anexos, autos, vistorias e histórico técnico alheios.
- Campos críticos como números, estados, ownership, urgência técnica, timestamps, validação e visibilidade são definidos por services.
- Anexos e autos usam storage privado; downloads passam por policy/controller e não expõem paths internos.
- Policies criadas/adaptadas: `MaintenanceRequestPolicy`, `MaintenanceCategoryPolicy`, `MaintenanceSupplierPolicy`, `MaintenanceAssignmentPolicy`, `MaintenanceInterventionPolicy`, `MaintenanceAttachmentPolicy`, `MaintenanceCostPolicy`, `InspectionChecklistTemplatePolicy`, `PropertyInspectionPolicy`, `PropertyInspectionItemPolicy`, `PropertyInspectionAttachmentPolicy`, `PropertyInspectionReportPolicy`, `PropertyHistoryEventPolicy`, `MaintenanceDashboardPolicy`, `MaintenanceCostReportPolicy`.

## Aplicação na Sprint 16

- Administrador gere templates, versões, variáveis, regras, comunicações, documentos e publicação/ativação.
- Técnico municipal consulta e opera comunicações quando possui `notifications.view/create/update`; ativação de versões exige `notifications.publish`.
- Gestores financeiro e de manutenção podem criar comunicações do respetivo domínio, sem gerir templates quando não têm `notifications.update`.
- Candidato consulta apenas notificações, comunicações e documentos próprios e atualiza apenas as próprias preferências.
- Auditor consulta centro, histórico, entregas, tentativas e comprovativos sem criar, reenviar, cancelar, arquivar ou ativar.
- Downloads de comprovativos e documentos exigem policy.
- Tomada de conhecimento só é permitida ao destinatário e quando a notificação a exige.
- Policies criadas/adaptadas: `NotificationTemplatePolicy`, `NotificationTemplateVersionPolicy`, `TemplateVariablePolicy`, `NotificationEventRulePolicy`, `CommunicationLogPolicy`, `CommunicationDeliveryPolicy`, `CommunicationAttemptPolicy`, `CommunicationReceiptPolicy`, `NotificationPreferencePolicy`, `DocumentTemplatePolicy`, `DocumentTemplateVersionPolicy`, `GeneratedOfficialDocumentPolicy`, `OfficialNotificationPolicy`.

## Aplicação na Sprint 17

- `reports.view` permite catálogo e dashboard operacional; candidato continua bloqueado.
- `reports.view_executive` protege o dashboard e relatórios executivos.
- `reports.view_sensitive`, `reports.view_financial` e `reports.view_maintenance` segmentam domínios sensíveis.
- `reports.manage` protege definições de relatório, indicadores, dashboards e widgets.
- `reports.export_sensitive`, `reports.export_financial` e `reports.export_nominal` separam exportação agregada de exportação sensível.
- `reports.audit` permite consulta read-only dos logs de acesso e download.
- Técnico municipal consulta reporting operacional e documental autorizado.
- Gestor financeiro consulta/exporta relatórios financeiros autorizados.
- Gestor de manutenção consulta/exporta relatórios do seu domínio.
- Auditor consulta reporting não restrito e logs, sem gestão.
- Listagens de definições, execuções e exportações são filtradas pelas mesmas Policies do detalhe.
- Policies criadas: `DashboardDefinitionPolicy`, `DashboardWidgetPolicy`, `IndicatorDefinitionPolicy`, `IndicatorSnapshotPolicy`, `ReportDefinitionPolicy`, `ReportRunPolicy`, `ReportExportPolicy`, `ReportDownloadPolicy`, `ReportFilterPresetPolicy`, `ReportAccessLogPolicy`.

| Modulo | Administrador | Tecnico municipal | Juri | Gestor financeiro | Gestor de manutencao | Candidato | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard | view, export, audit | view | view | view, export | view | view | view, audit, export |
| Utilizadores | view, create, update, delete, audit | none | none | none | none | none | view, audit |
| Municipios | view, create, update, delete, audit | view | none | none | none | none | view, audit |
| Programas | view, create, update, delete, publish, audit | view, create, update | view | view | view | view | view, audit |
| Concursos | view, create, update, delete, publish, audit | view, create, update | view, approve, reject | view | view | view | view, audit |
| Registo de adesao | view, update, delete, export, audit | view, create, update, approve, reject, export | none | none | none | view, create, update | view, audit, export |
| Candidaturas | view, create, update, delete, approve, reject, export, audit | view, create, update, approve, reject, export | view, approve, reject | view | none | view, create, update | view, audit, export |
| Agregados | view, create, update, delete, export, audit | view, create, update, export | view | none | none | view, create, update, delete | view, audit, export |
| Rendimentos | view, create, update, delete, export, audit | view, create, update, export | view | view | none | view, create, update, delete | view, audit, export |
| Documentos | view, create, update, delete, approve, reject, export, audit | view, create, update, approve, reject | view | view | view | view, create, update | view, audit, export |
| Elegibilidade | view, create, update, approve, reject, export, audit | view, create, update, approve, reject | view, approve, reject | none | none | view, create (pré-check próprio) | view, audit, export |
| Workflow administrativo | view, create, update, delete, approve, reject, export, audit | view, create, update, approve, reject, export | view, approve, reject | none | none | view, create, update (resposta própria) | view, audit, export |
| Classificacao | view, create, update, approve, reject, export, audit | view, create, update, approve, reject, export | view, approve, reject | none | none | none | view, audit, export |
| Listas | view, create, update, delete, approve, reject, publish, export, audit | view, create, update, publish, export | view, approve, reject, publish | none | none | view | view, audit, export |
| Reclamacoes | view, create, update, delete, approve, reject, export, audit | view, create, update, approve, reject, export | view, approve, reject | none | none | view, create, update | view, audit, export |
| Atribuicao | view, create, update, approve, reject, export, audit | view, create, update, export | view, approve, reject | none | none | view | view, audit, export |
| Contratos | view, create, update, delete, approve, reject, export, audit | view, create, update | view | view, create, update, approve, export | none | view | view, audit, export |
| Pagamentos | view, create, update, delete, approve, reject, export, audit | view | none | view, create, update, approve, reject, export | none | view | view, audit, export |
| Manutencao | view, create, update, delete, approve, reject, export, audit | view, create, update | none | none | view, create, update, approve, reject, export | view, create, update | view, audit, export |
| Vistorias | view, create, update, delete, approve, reject, export, audit | view | none | none | view, create, update, approve, reject, export | view | view, audit, export |
| Notificacoes | view, create, update, delete, publish, export, audit | view, create, update | view | view, create | view, create | view | view, audit, export |
| Relatorios | view, create, update, delete, export, audit | view, export | view | view, export | view, export | none | view, audit, export |
| Auditoria | view, export, audit | none | none | none | none | none | view, export, audit |
| RGPD | view, create, update, delete, approve, reject, export, audit | view, create, update | none | none | none | view, create, update, export | view, export, audit |
| Configuracoes | view, create, update, delete, audit | none | none | none | none | none | view, audit |

## Regras complementares

- `delete` deve ser evitado para dados processuais; preferir anulacao, encerramento ou retencao bloqueada.
- `export` exige registo de motivo, formato, filtros e utilizador.
- `audit` nao permite alterar registos auditados.
- O candidato so pode atuar sobre os proprios dados/processos.
- O juri so deve aceder a concursos onde esteja designado.
- O gestor de manutencao deve receber apenas dados pessoais necessarios a intervencao.
- O gestor financeiro deve receber apenas dados financeiros e contratuais necessarios.

## Aplicação na Sprint 18

- `/backoffice/security` exige role interna, utilizador ativo, MFA de backoffice e log de acesso.
- MFA, audit trail, logs, revisão de permissões, alertas, checklists, backups e campos sensíveis são apenas backoffice.
- `privacy.view/create/update/approve/reject/export/audit` controla finalidades, pedidos RGPD, exportações, retenção e anonimização.
- `audit_logs.view/export/audit` controla consulta de eventos e logs.
- `settings.view/create/update/audit` controla regras de alerta, MFA operacional, checklists e revisões técnicas.
- Candidato acede apenas a `/area-candidato/privacidade`, com ownership por `user_id`.
- Candidato pode criar pedido RGPD próprio, consultar o próprio pedido e descarregar exportação própria.
- Candidato pode dar/retirar consentimento opcional próprio; não pode retirar bases legais obrigatórias.
- Auditor consulta auditoria/RGPD conforme permissões `*.view`, `*.audit` e `privacy.export`, sem ações de decisão operacional.
- Recovery codes, secrets MFA, paths internos e chaves sensíveis nunca são expostos em listagens públicas.

Policies criadas:

- `AuditEventPolicy`, `AccessLogPolicy`, `SensitiveDataAccessLogPolicy`, `PermissionReviewPolicy`, `MfaDevicePolicy`, `ConsentPurposePolicy`, `UserConsentPolicy`, `RetentionPolicyPolicy`, `RetentionExecutionPolicy`, `DataSubjectRequestPolicy`, `DataExportPackagePolicy`, `AnonymizationRequestPolicy`, `EncryptedFieldRegistryPolicy`, `SecurityAlertRulePolicy`, `SecurityAlertPolicy`, `BackupReviewPolicy`, `SecurityChecklistPolicy`.
# Atualização Sprint 19 — Regressão de permissões

A Sprint 19 adicionou `tests/Feature/Security/PermissionMatrixTest.php`, cobrindo:

- administrador com acesso global;
- técnico municipal com acesso operacional sem gestão de utilizadores crítica;
- júri com acesso a classificação/listas sem configurações;
- gestor financeiro sem permissões de classificação;
- gestor de manutenção sem acesso a rendimentos;
- candidato sem acesso ao backoffice;
- auditor sem permissões mutáveis;
- MFA obrigatório em rotas sensíveis de segurança.

Nota técnica: o código atual usa a role `jury`; qualquer documentação externa que refira `jury_member` deve ser normalizada antes de produção ou mapeada explicitamente.

## Atualização Sprint 20 — Portal público

| Módulo | Administrador | Técnico municipal | Júri | Gestor financeiro | Gestor manutenção | Candidato | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Oferta habitacional pública | view, create, update, delete, audit | view, create, update | none | none | view | view público | view, audit |
| Configurações do portal público | view, create, update, delete, audit | none | none | none | none | none | view, audit |

Regras:

- páginas públicas não exigem autenticação e só mostram dados explicitamente publicados;
- edição de ficha pública usa permissões `housing_units.update`;
- imagens e documentos públicos usam permissões `housing_units.update/delete`;
- settings e links usam permissões `settings.view/create/update/delete`;
- candidato não acede ao backoffice do portal público.
## Atualização Sprint 21 — Módulo Simulador

| Módulo | Administrador | Técnico municipal | Júri | Gestor financeiro | Gestor manutenção | Candidato | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Simulador público | view | view | view | view | view | view/create | view |
| Simulações autenticadas | view/audit | view/export | view | view | view | view/create/update próprias | view/audit |
| Pré-preenchimentos | view/audit | none | none | none | none | view/update próprios | audit |
| Renovações de registo | view/audit | view | none | none | none | view/create/update próprias | audit |
| Insights do simulador | view/update/export/audit | view/update/export | view | view | view | none | view/export/audit |

## Atualização Sprint 24 — Backoffice operacional

| Módulo | Administrador | Técnico municipal | Júri | Gestor financeiro | Gestor manutenção | Candidato | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard operacional | view, export, audit | view | view | view | view | none | view, audit |
| Dashboard executivo | view, export, audit | none | none | none | none | none | view, audit |
| Relatórios por candidatura | view, create, export, audit | view, create, export | view | view | none | none | view, audit |
| Dossier documental | view, create, export, audit | view, create, export | view | none | none | none | view, audit |
| Alertas internos | view, create, update, audit | view, update | view | view | view | none | view, audit |
| Minutas de procedimento | view, create, update, publish, audit | view, create, update | view | view | none | none | view, audit |
| Automação assistida de listas | view, create, approve, audit | view, create | view, approve | none | none | none | view, audit |
| Atas de procedimento | view, create, approve, export, audit | view, create | view, approve | none | none | none | view, audit |
| Confirmações de processo | view, create, update, audit | view, create, update | view | none | none | none | view, audit |

Regras:

- candidatos não acedem ao backoffice operacional;
- downloads de relatórios, dossiers, documentos e atas passam por controller autorizado;
- automação de listas não equivale a publicação;
- auditor mantém perfil de consulta/auditoria sem mutações operacionais.
