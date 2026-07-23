# Inventário permission-first de rotas backoffice

Output determinístico do comando `access:inventory-backoffice-routes`.

## Resumo

| Métrica | Valor |
| --- | ---: |
| `route_collection_total` | 1165 |
| `inventoried_routes` | 706 |
| `fixed_role_routes` | 706 |
| `permission_middleware_routes` | 0 |
| `missing_permission_routes` | 706 |
| `missing_policy_routes` | 9 |
| `missing_scope_routes` | 615 |
| `mutations_without_audit` | 82 |
| `residual_routes` | 0 |
| `missing_active_backoffice_routes` | 594 |
| `missing_mfa_backoffice_routes` | 594 |
| `missing_log_backoffice_routes` | 594 |
| `routes_without_detected_tests` | 519 |
| `mixed_context_routes` | 33 |
| `platform_routes` | 0 |
| `feature_decision_pending_routes` | 32 |

## Distribuição por sprint

| Sprint | Rotas |
| --- | ---: |
| 47A | 72 |
| 47B | 102 |
| 47C | 78 |
| 47D | 78 |
| 47E | 58 |
| 47F | 99 |
| 47G | 96 |
| 47H | 123 |

## Rotas

| Rota | Métodos | Contexto | Risco | Permission recomendada | Policy | Scope | Sprint | Confiança |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `backoffice.access-audit.index` | GET | administration_security | critical | `access_audit.view` | `em falta` | candidate | 47A | medium |
| `backoffice.security.alert-rules.store` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.alert-rules.update` | PUT,PATCH | administration_security | critical | `em falta` | `App\Policies\SecurityAlertRulePolicy` | missing | 47A | low |
| `backoffice.security.alerts.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.alerts.resolve` | POST | administration_security | critical | `em falta` | `App\Policies\SecurityAlertPolicy` | missing | 47A | low |
| `backoffice.security.alerts.review` | POST | administration_security | critical | `em falta` | `App\Policies\SecurityAlertPolicy` | missing | 47A | low |
| `backoffice.security.audit.access-logs.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.audit.events.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.audit.events.show` | GET | administration_security | critical | `em falta` | `App\Policies\AuditEventPolicy` | missing | 47A | low |
| `backoffice.security.audit.sensitive-logs.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.backups.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.backups.store` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.checklist-items.update` | PUT,PATCH | administration_security | critical | `em falta` | `em falta` | missing | 47A | low |
| `backoffice.security.checklists.approve` | POST | administration_security | critical | `em falta` | `App\Policies\SecurityChecklistPolicy` | missing | 47A | low |
| `backoffice.security.checklists.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.checklists.show` | GET | administration_security | critical | `em falta` | `App\Policies\SecurityChecklistPolicy` | missing | 47A | low |
| `backoffice.security.checklists.store` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.dashboard` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.encrypted-fields.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.mfa.confirm` | POST | administration_security | critical | `em falta` | `App\Policies\MfaDevicePolicy` | missing | 47A | low |
| `backoffice.security.mfa.disable` | POST | administration_security | critical | `em falta` | `App\Policies\MfaDevicePolicy` | missing | 47A | low |
| `backoffice.security.mfa.enable` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.mfa.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.mfa.recovery-codes.regenerate` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.mfa.verify` | POST | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.security.permission-reviews.complete` | POST | administration_security | critical | `em falta` | `App\Policies\PermissionReviewPolicy` | missing | 47A | low |
| `backoffice.security.permission-reviews.index` | GET | administration_security | critical | `em falta` | `App\Policies\PermissionReviewPolicy` | missing | 47A | low |
| `backoffice.security.permission-reviews.show` | GET | administration_security | critical | `em falta` | `App\Policies\PermissionReviewPolicy` | missing | 47A | low |
| `backoffice.security.permission-reviews.store` | POST | administration_security | critical | `em falta` | `App\Policies\PermissionReviewPolicy` | missing | 47A | low |
| `backoffice.security.privacy.anonymization.approve` | POST | administration_security | critical | `privacy.approve` | `App\Policies\AnonymizationRequestPolicy` | missing | 47A | high |
| `backoffice.security.privacy.anonymization.index` | GET | administration_security | critical | `privacy.view` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.anonymization.run` | POST | administration_security | critical | `em falta` | `App\Policies\AnonymizationRequestPolicy` | missing | 47A | low |
| `backoffice.security.privacy.anonymization.show` | GET | administration_security | critical | `privacy.view` | `App\Policies\AnonymizationRequestPolicy` | missing | 47A | high |
| `backoffice.security.privacy.anonymization.store` | POST | administration_security | critical | `privacy.create` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.exports.download` | GET | administration_security | critical | `em falta` | `App\Policies\DataExportPackagePolicy` | missing | 47A | low |
| `backoffice.security.privacy.exports.show` | GET | administration_security | critical | `privacy.export` | `App\Policies\DataExportPackagePolicy` | missing | 47A | high |
| `backoffice.security.privacy.purposes.index` | GET | administration_security | critical | `privacy.view` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.purposes.store` | POST | administration_security | critical | `privacy.create` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.purposes.update` | PUT,PATCH | administration_security | critical | `privacy.update` | `App\Policies\ConsentPurposePolicy` | missing | 47A | high |
| `backoffice.security.privacy.requests.assign` | POST | administration_security | critical | `em falta` | `App\Policies\DataSubjectRequestPolicy` | missing | 47A | low |
| `backoffice.security.privacy.requests.complete` | POST | administration_security | critical | `em falta` | `App\Policies\DataSubjectRequestPolicy` | missing | 47A | low |
| `backoffice.security.privacy.requests.exports.store` | POST | administration_security | critical | `em falta` | `App\Policies\DataSubjectRequestPolicy` | missing | 47A | low |
| `backoffice.security.privacy.requests.index` | GET | administration_security | critical | `privacy.view` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.requests.reject` | POST | administration_security | critical | `privacy.reject` | `App\Policies\DataSubjectRequestPolicy` | missing | 47A | high |
| `backoffice.security.privacy.requests.show` | GET | administration_security | critical | `privacy.view` | `App\Policies\DataSubjectRequestPolicy` | missing | 47A | high |
| `backoffice.security.privacy.requests.store` | POST | administration_security | critical | `privacy.create` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.retention-executions.approve` | POST | administration_security | critical | `privacy.approve` | `App\Policies\RetentionExecutionPolicy` | missing | 47A | high |
| `backoffice.security.privacy.retention-executions.run` | POST | administration_security | critical | `em falta` | `App\Policies\RetentionExecutionPolicy` | missing | 47A | low |
| `backoffice.security.privacy.retention.index` | GET | administration_security | critical | `privacy.view` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.retention.simulate` | POST | administration_security | critical | `privacy.view` | `App\Policies\RetentionPolicyPolicy` | missing | 47A | high |
| `backoffice.security.privacy.retention.store` | POST | administration_security | critical | `privacy.create` | `em falta` | candidate | 47A | medium |
| `backoffice.security.privacy.retention.update` | PUT,PATCH | administration_security | critical | `privacy.update` | `App\Policies\RetentionPolicyPolicy` | missing | 47A | high |
| `backoffice.security.storage.index` | GET | administration_security | critical | `em falta` | `em falta` | candidate | 47A | low |
| `backoffice.cases.rgpd.show` | GET | rgpd | critical | `em falta` | `App\Policies\DataSubjectRequestPolicy` | candidate | 47A | low |
| `backoffice.teams.create` | GET | users_teams | critical | `teams.create` | `em falta` | missing | 47A | high |
| `backoffice.teams.edit` | GET | users_teams | critical | `teams.update` | `em falta` | missing | 47A | high |
| `backoffice.teams.index` | GET | users_teams | critical | `teams.view` | `em falta` | missing | 47A | high |
| `backoffice.teams.members.remove` | POST | users_teams | critical | `teams.manage_members` | `em falta` | missing | 47A | high |
| `backoffice.teams.members.store` | POST | users_teams | critical | `teams.manage_members` | `em falta` | missing | 47A | high |
| `backoffice.teams.show` | GET | users_teams | critical | `teams.view` | `em falta` | missing | 47A | high |
| `backoffice.teams.store` | POST | users_teams | critical | `teams.create` | `em falta` | missing | 47A | high |
| `backoffice.teams.update` | PUT,PATCH | users_teams | critical | `teams.update` | `em falta` | missing | 47A | high |
| `backoffice.users.create` | GET | users_teams | critical | `users.create` | `em falta` | candidate | 47A | medium |
| `backoffice.users.deactivate` | POST | users_teams | critical | `em falta` | `App\Policies\UserPolicy` | missing | 47A | low |
| `backoffice.users.edit` | GET | users_teams | critical | `users.update` | `App\Policies\UserPolicy` | missing | 47A | high |
| `backoffice.users.force-mfa` | POST | users_teams | critical | `users.view` | `App\Policies\UserPolicy` | missing | 47A | high |
| `backoffice.users.index` | GET | users_teams | critical | `users.view` | `em falta` | candidate | 47A | medium |
| `backoffice.users.reactivate` | POST | users_teams | critical | `em falta` | `App\Policies\UserPolicy` | missing | 47A | low |
| `backoffice.users.reset-password` | POST | users_teams | critical | `users.view` | `App\Policies\UserPolicy` | missing | 47A | high |
| `backoffice.users.show` | GET | users_teams | critical | `users.view` | `App\Policies\UserPolicy` | missing | 47A | high |
| `backoffice.users.store` | POST | users_teams | critical | `users.create` | `em falta` | candidate | 47A | medium |
| `backoffice.users.update` | PUT,PATCH | users_teams | critical | `users.update` | `App\Policies\UserPolicy` | missing | 47A | high |
| `backoffice.administrative-notes.destroy` | DELETE | administrative_processes | high | `administrative_processes.delete` | `App\Policies\AdministrativeProcessNotePolicy` | missing | 47B | high |
| `backoffice.administrative-notes.store` | POST | administrative_processes | high | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47B | high |
| `backoffice.administrative-notes.update` | PUT,PATCH | administrative_processes | high | `administrative_processes.update` | `App\Policies\AdministrativeProcessNotePolicy` | missing | 47B | high |
| `backoffice.administrative-tasks.cancel` | POST | administrative_processes | high | `em falta` | `App\Policies\AdministrativeTaskPolicy` | missing | 47B | low |
| `backoffice.administrative-tasks.complete` | POST | administrative_processes | high | `em falta` | `App\Policies\AdministrativeTaskPolicy` | missing | 47B | low |
| `backoffice.administrative-tasks.index` | GET | administrative_processes | high | `administrative_processes.view` | `App\Policies\AdministrativeTaskPolicy` | missing | 47B | high |
| `backoffice.administrative-tasks.store` | POST | administrative_processes | high | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47B | high |
| `backoffice.administrative-tasks.update` | PUT,PATCH | administrative_processes | high | `administrative_processes.update` | `App\Policies\AdministrativeTaskPolicy` | missing | 47B | high |
| `backoffice.application-inconsistencies.index` | GET | administrative_processes | high | `administrative_processes.view` | `App\Policies\ApplicationSimulationInconsistencyPolicy` | missing | 47B | high |
| `backoffice.application-inconsistencies.resolve` | POST | administrative_processes | high | `administrative_processes.view` | `App\Policies\ApplicationSimulationInconsistencyPolicy` | missing | 47B | high |
| `backoffice.correction-requests.cancel` | POST | administrative_processes | high | `em falta` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | low |
| `backoffice.correction-requests.close` | POST | administrative_processes | high | `administrative_processes.update` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-requests.create` | GET | administrative_processes | high | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47B | high |
| `backoffice.correction-requests.edit` | GET | administrative_processes | high | `administrative_processes.update` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-requests.index` | GET | administrative_processes | high | `administrative_processes.view` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47B | high |
| `backoffice.correction-requests.issue` | POST | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-requests.mark-overdue` | POST | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-requests.show` | GET | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-requests.store` | POST | administrative_processes | high | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47B | high |
| `backoffice.correction-requests.update` | PUT,PATCH | administrative_processes | high | `administrative_processes.update` | `App\Policies\CorrectionRequestPolicy` | missing | 47B | high |
| `backoffice.correction-responses.accept` | POST | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionResponsePolicy` | missing | 47B | high |
| `backoffice.correction-responses.reject` | POST | administrative_processes | high | `administrative_processes.reject` | `App\Policies\CorrectionResponsePolicy` | missing | 47B | high |
| `backoffice.correction-responses.request-more-information` | POST | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionResponsePolicy` | missing | 47B | high |
| `backoffice.correction-responses.show` | GET | administrative_processes | high | `administrative_processes.view` | `App\Policies\CorrectionResponsePolicy` | missing | 47B | high |
| `applications.create` | GET | applications | high | `applications.create` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.destroy` | DELETE | applications | high | `applications.delete` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.edit` | GET | applications | high | `applications.update` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.index` | GET | applications | high | `applications.view` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.show` | GET | applications | high | `applications.view` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.store` | POST | applications | high | `applications.create` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `applications.update` | PUT,PATCH | applications | high | `applications.update` | `App\Policies\HousingApplicationPolicy` | missing | 47B | high |
| `backoffice.data-reuse.index` | GET | applications | high | `applications.view` | `App\Policies\FutureApplicationDataReusePolicy` | missing | 47B | high |
| `backoffice.simulator.configuration.edit` | GET | applications | high | `simulator.update` | `App\Policies\SimulatorConfigurationPolicy` | missing | 47B | high |
| `backoffice.simulator.configuration.update` | PUT,PATCH | applications | high | `simulator.update` | `App\Policies\SimulatorConfigurationPolicy` | missing | 47B | high |
| `backoffice.simulator.insights.index` | GET | applications | high | `simulator.view` | `em falta` | candidate | 47B | medium |
| `backoffice.simulator.insights.show` | GET | applications | high | `simulator.view` | `App\Policies\SimulationSessionPolicy` | missing | 47B | high |
| `citizens.create` | GET | applications | high | `citizens.create` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.destroy` | DELETE | applications | high | `citizens.delete` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.edit` | GET | applications | high | `citizens.update` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.index` | GET | applications | high | `citizens.view` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.show` | GET | applications | high | `citizens.view` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.store` | POST | applications | high | `citizens.create` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `citizens.update` | PUT,PATCH | applications | high | `citizens.update` | `App\Policies\CitizenPolicy` | missing | 47B | high |
| `households.create` | GET | applications | high | `households.create` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.destroy` | DELETE | applications | high | `households.delete` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.edit` | GET | applications | high | `households.update` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.index` | GET | applications | high | `households.view` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.show` | GET | applications | high | `households.view` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.store` | POST | applications | high | `households.create` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `households.update` | PUT,PATCH | applications | high | `households.update` | `App\Policies\HouseholdPolicy` | missing | 47B | high |
| `backoffice.cases.documents.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentSubmissionPolicy` | confirmed | 47B | high |
| `backoffice.contracts.documents.download` | GET | documents | critical | `em falta` | `App\Policies\LeaseContractDocumentPolicy` | missing | 47B | low |
| `backoffice.contracts.documents.generate` | POST | documents | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47B | low |
| `backoffice.document-ai.assistant.index` | GET | documents | critical | `documents.view` | `em falta` | candidate | 47B | medium |
| `backoffice.document-ai.assistant.recalculate` | POST | documents | critical | `em falta` | `App\Policies\DocumentAiAnalysisPolicy` | missing | 47B | low |
| `backoffice.document-ai.assistant.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentAiAnalysisPolicy` | missing | 47B | high |
| `backoffice.document-ai.assistant.suggestions.accept` | POST | documents | critical | `documents.view` | `App\Policies\DocumentAiSuggestionPolicy` | missing | 47B | high |
| `backoffice.document-ai.assistant.suggestions.dismiss` | POST | documents | critical | `documents.view` | `App\Policies\DocumentAiSuggestionPolicy` | missing | 47B | high |
| `backoffice.document-ai.assistant.suggestions.update` | PUT | documents | critical | `documents.update` | `App\Policies\DocumentAiSuggestionPolicy` | missing | 47B | high |
| `backoffice.document-ai.extractions.index` | GET | documents | critical | `documents.view` | `em falta` | candidate | 47B | medium |
| `backoffice.document-ai.extractions.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentAiAnalysisPolicy` | missing | 47B | high |
| `backoffice.document-ai.fields.review` | POST | documents | critical | `documents.update` | `App\Policies\DocumentAiFieldPolicy` | missing | 47B | high |
| `backoffice.document-ai.validations.index` | GET | documents | critical | `documents.view` | `App\Policies\DocumentAiValidationPolicy` | missing | 47B | high |
| `backoffice.document-ai.validations.manual-review` | POST | documents | critical | `documents.update` | `App\Policies\DocumentAiValidationPolicy` | missing | 47B | high |
| `backoffice.document-ai.validations.rerun` | POST | documents | critical | `em falta` | `App\Policies\ApplicationPolicy` | confirmed | 47B | low |
| `backoffice.document-ai.validations.show` | GET | documents | critical | `documents.view` | `App\Policies\ApplicationPolicy` | confirmed | 47B | high |
| `backoffice.document-ai.validations.validation` | GET | documents | critical | `documents.view` | `App\Policies\DocumentAiValidationPolicy` | missing | 47B | high |
| `backoffice.document-template-versions.activate` | POST | documents | critical | `em falta` | `App\Policies\DocumentTemplateVersionPolicy` | missing | 47B | low |
| `backoffice.document-template-versions.approve` | POST | documents | critical | `documents.approve` | `App\Policies\DocumentTemplateVersionPolicy` | missing | 47B | high |
| `backoffice.document-template-versions.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentTemplateVersionPolicy` | missing | 47B | high |
| `backoffice.document-template-versions.store` | POST | documents | critical | `documents.create` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.archive` | POST | documents | critical | `documents.update` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.create` | GET | documents | critical | `documents.create` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.edit` | GET | documents | critical | `documents.update` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.index` | GET | documents | critical | `documents.view` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.preview` | GET,POST | documents | critical | `documents.update` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.store` | POST | documents | critical | `documents.create` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.document-templates.update` | PUT,PATCH | documents | critical | `documents.update` | `App\Policies\DocumentTemplatePolicy` | missing | 47B | high |
| `backoffice.finance.annual-document-updates.accept` | POST | documents | critical | `documents.view` | `App\Policies\AnnualDocumentUpdateRequestPolicy` | missing | 47B | high |
| `backoffice.finance.annual-document-updates.index` | GET | documents | critical | `documents.view` | `App\Policies\AnnualDocumentUpdateRequestPolicy` | missing | 47B | high |
| `backoffice.finance.annual-document-updates.reject` | POST | documents | critical | `documents.reject` | `App\Policies\AnnualDocumentUpdateRequestPolicy` | missing | 47B | high |
| `backoffice.finance.annual-document-updates.show` | GET | documents | critical | `documents.view` | `App\Policies\AnnualDocumentUpdateRequestPolicy` | missing | 47B | high |
| `backoffice.finance.annual-document-updates.store` | POST | documents | critical | `documents.create` | `App\Policies\AnnualDocumentUpdateRequestPolicy` | missing | 47B | high |
| `backoffice.generated-documents.download` | GET | documents | critical | `em falta` | `App\Policies\GeneratedProcedureDocumentPolicy` | missing | 47B | low |
| `backoffice.generated-documents.index` | GET | documents | critical | `documents.view` | `App\Policies\GeneratedProcedureDocumentPolicy` | missing | 47B | high |
| `backoffice.generated-documents.issue` | POST | documents | critical | `documents.view` | `App\Policies\GeneratedProcedureDocumentPolicy` | missing | 47B | high |
| `backoffice.generated-documents.show` | GET | documents | critical | `documents.view` | `App\Policies\GeneratedProcedureDocumentPolicy` | missing | 47B | high |
| `backoffice.official-documents.cancel` | POST | documents | critical | `em falta` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | low |
| `backoffice.official-documents.download` | GET | documents | critical | `em falta` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | low |
| `backoffice.official-documents.generate` | POST | documents | critical | `em falta` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | low |
| `backoffice.official-documents.index` | GET | documents | critical | `documents.view` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | high |
| `backoffice.official-documents.issue` | POST | documents | critical | `documents.view` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | high |
| `backoffice.official-documents.show` | GET | documents | critical | `documents.view` | `App\Policies\GeneratedOfficialDocumentPolicy` | missing | 47B | high |
| `backoffice.procedure-templates.documents.generate` | POST | documents | critical | `em falta` | `App\Policies\ProcedureTemplatePolicy` | missing | 47B | low |
| `documents.create` | GET | documents | critical | `documents.create` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.destroy` | DELETE | documents | critical | `documents.delete` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.edit` | GET | documents | critical | `documents.update` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.index` | GET | documents | critical | `documents.view` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.show` | GET | documents | critical | `documents.view` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.store` | POST | documents | critical | `documents.create` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `documents.update` | PUT,PATCH | documents | critical | `documents.update` | `App\Policies\DocumentPolicy` | missing | 47B | high |
| `backoffice.administrative-decisions.approve` | POST | decisions | critical | `administrative_processes.approve` | `App\Policies\AdministrativeDecisionPolicy` | missing | 47C | high |
| `backoffice.administrative-decisions.cancel` | POST | decisions | critical | `em falta` | `App\Policies\AdministrativeDecisionPolicy` | missing | 47C | low |
| `backoffice.administrative-decisions.create-admission` | GET | decisions | critical | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47C | high |
| `backoffice.administrative-decisions.create-non-admission` | GET | decisions | critical | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47C | high |
| `backoffice.administrative-decisions.show` | GET | decisions | critical | `administrative_processes.view` | `App\Policies\AdministrativeDecisionPolicy` | missing | 47C | high |
| `backoffice.administrative-decisions.store-admission` | POST | decisions | critical | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47C | high |
| `backoffice.administrative-decisions.store-non-admission` | POST | decisions | critical | `administrative_processes.create` | `App\Policies\AdministrativeProcessPolicy` | confirmed | 47C | high |
| `backoffice.complaint-decisions.approve` | POST | decisions | critical | `administrative_processes.approve` | `App\Policies\ComplaintDecisionPolicy` | missing | 47C | high |
| `backoffice.complaint-decisions.cancel` | POST | decisions | critical | `em falta` | `App\Policies\ComplaintDecisionPolicy` | missing | 47C | low |
| `backoffice.complaint-decisions.create` | GET | decisions | critical | `administrative_processes.create` | `App\Policies\ComplaintPolicy` | missing | 47C | high |
| `backoffice.complaint-decisions.show` | GET | decisions | critical | `administrative_processes.view` | `App\Policies\ComplaintDecisionPolicy` | missing | 47C | high |
| `backoffice.complaint-decisions.store` | POST | decisions | critical | `administrative_processes.create` | `App\Policies\ComplaintPolicy` | missing | 47C | high |
| `backoffice.eligibility.criteria.activate` | POST | eligibility | high | `em falta` | `App\Policies\EligibilityCriterionPolicy` | missing | 47C | low |
| `backoffice.eligibility.criteria.create` | GET | eligibility | high | `eligibility.create` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.criteria.edit` | GET | eligibility | high | `eligibility.update` | `App\Policies\EligibilityCriterionPolicy` | missing | 47C | high |
| `backoffice.eligibility.criteria.inactivate` | POST | eligibility | high | `em falta` | `App\Policies\EligibilityCriterionPolicy` | missing | 47C | low |
| `backoffice.eligibility.criteria.index` | GET | eligibility | high | `eligibility.view` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.criteria.store` | POST | eligibility | high | `eligibility.create` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.criteria.update` | PUT,PATCH | eligibility | high | `eligibility.update` | `App\Policies\EligibilityCriterionPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.activate` | POST | eligibility | high | `em falta` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | low |
| `backoffice.eligibility.rule-sets.archive` | POST | eligibility | high | `eligibility.update` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.create` | GET | eligibility | high | `eligibility.create` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.duplicate` | POST | eligibility | high | `eligibility.create` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.edit` | GET | eligibility | high | `eligibility.update` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.index` | GET | eligibility | high | `eligibility.view` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.show` | GET | eligibility | high | `eligibility.view` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.store` | POST | eligibility | high | `eligibility.create` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.eligibility.rule-sets.update` | PUT,PATCH | eligibility | high | `eligibility.update` | `App\Policies\EligibilityRuleSetPolicy` | missing | 47C | high |
| `backoffice.document-ai.assistant.score` | GET | scoring | high | `scoring.view` | `App\Policies\DocumentAiScorePolicy` | missing | 47C | high |
| `backoffice.document-ai.classifications.index` | GET | scoring | high | `scoring.view` | `em falta` | candidate | 47C | medium |
| `backoffice.document-ai.classifications.manual-review` | POST | scoring | high | `scoring.update` | `App\Policies\DocumentAiAnalysisPolicy` | missing | 47C | high |
| `backoffice.document-ai.classifications.show` | GET | scoring | high | `scoring.view` | `App\Policies\DocumentAiAnalysisPolicy` | missing | 47C | high |
| `backoffice.lottery-draws.ranking.update` | POST | scoring | high | `scoring.view` | `App\Policies\LotteryDrawPolicy` | missing | 47C | high |
| `backoffice.scoring.application-scores.index` | GET | scoring | high | `scoring.view` | `App\Policies\ApplicationScorePolicy` | missing | 47C | high |
| `backoffice.scoring.application-scores.lock` | POST | scoring | high | `em falta` | `App\Policies\ApplicationScorePolicy` | missing | 47C | low |
| `backoffice.scoring.application-scores.manual-review` | GET | scoring | high | `scoring.update` | `App\Policies\ApplicationScorePolicy` | missing | 47C | high |
| `backoffice.scoring.application-scores.manual-review.update` | PUT,PATCH | scoring | high | `scoring.update` | `App\Policies\ApplicationScorePolicy` | missing | 47C | high |
| `backoffice.scoring.application-scores.show` | GET | scoring | high | `scoring.view` | `App\Policies\ApplicationScorePolicy` | missing | 47C | high |
| `backoffice.scoring.criteria.activate` | POST | scoring | high | `em falta` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | low |
| `backoffice.scoring.criteria.create` | GET | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.criteria.edit` | GET | scoring | high | `scoring.update` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | high |
| `backoffice.scoring.criteria.inactivate` | POST | scoring | high | `em falta` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | low |
| `backoffice.scoring.criteria.index` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.criteria.store` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.criteria.update` | PUT,PATCH | scoring | high | `scoring.update` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | high |
| `backoffice.scoring.ranking-snapshots.archive` | POST | scoring | high | `scoring.update` | `App\Policies\RankingSnapshotPolicy` | missing | 47C | high |
| `backoffice.scoring.ranking-snapshots.index` | GET | scoring | high | `scoring.view` | `App\Policies\RankingSnapshotPolicy` | missing | 47C | high |
| `backoffice.scoring.ranking-snapshots.lock` | POST | scoring | high | `em falta` | `App\Policies\RankingSnapshotPolicy` | missing | 47C | low |
| `backoffice.scoring.ranking-snapshots.show` | GET | scoring | high | `scoring.view` | `App\Policies\RankingSnapshotPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.activate` | POST | scoring | high | `em falta` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | low |
| `backoffice.scoring.rule-sets.archive` | POST | scoring | high | `scoring.update` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.create` | GET | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.duplicate` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.edit` | GET | scoring | high | `scoring.update` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.index` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.show` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.store` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rule-sets.update` | PUT,PATCH | scoring | high | `scoring.update` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.rules.create` | GET | scoring | high | `scoring.create` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | high |
| `backoffice.scoring.rules.destroy` | DELETE | scoring | high | `em falta` | `App\Policies\ScoringRulePolicy` | missing | 47C | low |
| `backoffice.scoring.rules.edit` | GET | scoring | high | `scoring.update` | `App\Policies\ScoringRulePolicy` | missing | 47C | high |
| `backoffice.scoring.rules.index` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | high |
| `backoffice.scoring.rules.store` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringCriterionPolicy` | missing | 47C | high |
| `backoffice.scoring.rules.update` | PUT,PATCH | scoring | high | `scoring.update` | `App\Policies\ScoringRulePolicy` | missing | 47C | high |
| `backoffice.scoring.runs.cancel` | POST | scoring | high | `em falta` | `App\Policies\ScoringRunPolicy` | missing | 47C | low |
| `backoffice.scoring.runs.create` | GET | scoring | high | `scoring.create` | `App\Policies\ScoringRunPolicy` | missing | 47C | high |
| `backoffice.scoring.runs.index` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRunPolicy` | missing | 47C | high |
| `backoffice.scoring.runs.lock` | POST | scoring | high | `em falta` | `App\Policies\ScoringRunPolicy` | missing | 47C | low |
| `backoffice.scoring.runs.run` | POST | scoring | high | `em falta` | `App\Policies\ScoringRunPolicy` | missing | 47C | low |
| `backoffice.scoring.runs.show` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRunPolicy` | missing | 47C | high |
| `backoffice.scoring.runs.store` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringRunPolicy` | missing | 47C | high |
| `backoffice.scoring.tie-breakers.activate` | POST | scoring | high | `em falta` | `App\Policies\TieBreakerRulePolicy` | missing | 47C | low |
| `backoffice.scoring.tie-breakers.create` | GET | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.tie-breakers.edit` | GET | scoring | high | `scoring.update` | `App\Policies\TieBreakerRulePolicy` | missing | 47C | high |
| `backoffice.scoring.tie-breakers.inactivate` | POST | scoring | high | `em falta` | `App\Policies\TieBreakerRulePolicy` | missing | 47C | low |
| `backoffice.scoring.tie-breakers.index` | GET | scoring | high | `scoring.view` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.tie-breakers.store` | POST | scoring | high | `scoring.create` | `App\Policies\ScoringRuleSetPolicy` | missing | 47C | high |
| `backoffice.scoring.tie-breakers.update` | PUT,PATCH | scoring | high | `scoring.update` | `App\Policies\TieBreakerRulePolicy` | missing | 47C | high |
| `backoffice.post-draw-reports.download` | GET | allocations | critical | `em falta` | `App\Policies\PostDrawReportPolicy` | missing | 47D | low |
| `backoffice.contest-closures.show` | GET | allocations | high | `contests.view` | `App\Policies\ContestClosurePolicy` | missing | 47D | high |
| `backoffice.draw-convocations.index` | GET | allocations | high | `allocations.view` | `App\Policies\DrawConvocationPolicy` | missing | 47D | high |
| `backoffice.draw-convocations.send` | POST | allocations | high | `allocations.view` | `App\Policies\DrawConvocationPolicy` | missing | 47D | high |
| `backoffice.draw-convocations.show` | GET | allocations | high | `allocations.view` | `App\Policies\DrawConvocationPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.attendance.bulk-store` | POST | allocations | high | `allocations.create` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.attendance.index` | GET | allocations | high | `allocations.view` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.attendance.store` | POST | allocations | high | `allocations.create` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.cancel` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-draws.convocations.generate` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-draws.create` | GET | allocations | high | `allocations.create` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.edit` | GET | allocations | high | `allocations.update` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.index` | GET | allocations | high | `allocations.view` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.participants.load` | POST | allocations | high | `allocations.view` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.participants.lock` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-draws.post-draw-report.generate` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-draws.results.index` | GET | allocations | high | `allocations.view` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.run` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-draws.show` | GET | allocations | high | `allocations.view` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.store` | POST | allocations | high | `allocations.create` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.update` | PUT,PATCH | allocations | high | `allocations.update` | `App\Policies\LotteryDrawPolicy` | missing | 47D | high |
| `backoffice.lottery-draws.validate` | POST | allocations | high | `em falta` | `App\Policies\LotteryDrawPolicy` | missing | 47D | low |
| `backoffice.lottery-results.winner.store` | POST | allocations | high | `allocations.create` | `App\Policies\LotteryResultPolicy` | missing | 47D | high |
| `backoffice.post-draw-reports.show` | GET | allocations | high | `allocations.view` | `App\Policies\PostDrawReportPolicy` | missing | 47D | high |
| `backoffice.withdrawals.index` | GET | allocations | high | `allocations.view` | `App\Policies\ControlledWithdrawalPolicy` | missing | 47D | high |
| `backoffice.withdrawals.process` | POST | allocations | high | `allocations.view` | `App\Policies\ControlledWithdrawalPolicy` | missing | 47D | high |
| `backoffice.withdrawals.show` | GET | allocations | high | `allocations.view` | `App\Policies\ControlledWithdrawalPolicy` | missing | 47D | high |
| `backoffice.additional-information-requests.close` | POST | complaints | high | `complaints.update` | `App\Policies\AdditionalInformationRequestPolicy` | missing | 47D | high |
| `backoffice.additional-information-requests.create` | GET | complaints | high | `complaints.create` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.additional-information-requests.mark-overdue` | POST | complaints | high | `complaints.view` | `App\Policies\AdditionalInformationRequestPolicy` | missing | 47D | high |
| `backoffice.additional-information-requests.show` | GET | complaints | high | `complaints.view` | `App\Policies\AdditionalInformationRequestPolicy` | missing | 47D | high |
| `backoffice.additional-information-requests.store` | POST | complaints | high | `complaints.create` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.cases.complaints.show` | GET | complaints | high | `complaints.view` | `App\Policies\ComplaintPolicy` | candidate | 47D | high |
| `backoffice.complaints.assign` | POST | complaints | high | `em falta` | `App\Policies\ComplaintPolicy` | missing | 47D | low |
| `backoffice.complaints.close` | POST | complaints | high | `complaints.update` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.complaints.index` | GET | complaints | high | `complaints.view` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.complaints.mark-received` | POST | complaints | high | `complaints.view` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.complaints.reviews.store` | POST | complaints | high | `complaints.create` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.complaints.show` | GET | complaints | high | `complaints.view` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.complaints.start-review` | POST | complaints | high | `complaints.update` | `App\Policies\ComplaintPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.close-complaint-period` | POST | complaints | high | `complaints.update` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.open-complaint-period` | POST | complaints | high | `complaints.view` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.hearing-submissions.accept` | POST | hearings | high | `complaints.view` | `App\Policies\HearingSubmissionPolicy` | missing | 47D | high |
| `backoffice.hearing-submissions.reject` | POST | hearings | high | `complaints.reject` | `App\Policies\HearingSubmissionPolicy` | missing | 47D | high |
| `backoffice.hearing-submissions.show` | GET | hearings | high | `complaints.view` | `App\Policies\HearingSubmissionPolicy` | missing | 47D | high |
| `backoffice.hearings.cancel` | POST | hearings | high | `em falta` | `App\Policies\HearingPolicy` | missing | 47D | low |
| `backoffice.hearings.close` | POST | hearings | high | `complaints.update` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.hearings.create` | GET | hearings | high | `complaints.create` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.hearings.index` | GET | hearings | high | `complaints.view` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.hearings.issue` | POST | hearings | high | `complaints.view` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.hearings.show` | GET | hearings | high | `complaints.view` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.hearings.store` | POST | hearings | high | `complaints.create` | `App\Policies\HearingPolicy` | missing | 47D | high |
| `backoffice.preliminary-hearings.decide` | POST | hearings | high | `complaints.view` | `App\Policies\HearingSubmissionPolicy` | missing | 47D | high |
| `backoffice.preliminary-hearings.index` | GET | hearings | high | `complaints.view` | `em falta` | candidate | 47D | medium |
| `backoffice.preliminary-hearings.show` | GET | hearings | high | `complaints.view` | `App\Policies\HearingSubmissionPolicy` | missing | 47D | high |
| `backoffice.lists.automation-runs.approve` | POST | lists | high | `public_lists.approve` | `App\Policies\ListAutomationRunPolicy` | missing | 47D | high |
| `backoffice.lists.automation-runs.show` | GET | lists | high | `public_lists.view` | `App\Policies\ListAutomationRunPolicy` | missing | 47D | high |
| `backoffice.lists.automation.definitive` | POST | lists | high | `em falta` | `App\Policies\ContestPolicy` | missing | 47D | low |
| `backoffice.lists.automation.index` | GET | lists | high | `contests.view` | `App\Policies\ContestPolicy` | missing | 47D | high |
| `backoffice.lists.automation.provisional` | POST | lists | high | `em falta` | `App\Policies\ContestPolicy` | missing | 47D | low |
| `backoffice.lists.definitive.approve` | POST | lists | high | `public_lists.approve` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.archive` | POST | lists | high | `public_lists.update` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.create` | GET | lists | high | `public_lists.create` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.index` | GET | lists | high | `public_lists.view` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.lock` | POST | lists | high | `em falta` | `App\Policies\DefinitiveListPolicy` | missing | 47D | low |
| `backoffice.lists.definitive.publish` | POST | lists | high | `public_lists.publish` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.review` | POST | lists | high | `public_lists.update` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.show` | GET | lists | high | `public_lists.view` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.definitive.store` | POST | lists | high | `public_lists.create` | `App\Policies\DefinitiveListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.approve` | POST | lists | high | `public_lists.approve` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.archive` | POST | lists | high | `public_lists.update` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.cancel` | POST | lists | high | `em falta` | `App\Policies\ProvisionalListPolicy` | missing | 47D | low |
| `backoffice.lists.provisional.create` | GET | lists | high | `public_lists.create` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.index` | GET | lists | high | `public_lists.view` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.publish` | POST | lists | high | `public_lists.publish` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.review` | POST | lists | high | `public_lists.update` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.show` | GET | lists | high | `public_lists.view` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.lists.provisional.store` | POST | lists | high | `public_lists.create` | `App\Policies\ProvisionalListPolicy` | missing | 47D | high |
| `backoffice.cases.contracts.show` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractPolicy` | candidate | 47E | high |
| `backoffice.contracts.clauses.activate` | POST | contracts | critical | `em falta` | `App\Policies\ContractClausePolicy` | missing | 47E | low |
| `backoffice.contracts.clauses.archive` | POST | contracts | critical | `contracts.update` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.create` | GET | contracts | critical | `contracts.create` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.edit` | GET | contracts | critical | `contracts.update` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.index` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.show` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.store` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.clauses.update` | PUT,PATCH | contracts | critical | `contracts.update` | `App\Policies\ContractClausePolicy` | missing | 47E | high |
| `backoffice.contracts.leases.activate` | POST | contracts | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47E | low |
| `backoffice.contracts.leases.cancel` | POST | contracts | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47E | low |
| `backoffice.contracts.leases.create` | GET | contracts | critical | `contracts.create` | `em falta` | candidate | 47E | medium |
| `backoffice.contracts.leases.edit` | GET | contracts | critical | `contracts.update` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.leases.index` | GET | contracts | critical | `contracts.view` | `em falta` | candidate | 47E | medium |
| `backoffice.contracts.leases.issue` | POST | contracts | critical | `contracts.view` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.leases.show` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.leases.store` | POST | contracts | critical | `contracts.create` | `em falta` | candidate | 47E | medium |
| `backoffice.contracts.leases.suspend` | POST | contracts | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47E | low |
| `backoffice.contracts.leases.terminate` | POST | contracts | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47E | low |
| `backoffice.contracts.leases.update` | PUT,PATCH | contracts | critical | `contracts.update` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.signatures.store` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.templates.activate` | POST | contracts | critical | `em falta` | `App\Policies\ContractTemplatePolicy` | missing | 47E | low |
| `backoffice.contracts.templates.archive` | POST | contracts | critical | `contracts.update` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.create` | GET | contracts | critical | `contracts.create` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.duplicate` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.edit` | GET | contracts | critical | `contracts.update` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.index` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.show` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.store` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.templates.update` | PUT,PATCH | contracts | critical | `contracts.update` | `App\Policies\ContractTemplatePolicy` | missing | 47E | high |
| `backoffice.contracts.validations.approve` | POST | contracts | critical | `contracts.approve` | `App\Policies\LeaseContractValidationPolicy` | missing | 47E | high |
| `backoffice.contracts.validations.reject` | POST | contracts | critical | `contracts.reject` | `App\Policies\LeaseContractValidationPolicy` | missing | 47E | high |
| `backoffice.contracts.validations.store` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.key-handovers.cancel` | POST | contracts | critical | `em falta` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | low |
| `backoffice.key-handovers.complete` | POST | contracts | critical | `em falta` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | low |
| `backoffice.key-handovers.create` | GET | contracts | critical | `contracts.create` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | high |
| `backoffice.key-handovers.index` | GET | contracts | critical | `contracts.view` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | high |
| `backoffice.key-handovers.show` | GET | contracts | critical | `contracts.view` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | high |
| `backoffice.key-handovers.store` | POST | contracts | critical | `contracts.create` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | high |
| `backoffice.key-handovers.update` | PUT,PATCH | contracts | critical | `contracts.update` | `App\Policies\KeyHandoverAppointmentPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.charge-runs.index` | GET | contracts | critical | `contracts.view` | `App\Policies\TenantChargeRunPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.charge-runs.show` | GET | contracts | critical | `contracts.view` | `App\Policies\TenantChargeRunPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.charge-runs.store` | POST | contracts | critical | `contracts.create` | `App\Policies\TenantChargeRunPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.communications.index` | GET | contracts | critical | `contracts.view` | `App\Policies\TenantCommunicationPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.communications.messages.store` | POST | contracts | critical | `contracts.view` | `App\Policies\TenantCommunicationPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.communications.show` | GET | contracts | critical | `contracts.view` | `App\Policies\TenantCommunicationPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.communications.store` | POST | contracts | critical | `contracts.create` | `App\Policies\TenantCommunicationPolicy` | missing | 47E | high |
| `backoffice.tenant-operations.dashboard` | GET | contracts | critical | `contracts.view` | `em falta` | candidate | 47E | medium |
| `backoffice.tenant-operations.maintenance-reports.index` | GET | contracts | critical | `contracts.view` | `em falta` | candidate | 47E | medium |
| `backoffice.tenant-transitions.index` | GET | contracts | critical | `contracts.view` | `App\Policies\TenantTransitionPolicy` | missing | 47E | high |
| `backoffice.tenant-transitions.run` | POST | contracts | critical | `em falta` | `App\Policies\TenantTransitionPolicy` | missing | 47E | low |
| `contracts.create` | GET | contracts | critical | `contracts.create` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.destroy` | DELETE | contracts | critical | `contracts.delete` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.edit` | GET | contracts | critical | `contracts.update` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.index` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.show` | GET | contracts | critical | `contracts.view` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.store` | POST | contracts | critical | `contracts.create` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `contracts.update` | PUT,PATCH | contracts | critical | `contracts.update` | `App\Policies\ContractPolicy` | missing | 47E | high |
| `backoffice.contracts.deposits.cancel` | POST | finance | critical | `em falta` | `App\Policies\ContractDepositPolicy` | missing | 47F | low |
| `backoffice.contracts.deposits.paid` | POST | finance | critical | `finance.view` | `App\Policies\ContractDepositPolicy` | missing | 47F | high |
| `backoffice.contracts.deposits.requested` | POST | finance | critical | `finance.view` | `App\Policies\ContractDepositPolicy` | missing | 47F | high |
| `backoffice.contracts.deposits.show` | GET | finance | critical | `finance.view` | `App\Policies\ContractDepositPolicy` | missing | 47F | high |
| `backoffice.contracts.deposits.waived` | POST | finance | critical | `finance.view` | `App\Policies\ContractDepositPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-calculations.approve` | POST | finance | critical | `finance.approve` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-calculations.calculate` | POST | finance | critical | `finance.view` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-calculations.index` | GET | finance | critical | `finance.view` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-calculations.recalculate` | POST | finance | critical | `em falta` | `App\Policies\RentCalculationPolicy` | missing | 47F | low |
| `backoffice.contracts.rent-calculations.reject` | POST | finance | critical | `finance.reject` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-calculations.show` | GET | finance | critical | `finance.view` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-manual-reviews.approve` | POST | finance | critical | `finance.approve` | `App\Policies\RentManualReviewPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-manual-reviews.reject` | POST | finance | critical | `finance.reject` | `App\Policies\RentManualReviewPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-manual-reviews.store` | POST | finance | critical | `finance.create` | `App\Policies\RentCalculationPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.activate` | POST | finance | critical | `em falta` | `App\Policies\RentRuleSetPolicy` | missing | 47F | low |
| `backoffice.contracts.rent-rule-sets.archive` | POST | finance | critical | `finance.update` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.create` | GET | finance | critical | `finance.create` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.duplicate` | POST | finance | critical | `finance.create` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.edit` | GET | finance | critical | `finance.update` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.index` | GET | finance | critical | `finance.view` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.show` | GET | finance | critical | `finance.view` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.store` | POST | finance | critical | `finance.create` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rule-sets.update` | PUT,PATCH | finance | critical | `finance.update` | `App\Policies\RentRuleSetPolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rules.create` | GET | finance | critical | `finance.create` | `App\Policies\RentRulePolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rules.edit` | GET | finance | critical | `finance.update` | `App\Policies\RentRulePolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rules.index` | GET | finance | critical | `finance.view` | `App\Policies\RentRulePolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rules.store` | POST | finance | critical | `finance.create` | `App\Policies\RentRulePolicy` | missing | 47F | high |
| `backoffice.contracts.rent-rules.update` | PUT,PATCH | finance | critical | `finance.update` | `App\Policies\RentRulePolicy` | missing | 47F | high |
| `backoffice.finance.accounts.detect-arrears` | POST | finance | critical | `finance.view` | `App\Policies\TenantFinancialAccountPolicy` | missing | 47F | high |
| `backoffice.finance.accounts.index` | GET | finance | critical | `finance.view` | `App\Policies\TenantFinancialAccountPolicy` | missing | 47F | high |
| `backoffice.finance.accounts.show` | GET | finance | critical | `finance.view` | `App\Policies\TenantFinancialAccountPolicy` | missing | 47F | high |
| `backoffice.finance.accounts.statement` | GET | finance | critical | `finance.view` | `App\Policies\TenantFinancialAccountPolicy` | missing | 47F | high |
| `backoffice.finance.accounts.store` | POST | finance | critical | `finance.create` | `App\Policies\TenantFinancialAccountPolicy` | missing | 47F | high |
| `backoffice.finance.arrears.close` | POST | finance | critical | `finance.update` | `App\Policies\ArrearPolicy` | missing | 47F | high |
| `backoffice.finance.arrears.index` | GET | finance | critical | `finance.view` | `App\Policies\ArrearPolicy` | missing | 47F | high |
| `backoffice.finance.arrears.show` | GET | finance | critical | `finance.view` | `App\Policies\ArrearPolicy` | missing | 47F | high |
| `backoffice.finance.default-notices.cancel` | POST | finance | critical | `em falta` | `App\Policies\DefaultNoticePolicy` | missing | 47F | low |
| `backoffice.finance.default-notices.create` | GET | finance | critical | `finance.create` | `App\Policies\DefaultNoticePolicy` | missing | 47F | high |
| `backoffice.finance.default-notices.index` | GET | finance | critical | `finance.view` | `App\Policies\DefaultNoticePolicy` | missing | 47F | high |
| `backoffice.finance.default-notices.issue` | POST | finance | critical | `finance.view` | `App\Policies\DefaultNoticePolicy` | missing | 47F | high |
| `backoffice.finance.default-notices.show` | GET | finance | critical | `finance.view` | `App\Policies\DefaultNoticePolicy` | missing | 47F | high |
| `backoffice.finance.default-notices.store` | POST | finance | critical | `finance.create` | `App\Policies\DefaultNoticePolicy` | missing | 47F | high |
| `backoffice.finance.income-changes.accept` | POST | finance | critical | `finance.view` | `App\Policies\IncomeChangeDeclarationPolicy` | missing | 47F | high |
| `backoffice.finance.income-changes.index` | GET | finance | critical | `finance.view` | `App\Policies\IncomeChangeDeclarationPolicy` | missing | 47F | high |
| `backoffice.finance.income-changes.reject` | POST | finance | critical | `finance.reject` | `App\Policies\IncomeChangeDeclarationPolicy` | missing | 47F | high |
| `backoffice.finance.income-changes.show` | GET | finance | critical | `finance.view` | `App\Policies\IncomeChangeDeclarationPolicy` | missing | 47F | high |
| `backoffice.finance.installments.index` | GET | finance | critical | `finance.view` | `App\Policies\RentInstallmentPolicy` | missing | 47F | high |
| `backoffice.finance.installments.issue` | POST | finance | critical | `finance.view` | `App\Policies\RentInstallmentPolicy` | missing | 47F | high |
| `backoffice.finance.installments.show` | GET | finance | critical | `finance.view` | `App\Policies\RentInstallmentPolicy` | missing | 47F | high |
| `backoffice.finance.installments.waive` | POST | finance | critical | `finance.view` | `App\Policies\RentInstallmentPolicy` | missing | 47F | high |
| `backoffice.finance.regularization-agreements.approve` | POST | finance | critical | `finance.approve` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | high |
| `backoffice.finance.regularization-agreements.cancel` | POST | finance | critical | `em falta` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | low |
| `backoffice.finance.regularization-agreements.create` | GET | finance | critical | `finance.create` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | high |
| `backoffice.finance.regularization-agreements.index` | GET | finance | critical | `finance.view` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | high |
| `backoffice.finance.regularization-agreements.show` | GET | finance | critical | `finance.view` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | high |
| `backoffice.finance.regularization-agreements.store` | POST | finance | critical | `finance.create` | `App\Policies\RegularizationAgreementPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.apply` | POST | finance | critical | `finance.view` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.approve` | POST | finance | critical | `finance.approve` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.calculate` | POST | finance | critical | `finance.view` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.create` | GET | finance | critical | `finance.create` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.index` | GET | finance | critical | `finance.view` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.reject` | POST | finance | critical | `finance.reject` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.show` | GET | finance | critical | `finance.view` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.rent-reviews.store` | POST | finance | critical | `finance.create` | `App\Policies\RentReviewPolicy` | missing | 47F | high |
| `backoffice.finance.schedules.generate` | POST | finance | critical | `em falta` | `App\Policies\ContractPolicy` | missing | 47F | low |
| `backoffice.finance.schedules.index` | GET | finance | critical | `finance.view` | `App\Policies\RentSchedulePolicy` | missing | 47F | high |
| `backoffice.finance.schedules.show` | GET | finance | critical | `finance.view` | `App\Policies\RentSchedulePolicy` | missing | 47F | high |
| `backoffice.communications.receipts.download` | GET | payments | critical | `em falta` | `App\Policies\CommunicationReceiptPolicy` | missing | 47F | low |
| `backoffice.finance.imports.create` | GET | payments | critical | `payments.create` | `em falta` | candidate | 47F | medium |
| `backoffice.finance.imports.index` | GET | payments | critical | `payments.view` | `em falta` | candidate | 47F | medium |
| `backoffice.finance.imports.process` | POST | payments | critical | `payments.view` | `App\Policies\PaymentImportBatchPolicy` | missing | 47F | high |
| `backoffice.finance.imports.show` | GET | payments | critical | `payments.view` | `App\Policies\PaymentImportBatchPolicy` | missing | 47F | high |
| `backoffice.finance.imports.store` | POST | payments | critical | `payments.create` | `em falta` | candidate | 47F | medium |
| `backoffice.finance.payments.allocate` | POST | payments | critical | `payments.view` | `App\Policies\LeasePaymentPolicy` | missing | 47F | high |
| `backoffice.finance.payments.confirm` | POST | payments | critical | `em falta` | `App\Policies\LeasePaymentPolicy` | missing | 47F | low |
| `backoffice.finance.payments.create` | GET | payments | critical | `payments.create` | `App\Policies\LeasePaymentPolicy` | missing | 47F | high |
| `backoffice.finance.payments.index` | GET | payments | critical | `payments.view` | `App\Policies\LeasePaymentPolicy` | missing | 47F | high |
| `backoffice.finance.payments.reverse` | POST | payments | critical | `em falta` | `App\Policies\LeasePaymentPolicy` | missing | 47F | low |
| `backoffice.finance.payments.show` | GET | payments | critical | `payments.view` | `App\Policies\LeasePaymentPolicy` | missing | 47F | high |
| `backoffice.finance.payments.store` | POST | payments | critical | `payments.create` | `App\Policies\LeasePaymentPolicy` | missing | 47F | high |
| `backoffice.finance.receipts.cancel` | POST | payments | critical | `em falta` | `App\Policies\PaymentReceiptPolicy` | missing | 47F | low |
| `backoffice.finance.receipts.download` | GET | payments | critical | `em falta` | `App\Policies\PaymentReceiptPolicy` | missing | 47F | low |
| `backoffice.finance.receipts.generate` | POST | payments | critical | `em falta` | `App\Policies\LeasePaymentPolicy` | missing | 47F | low |
| `backoffice.finance.receipts.index` | GET | payments | critical | `payments.view` | `App\Policies\PaymentReceiptPolicy` | missing | 47F | high |
| `backoffice.finance.receipts.show` | GET | payments | critical | `payments.view` | `App\Policies\PaymentReceiptPolicy` | missing | 47F | high |
| `backoffice.tenant-operations.invoices.index` | GET | payments | critical | `payments.view` | `App\Policies\TenantInvoicePolicy` | missing | 47F | high |
| `backoffice.tenant-operations.invoices.show` | GET | payments | critical | `payments.view` | `App\Policies\TenantInvoicePolicy` | missing | 47F | high |
| `backoffice.tenant-operations.invoices.store` | POST | payments | critical | `payments.create` | `App\Policies\TenantInvoicePolicy` | missing | 47F | high |
| `backoffice.tenant-operations.payments.confirm` | POST | payments | critical | `em falta` | `App\Policies\TenantPaymentPolicy` | missing | 47F | low |
| `backoffice.tenant-operations.payments.index` | GET | payments | critical | `payments.view` | `App\Policies\TenantPaymentPolicy` | missing | 47F | high |
| `backoffice.tenant-operations.payments.show` | GET | payments | critical | `payments.view` | `App\Policies\TenantPaymentPolicy` | missing | 47F | high |
| `backoffice.tenant-operations.payments.store` | POST | payments | critical | `payments.create` | `App\Policies\TenantPaymentPolicy` | missing | 47F | high |
| `payments.create` | GET | payments | critical | `payments.create` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.destroy` | DELETE | payments | critical | `payments.delete` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.edit` | GET | payments | critical | `payments.update` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.index` | GET | payments | critical | `payments.view` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.show` | GET | payments | critical | `payments.view` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.store` | POST | payments | critical | `payments.create` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `payments.update` | PUT,PATCH | payments | critical | `payments.update` | `App\Policies\PaymentPolicy` | missing | 47F | high |
| `backoffice.agenda.index` | GET | agenda | medium | `visits.view` | `em falta` | candidate | 47G | medium |
| `backoffice.inspections.attachments.download` | GET | inspections | critical | `em falta` | `App\Policies\PropertyInspectionAttachmentPolicy` | missing | 47G | low |
| `backoffice.inspections.reports.download` | GET | inspections | critical | `em falta` | `App\Policies\PropertyInspectionReportPolicy` | missing | 47G | low |
| `backoffice.cases.inspections.show` | GET | inspections | medium | `inspections.view` | `App\Policies\PropertyInspectionPolicy` | candidate | 47G | high |
| `backoffice.inspections.attachments.store` | POST | inspections | medium | `inspections.create` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.cancel` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | low |
| `backoffice.inspections.close` | POST | inspections | medium | `inspections.update` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.complete` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | low |
| `backoffice.inspections.create` | GET | inspections | medium | `inspections.create` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.edit` | GET | inspections | medium | `inspections.update` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.index` | GET | inspections | medium | `inspections.view` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.items.store` | POST | inspections | medium | `inspections.create` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.items.update` | PUT,PATCH | inspections | medium | `inspections.update` | `App\Policies\PropertyInspectionItemPolicy` | missing | 47G | high |
| `backoffice.inspections.reports.cancel` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionReportPolicy` | missing | 47G | low |
| `backoffice.inspections.reports.generate` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | low |
| `backoffice.inspections.reports.show` | GET | inspections | medium | `inspections.view` | `App\Policies\PropertyInspectionReportPolicy` | missing | 47G | high |
| `backoffice.inspections.reports.validate` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionReportPolicy` | missing | 47G | low |
| `backoffice.inspections.show` | GET | inspections | medium | `inspections.view` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.start` | POST | inspections | medium | `inspections.view` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.store` | POST | inspections | medium | `inspections.create` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.templates.create` | GET | inspections | medium | `inspections.create` | `App\Policies\InspectionChecklistTemplatePolicy` | missing | 47G | high |
| `backoffice.inspections.templates.edit` | GET | inspections | medium | `inspections.update` | `App\Policies\InspectionChecklistTemplatePolicy` | missing | 47G | high |
| `backoffice.inspections.templates.index` | GET | inspections | medium | `inspections.view` | `App\Policies\InspectionChecklistTemplatePolicy` | missing | 47G | high |
| `backoffice.inspections.templates.store` | POST | inspections | medium | `inspections.create` | `App\Policies\InspectionChecklistTemplatePolicy` | missing | 47G | high |
| `backoffice.inspections.templates.update` | PUT,PATCH | inspections | medium | `inspections.update` | `App\Policies\InspectionChecklistTemplatePolicy` | missing | 47G | high |
| `backoffice.inspections.update` | PUT,PATCH | inspections | medium | `inspections.update` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | high |
| `backoffice.inspections.validate` | POST | inspections | medium | `em falta` | `App\Policies\PropertyInspectionPolicy` | missing | 47G | low |
| `backoffice.maintenance.attachments.download` | GET | maintenance | critical | `em falta` | `App\Policies\MaintenanceAttachmentPolicy` | missing | 47G | low |
| `backoffice.cases.housing-units.show` | GET | maintenance | medium | `housing_units.view` | `App\Policies\HousingUnitPolicy` | candidate | 47G | high |
| `backoffice.cases.maintenance.show` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | candidate | 47G | high |
| `backoffice.maintenance.assignments.cancel` | POST | maintenance | medium | `em falta` | `App\Policies\MaintenanceAssignmentPolicy` | missing | 47G | low |
| `backoffice.maintenance.assignments.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.attachments.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.create` | GET | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.destroy` | DELETE | maintenance | medium | `maintenance_requests.delete` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.edit` | GET | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.index` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.categories.update` | PUT,PATCH | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceCategoryPolicy` | missing | 47G | high |
| `backoffice.maintenance.cost-reports.index` | GET | maintenance | medium | `maintenance_requests.view` | `em falta` | candidate | 47G | medium |
| `backoffice.maintenance.costs.approve` | POST | maintenance | medium | `maintenance_requests.approve` | `App\Policies\MaintenanceCostPolicy` | missing | 47G | high |
| `backoffice.maintenance.costs.index` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceCostPolicy` | missing | 47G | high |
| `backoffice.maintenance.costs.reject` | POST | maintenance | medium | `maintenance_requests.reject` | `App\Policies\MaintenanceCostPolicy` | missing | 47G | high |
| `backoffice.maintenance.costs.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.dashboard` | GET | maintenance | medium | `maintenance_requests.view` | `em falta` | candidate | 47G | medium |
| `backoffice.maintenance.index` | GET | maintenance | medium | `maintenance_requests.view` | `em falta` | candidate | 47G | medium |
| `backoffice.maintenance.interventions.cancel` | POST | maintenance | medium | `em falta` | `App\Policies\MaintenanceInterventionPolicy` | missing | 47G | low |
| `backoffice.maintenance.interventions.complete` | POST | maintenance | medium | `em falta` | `App\Policies\MaintenanceInterventionPolicy` | missing | 47G | low |
| `backoffice.maintenance.interventions.show` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceInterventionPolicy` | missing | 47G | high |
| `backoffice.maintenance.interventions.start` | POST | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceInterventionPolicy` | missing | 47G | high |
| `backoffice.maintenance.interventions.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.cancel` | POST | maintenance | medium | `em falta` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | low |
| `backoffice.maintenance.requests.close` | POST | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.create` | GET | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.edit` | GET | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.index` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.reject` | POST | maintenance | medium | `maintenance_requests.reject` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.resolve` | POST | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.review` | POST | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.schedule` | POST | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.show` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.start` | POST | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.requests.update` | PUT,PATCH | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.create` | GET | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.edit` | GET | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.index` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.show` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.maintenance.suppliers.update` | PUT,PATCH | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceSupplierPolicy` | missing | 47G | high |
| `backoffice.properties.technical-history` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\HousingUnitPolicy` | missing | 47G | high |
| `maintenance-requests.create` | GET | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.destroy` | DELETE | maintenance | medium | `maintenance_requests.delete` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.edit` | GET | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.index` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.show` | GET | maintenance | medium | `maintenance_requests.view` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.store` | POST | maintenance | medium | `maintenance_requests.create` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `maintenance-requests.update` | PUT,PATCH | maintenance | medium | `maintenance_requests.update` | `App\Policies\MaintenanceRequestPolicy` | missing | 47G | high |
| `backoffice.housing-visits.cancel` | POST | visits | medium | `em falta` | `App\Policies\HousingVisitPolicy` | missing | 47G | low |
| `backoffice.housing-visits.complete` | POST | visits | medium | `em falta` | `App\Policies\HousingVisitPolicy` | missing | 47G | low |
| `backoffice.housing-visits.confirm` | POST | visits | medium | `em falta` | `App\Policies\HousingVisitPolicy` | missing | 47G | low |
| `backoffice.housing-visits.index` | GET | visits | medium | `visits.view` | `App\Policies\HousingVisitPolicy` | missing | 47G | high |
| `backoffice.housing-visits.no-show` | POST | visits | medium | `visits.view` | `App\Policies\HousingVisitPolicy` | missing | 47G | high |
| `backoffice.housing-visits.reject` | POST | visits | medium | `visits.reject` | `App\Policies\HousingVisitPolicy` | missing | 47G | high |
| `backoffice.housing-visits.show` | GET | visits | medium | `visits.view` | `App\Policies\HousingVisitPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.create` | GET | visits | medium | `visits.create` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.destroy` | DELETE | visits | medium | `visits.delete` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.edit` | GET | visits | medium | `visits.update` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.index` | GET | visits | medium | `visits.view` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.show` | GET | visits | medium | `visits.view` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.slots.generate` | POST | visits | medium | `em falta` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | low |
| `backoffice.visit-availabilities.store` | POST | visits | medium | `visits.create` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-availabilities.update` | PUT,PATCH | visits | medium | `visits.update` | `App\Policies\VisitAvailabilityPolicy` | missing | 47G | high |
| `backoffice.visit-slots.block` | POST | visits | medium | `em falta` | `App\Policies\VisitSlotPolicy` | missing | 47G | low |
| `backoffice.visit-slots.index` | GET | visits | medium | `visits.view` | `App\Policies\VisitSlotPolicy` | missing | 47G | high |
| `backoffice.visit-slots.unblock` | POST | visits | medium | `em falta` | `App\Policies\VisitSlotPolicy` | missing | 47G | low |
| `backoffice.procedure-minutes.download` | GET | communications | critical | `em falta` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | low |
| `backoffice.support-ticket-attachments.download` | GET | communications | critical | `em falta` | `App\Policies\SupportTicketAttachmentPolicy` | missing | 47H | low |
| `backoffice.communications.deliveries.postal` | POST | communications | medium | `notifications.view` | `App\Policies\CommunicationDeliveryPolicy` | missing | 47H | high |
| `backoffice.communications.deliveries.resend` | POST | communications | medium | `notifications.view` | `App\Policies\CommunicationDeliveryPolicy` | missing | 47H | high |
| `backoffice.communications.logs.archive` | POST | communications | medium | `notifications.update` | `App\Policies\CommunicationLogPolicy` | missing | 47H | high |
| `backoffice.communications.logs.cancel` | POST | communications | medium | `em falta` | `App\Policies\CommunicationLogPolicy` | missing | 47H | low |
| `backoffice.communications.logs.index` | GET | communications | medium | `notifications.view` | `App\Policies\CommunicationLogPolicy` | missing | 47H | high |
| `backoffice.communications.logs.show` | GET | communications | medium | `notifications.view` | `App\Policies\CommunicationLogPolicy` | missing | 47H | high |
| `backoffice.communications.logs.store` | POST | communications | medium | `notifications.create` | `App\Policies\CommunicationLogPolicy` | missing | 47H | high |
| `backoffice.communications.variables.index` | GET | communications | medium | `notifications.view` | `App\Policies\TemplateVariablePolicy` | missing | 47H | high |
| `backoffice.communications.variables.store` | POST | communications | medium | `notifications.create` | `App\Policies\TemplateVariablePolicy` | missing | 47H | high |
| `backoffice.communications.variables.update` | PUT,PATCH | communications | medium | `notifications.update` | `App\Policies\TemplateVariablePolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.create` | GET | communications | medium | `contextual_faqs.create` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.destroy` | DELETE | communications | medium | `contextual_faqs.delete` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.edit` | GET | communications | medium | `contextual_faqs.update` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.index` | GET | communications | medium | `contextual_faqs.view` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.store` | POST | communications | medium | `contextual_faqs.create` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.contextual-faqs.update` | PUT,PATCH | communications | medium | `contextual_faqs.update` | `App\Policies\ContextualFaqPolicy` | missing | 47H | high |
| `backoffice.procedure-minutes.approve` | POST | communications | medium | `em falta` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | low |
| `backoffice.procedure-minutes.destroy` | DELETE | communications | medium | `notifications.delete` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | high |
| `backoffice.procedure-minutes.generate` | POST | communications | medium | `em falta` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | low |
| `backoffice.procedure-minutes.index` | GET | communications | medium | `notifications.view` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | high |
| `backoffice.procedure-minutes.show` | GET | communications | medium | `notifications.view` | `App\Policies\ProcedureMinutePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.create` | GET | communications | medium | `notifications.create` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.edit` | GET | communications | medium | `notifications.update` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.index` | GET | communications | medium | `notifications.view` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.preview` | GET,POST | communications | medium | `notifications.view` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.publish` | POST | communications | medium | `notifications.publish` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.show` | GET | communications | medium | `notifications.view` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.store` | POST | communications | medium | `notifications.create` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.procedure-templates.update` | PUT,PATCH | communications | medium | `notifications.update` | `App\Policies\ProcedureTemplatePolicy` | missing | 47H | high |
| `backoffice.support-ticket-messages.store` | POST | communications | medium | `support.create` | `App\Policies\SupportTicketPolicy` | missing | 47H | high |
| `backoffice.support-tickets.assign` | POST | communications | medium | `em falta` | `App\Policies\SupportTicketPolicy` | missing | 47H | low |
| `backoffice.support-tickets.index` | GET | communications | medium | `support.view` | `App\Policies\SupportTicketPolicy` | missing | 47H | high |
| `backoffice.support-tickets.show` | GET | communications | medium | `support.view` | `App\Policies\SupportTicketPolicy` | missing | 47H | high |
| `backoffice.support-tickets.status` | POST | communications | medium | `em falta` | `App\Policies\SupportTicketPolicy` | missing | 47H | low |
| `backoffice.administrative-workflow-configs.activate` | POST | configuration | low | `em falta` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | low |
| `backoffice.administrative-workflow-configs.create` | GET | configuration | low | `settings.create` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | high |
| `backoffice.administrative-workflow-configs.deactivate` | POST | configuration | low | `em falta` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | low |
| `backoffice.administrative-workflow-configs.edit` | GET | configuration | low | `settings.update` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | high |
| `backoffice.administrative-workflow-configs.index` | GET | configuration | low | `settings.view` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | high |
| `backoffice.administrative-workflow-configs.store` | POST | configuration | low | `settings.create` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | high |
| `backoffice.administrative-workflow-configs.update` | PUT,PATCH | configuration | low | `settings.update` | `App\Policies\AdministrativeWorkflowConfigPolicy` | missing | 47H | high |
| `backoffice.contests.close` | POST | configuration | low | `contests.update` | `App\Policies\ContestPolicy` | missing | 47H | high |
| `backoffice.communications.event-rules.activate` | POST | notifications | medium | `em falta` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | low |
| `backoffice.communications.event-rules.create` | GET | notifications | medium | `notifications.create` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | high |
| `backoffice.communications.event-rules.deactivate` | POST | notifications | medium | `em falta` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | low |
| `backoffice.communications.event-rules.edit` | GET | notifications | medium | `notifications.update` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | high |
| `backoffice.communications.event-rules.index` | GET | notifications | medium | `notifications.view` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | high |
| `backoffice.communications.event-rules.store` | POST | notifications | medium | `notifications.create` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | high |
| `backoffice.communications.event-rules.update` | PUT,PATCH | notifications | medium | `notifications.update` | `App\Policies\NotificationEventRulePolicy` | missing | 47H | high |
| `backoffice.communications.index` | GET | notifications | medium | `notifications.view` | `em falta` | candidate | 47H | medium |
| `backoffice.communications.preferences.index` | GET | notifications | medium | `notifications.view` | `App\Policies\NotificationPreferencePolicy` | missing | 47H | high |
| `backoffice.communications.template-versions.activate` | POST | notifications | medium | `em falta` | `App\Policies\NotificationTemplateVersionPolicy` | missing | 47H | low |
| `backoffice.communications.template-versions.approve` | POST | notifications | medium | `em falta` | `App\Policies\NotificationTemplateVersionPolicy` | missing | 47H | low |
| `backoffice.communications.template-versions.archive` | POST | notifications | medium | `notifications.update` | `App\Policies\NotificationTemplateVersionPolicy` | missing | 47H | high |
| `backoffice.communications.template-versions.show` | GET | notifications | medium | `notifications.view` | `App\Policies\NotificationTemplateVersionPolicy` | missing | 47H | high |
| `backoffice.communications.template-versions.store` | POST | notifications | medium | `notifications.create` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.archive` | POST | notifications | medium | `notifications.update` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.create` | GET | notifications | medium | `notifications.create` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.edit` | GET | notifications | medium | `notifications.update` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.index` | GET | notifications | medium | `notifications.view` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.preview` | GET,POST | notifications | medium | `notifications.update` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.show` | GET | notifications | medium | `notifications.view` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.store` | POST | notifications | medium | `notifications.create` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.communications.templates.update` | PUT,PATCH | notifications | medium | `notifications.update` | `App\Policies\NotificationTemplatePolicy` | missing | 47H | high |
| `backoffice.internal-alerts.detect` | POST | notifications | medium | `notifications.view` | `App\Policies\InternalAlertPolicy` | missing | 47H | high |
| `backoffice.internal-alerts.dismiss` | POST | notifications | medium | `notifications.view` | `App\Policies\InternalAlertPolicy` | missing | 47H | high |
| `backoffice.internal-alerts.index` | GET | notifications | medium | `notifications.view` | `App\Policies\InternalAlertPolicy` | missing | 47H | high |
| `backoffice.internal-alerts.resolve` | POST | notifications | medium | `notifications.view` | `App\Policies\InternalAlertPolicy` | missing | 47H | high |
| `backoffice.internal-alerts.show` | GET | notifications | medium | `notifications.view` | `App\Policies\InternalAlertPolicy` | missing | 47H | high |
| `backoffice.official-notifications.create` | GET | notifications | medium | `notifications.create` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.official-notifications.index` | GET | notifications | medium | `notifications.view` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.official-notifications.mark-failed` | POST | notifications | medium | `notifications.view` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.official-notifications.mark-sent` | POST | notifications | medium | `notifications.view` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.official-notifications.show` | GET | notifications | medium | `notifications.view` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.official-notifications.store` | POST | notifications | medium | `notifications.create` | `App\Policies\OfficialNotificationPolicy` | missing | 47H | high |
| `backoffice.work-tasks.claim` | POST | notifications | medium | `work_tasks.claim` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.index` | GET | notifications | medium | `work_tasks.view` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.my` | GET | notifications | medium | `work_tasks.view` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.overdue` | GET | notifications | medium | `work_tasks.view` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.reassign` | POST | notifications | medium | `work_tasks.reassign` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.show` | GET | notifications | medium | `work_tasks.view` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.status` | POST | notifications | medium | `work_tasks.update_status` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.work-tasks.team` | GET | notifications | medium | `work_tasks.view_team` | `App\Policies\WorkTaskPolicy` | missing | 47H | high |
| `backoffice.analytics.index` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.cases.audit.show` | GET | reports | low | `reports.audit` | `App\Policies\AuditEventPolicy` | candidate | 47H | high |
| `backoffice.cases.contests.show` | GET | reports | low | `contests.view` | `App\Policies\ContestPolicy` | candidate | 47H | high |
| `backoffice.cases.tickets.show` | GET | reports | low | `reports.view` | `App\Policies\SupportTicketPolicy` | candidate | 47H | high |
| `backoffice.communications.dashboard` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.operational.dashboard` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.operational.executive-dashboard` | GET | reports | low | `reports.view` | `em falta` | confirmed | 47H | medium |
| `backoffice.productivity.index` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.analytics` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.dashboard` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.dashboards.create` | GET | reports | low | `reports.create` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.dashboards.destroy` | DELETE | reports | low | `reports.delete` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.dashboards.edit` | GET | reports | low | `reports.update` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.dashboards.index` | GET | reports | low | `reports.view` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.dashboards.store` | POST | reports | low | `reports.create` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.dashboards.update` | PUT,PATCH | reports | low | `reports.update` | `App\Policies\DashboardDefinitionPolicy` | candidate | 47H | high |
| `backoffice.reports.definitions.create` | GET | reports | low | `reports.create` | `App\Policies\ReportDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.definitions.destroy` | DELETE | reports | low | `reports.delete` | `App\Policies\ReportDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.definitions.edit` | GET | reports | low | `reports.update` | `App\Policies\ReportDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.definitions.store` | POST | reports | low | `reports.create` | `App\Policies\ReportDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.definitions.update` | PUT,PATCH | reports | low | `reports.update` | `App\Policies\ReportDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.executive` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.filter-presets.destroy` | DELETE | reports | low | `reports.delete` | `App\Policies\ReportFilterPresetPolicy` | missing | 47H | high |
| `backoffice.reports.filter-presets.index` | GET | reports | low | `reports.view` | `App\Policies\ReportFilterPresetPolicy` | missing | 47H | high |
| `backoffice.reports.filter-presets.store` | POST | reports | low | `reports.create` | `App\Policies\ReportFilterPresetPolicy` | missing | 47H | high |
| `backoffice.reports.filter-presets.update` | PUT,PATCH | reports | low | `reports.update` | `App\Policies\ReportFilterPresetPolicy` | missing | 47H | high |
| `backoffice.reports.indicators.index` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.indicators.show` | GET | reports | low | `reports.view` | `App\Policies\IndicatorDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.indicators.store` | POST | reports | low | `reports.create` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.indicators.update` | PUT,PATCH | reports | low | `reports.update` | `App\Policies\IndicatorDefinitionPolicy` | missing | 47H | high |
| `backoffice.reports.operational` | GET | reports | low | `reports.view` | `em falta` | candidate | 47H | medium |
| `backoffice.reports.runs.index` | GET | reports | low | `reports.view` | `App\Policies\ReportRunPolicy` | confirmed | 47H | high |
| `backoffice.reports.runs.show` | GET | reports | low | `reports.view` | `App\Policies\ReportRunPolicy` | confirmed | 47H | high |
| `backoffice.reports.runs.store` | POST | reports | low | `reports.create` | `App\Policies\ReportDefinitionPolicy` | confirmed | 47H | high |
| `backoffice.reports.widgets.destroy` | DELETE | reports | low | `reports.delete` | `App\Policies\DashboardWidgetPolicy` | candidate | 47H | high |
| `backoffice.reports.widgets.store` | POST | reports | low | `reports.create` | `App\Policies\DashboardWidgetPolicy` | candidate | 47H | high |
| `backoffice.reports.widgets.update` | PUT,PATCH | reports | low | `reports.update` | `App\Policies\DashboardWidgetPolicy` | candidate | 47H | high |
| `backoffice.work-tasks.dashboard` | GET | reports | low | `work_tasks.view` | `em falta` | candidate | 47H | medium |

Os campos completos de cada rota estão nos artefactos JSON e CSV.
