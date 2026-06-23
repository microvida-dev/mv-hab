<?php

use App\Http\Controllers\Admin\ContestController as AdminContestController;
use App\Http\Controllers\Admin\DocumentReviewController as AdminDocumentReviewController;
use App\Http\Controllers\Admin\DocumentTypeController as AdminDocumentTypeController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\RequiredDocumentController as AdminRequiredDocumentController;
use App\Http\Controllers\Backoffice\AdditionalDocumentRequestController as BackofficeAdditionalDocumentRequestController;
use App\Http\Controllers\Backoffice\AdditionalDocumentSubmissionController as BackofficeAdditionalDocumentSubmissionController;
use App\Http\Controllers\Backoffice\AdditionalInformationRequestController as BackofficeAdditionalInformationRequestController;
use App\Http\Controllers\Backoffice\AdministrativeDecisionController as BackofficeAdministrativeDecisionController;
use App\Http\Controllers\Backoffice\AdministrativeProcessController as BackofficeAdministrativeProcessController;
use App\Http\Controllers\Backoffice\AdministrativeProcessNoteController as BackofficeAdministrativeProcessNoteController;
use App\Http\Controllers\Backoffice\AdministrativeTaskController as BackofficeAdministrativeTaskController;
use App\Http\Controllers\Backoffice\AdministrativeWorkflowConfigController as BackofficeAdministrativeWorkflowConfigController;
use App\Http\Controllers\Backoffice\AllocationController as BackofficeAllocationController;
use App\Http\Controllers\Backoffice\AllocationOfferController as BackofficeAllocationOfferController;
use App\Http\Controllers\Backoffice\AllocationReportController as BackofficeAllocationReportController;
use App\Http\Controllers\Backoffice\AllocationRuleSetController as BackofficeAllocationRuleSetController;
use App\Http\Controllers\Backoffice\AllocationRunController as BackofficeAllocationRunController;
use App\Http\Controllers\Backoffice\ApplicationController as BackofficeApplicationController;
use App\Http\Controllers\Backoffice\ApplicationIntakeController as BackofficeApplicationIntakeController;
use App\Http\Controllers\Backoffice\ApplicationPublicStatusController as BackofficeApplicationPublicStatusController;
use App\Http\Controllers\Backoffice\ApplicationReportController as BackofficeApplicationReportController;
use App\Http\Controllers\Backoffice\ApplicationReviewController as BackofficeApplicationReviewController;
use App\Http\Controllers\Backoffice\ApplicationScoreController as BackofficeApplicationScoreController;
use App\Http\Controllers\Backoffice\ApplicationSimulationInconsistencyController as BackofficeApplicationSimulationInconsistencyController;
use App\Http\Controllers\Backoffice\CommunicationDeliveryController as BackofficeCommunicationDeliveryController;
use App\Http\Controllers\Backoffice\CommunicationLogController as BackofficeCommunicationLogController;
use App\Http\Controllers\Backoffice\CommunicationReceiptController as BackofficeCommunicationReceiptController;
use App\Http\Controllers\Backoffice\ComplaintController as BackofficeComplaintController;
use App\Http\Controllers\Backoffice\ComplaintDecisionController as BackofficeComplaintDecisionController;
use App\Http\Controllers\Backoffice\ComplaintReviewController as BackofficeComplaintReviewController;
use App\Http\Controllers\Backoffice\ContestClosureController as BackofficeContestClosureController;
use App\Http\Controllers\Backoffice\ContestHousingUnitController as BackofficeContestHousingUnitController;
use App\Http\Controllers\Backoffice\ContextualFaqController as BackofficeContextualFaqController;
use App\Http\Controllers\Backoffice\ContractClauseController as BackofficeContractClauseController;
use App\Http\Controllers\Backoffice\ContractDepositController as BackofficeContractDepositController;
use App\Http\Controllers\Backoffice\ContractTemplateController as BackofficeContractTemplateController;
use App\Http\Controllers\Backoffice\ControlledWithdrawalController as BackofficeControlledWithdrawalController;
use App\Http\Controllers\Backoffice\CorrectionRequestController as BackofficeCorrectionRequestController;
use App\Http\Controllers\Backoffice\CorrectionResponseReviewController as BackofficeCorrectionResponseReviewController;
use App\Http\Controllers\Backoffice\DefinitiveListController as BackofficeDefinitiveListController;
use App\Http\Controllers\Backoffice\DocumentAiAssistantController as BackofficeDocumentAiAssistantController;
use App\Http\Controllers\Backoffice\DocumentAiClassificationController as BackofficeDocumentAiClassificationController;
use App\Http\Controllers\Backoffice\DocumentAiExtractionController as BackofficeDocumentAiExtractionController;
use App\Http\Controllers\Backoffice\DocumentAiValidationController as BackofficeDocumentAiValidationController;
use App\Http\Controllers\Backoffice\DocumentDossierController as BackofficeDocumentDossierController;
use App\Http\Controllers\Backoffice\DocumentTemplateController as BackofficeDocumentTemplateController;
use App\Http\Controllers\Backoffice\DocumentTemplateVersionController as BackofficeDocumentTemplateVersionController;
use App\Http\Controllers\Backoffice\DrawAttendanceController as BackofficeDrawAttendanceController;
use App\Http\Controllers\Backoffice\DrawConvocationController as BackofficeDrawConvocationController;
use App\Http\Controllers\Backoffice\EligibilityCheckController as BackofficeEligibilityCheckController;
use App\Http\Controllers\Backoffice\EligibilityCriterionController as BackofficeEligibilityCriterionController;
use App\Http\Controllers\Backoffice\EligibilityRuleSetController as BackofficeEligibilityRuleSetController;
use App\Http\Controllers\Backoffice\ExecutiveDashboardController as BackofficeSprint24ExecutiveDashboardController;
use App\Http\Controllers\Backoffice\Finance\AccountStatementController as BackofficeFinanceAccountStatementController;
use App\Http\Controllers\Backoffice\Finance\AnnualDocumentUpdateRequestController as BackofficeFinanceAnnualDocumentUpdateRequestController;
use App\Http\Controllers\Backoffice\Finance\ArrearController as BackofficeFinanceArrearController;
use App\Http\Controllers\Backoffice\Finance\DefaultNoticeController as BackofficeFinanceDefaultNoticeController;
use App\Http\Controllers\Backoffice\Finance\IncomeChangeDeclarationController as BackofficeFinanceIncomeChangeDeclarationController;
use App\Http\Controllers\Backoffice\Finance\LeasePaymentController as BackofficeFinanceLeasePaymentController;
use App\Http\Controllers\Backoffice\Finance\PaymentImportController as BackofficeFinancePaymentImportController;
use App\Http\Controllers\Backoffice\Finance\PaymentReceiptController as BackofficeFinancePaymentReceiptController;
use App\Http\Controllers\Backoffice\Finance\RegularizationAgreementController as BackofficeFinanceRegularizationAgreementController;
use App\Http\Controllers\Backoffice\Finance\RentInstallmentController as BackofficeFinanceRentInstallmentController;
use App\Http\Controllers\Backoffice\Finance\RentReviewController as BackofficeFinanceRentReviewController;
use App\Http\Controllers\Backoffice\Finance\RentScheduleController as BackofficeFinanceRentScheduleController;
use App\Http\Controllers\Backoffice\Finance\TenantFinancialAccountController as BackofficeFinanceTenantFinancialAccountController;
use App\Http\Controllers\Backoffice\FutureApplicationDataReuseController as BackofficeFutureApplicationDataReuseController;
use App\Http\Controllers\Backoffice\GeneratedOfficialDocumentController as BackofficeGeneratedOfficialDocumentController;
use App\Http\Controllers\Backoffice\GeneratedProcedureDocumentController as BackofficeGeneratedProcedureDocumentController;
use App\Http\Controllers\Backoffice\HearingController as BackofficeHearingController;
use App\Http\Controllers\Backoffice\HearingSubmissionReviewController as BackofficeHearingSubmissionReviewController;
use App\Http\Controllers\Backoffice\HousingVisitController as BackofficeHousingVisitController;
use App\Http\Controllers\Backoffice\InspectionChecklistTemplateController as BackofficeInspectionChecklistTemplateController;
use App\Http\Controllers\Backoffice\InternalAlertController as BackofficeInternalAlertController;
use App\Http\Controllers\Backoffice\KeyHandoverAppointmentController as BackofficeKeyHandoverAppointmentController;
use App\Http\Controllers\Backoffice\LandlordDashboardController as BackofficeLandlordDashboardController;
use App\Http\Controllers\Backoffice\LeaseContractController as BackofficeLeaseContractController;
use App\Http\Controllers\Backoffice\LeaseContractDocumentController as BackofficeLeaseContractDocumentController;
use App\Http\Controllers\Backoffice\LeaseContractSignatureController as BackofficeLeaseContractSignatureController;
use App\Http\Controllers\Backoffice\LeaseContractValidationController as BackofficeLeaseContractValidationController;
use App\Http\Controllers\Backoffice\ListAutomationController as BackofficeListAutomationController;
use App\Http\Controllers\Backoffice\LotteryDrawController as BackofficeLotteryDrawController;
use App\Http\Controllers\Backoffice\LotteryParticipantController as BackofficeLotteryParticipantController;
use App\Http\Controllers\Backoffice\LotteryResultController as BackofficeLotteryResultController;
use App\Http\Controllers\Backoffice\LotteryRunController as BackofficeLotteryRunController;
use App\Http\Controllers\Backoffice\MaintenanceAssignmentController as BackofficeMaintenanceAssignmentController;
use App\Http\Controllers\Backoffice\MaintenanceAttachmentController as BackofficeMaintenanceAttachmentController;
use App\Http\Controllers\Backoffice\MaintenanceCategoryController as BackofficeMaintenanceCategoryController;
use App\Http\Controllers\Backoffice\MaintenanceCostController as BackofficeMaintenanceCostController;
use App\Http\Controllers\Backoffice\MaintenanceCostReportController as BackofficeMaintenanceCostReportController;
use App\Http\Controllers\Backoffice\MaintenanceDashboardController as BackofficeMaintenanceDashboardController;
use App\Http\Controllers\Backoffice\MaintenanceInterventionController as BackofficeMaintenanceInterventionController;
use App\Http\Controllers\Backoffice\MaintenanceRequestController as BackofficeMaintenanceRequestController;
use App\Http\Controllers\Backoffice\MaintenanceSupplierController as BackofficeMaintenanceSupplierController;
use App\Http\Controllers\Backoffice\NotificationCenterController as BackofficeNotificationCenterController;
use App\Http\Controllers\Backoffice\NotificationEventRuleController as BackofficeNotificationEventRuleController;
use App\Http\Controllers\Backoffice\NotificationPreferenceController as BackofficeNotificationPreferenceController;
use App\Http\Controllers\Backoffice\NotificationTemplateController as BackofficeNotificationTemplateController;
use App\Http\Controllers\Backoffice\NotificationTemplateVersionController as BackofficeNotificationTemplateVersionController;
use App\Http\Controllers\Backoffice\OfficialNotificationController as BackofficeOfficialNotificationController;
use App\Http\Controllers\Backoffice\OperationalDashboardController as BackofficeSprint24OperationalDashboardController;
use App\Http\Controllers\Backoffice\PostDrawReportController as BackofficePostDrawReportController;
use App\Http\Controllers\Backoffice\PreliminaryHearingSubmissionController as BackofficePreliminaryHearingSubmissionController;
use App\Http\Controllers\Backoffice\ProcedureMinuteController as BackofficeProcedureMinuteController;
use App\Http\Controllers\Backoffice\ProcedureTemplateController as BackofficeProcedureTemplateController;
use App\Http\Controllers\Backoffice\ProcessConfirmationController as BackofficeProcessConfirmationController;
use App\Http\Controllers\Backoffice\ProcessTimelineController as BackofficeProcessTimelineController;
use App\Http\Controllers\Backoffice\PropertyInspectionAttachmentController as BackofficePropertyInspectionAttachmentController;
use App\Http\Controllers\Backoffice\PropertyInspectionController as BackofficePropertyInspectionController;
use App\Http\Controllers\Backoffice\PropertyInspectionItemController as BackofficePropertyInspectionItemController;
use App\Http\Controllers\Backoffice\PropertyInspectionReportController as BackofficePropertyInspectionReportController;
use App\Http\Controllers\Backoffice\PropertyTechnicalHistoryController as BackofficePropertyTechnicalHistoryController;
use App\Http\Controllers\Backoffice\ProvisionalListController as BackofficeProvisionalListController;
use App\Http\Controllers\Backoffice\PublicPortal\HousingUnitImageController as BackofficePublicHousingUnitImageController;
use App\Http\Controllers\Backoffice\PublicPortal\HousingUnitPublicDocumentController as BackofficePublicHousingUnitDocumentController;
use App\Http\Controllers\Backoffice\PublicPortal\HousingUnitPublicProfileController as BackofficePublicHousingUnitProfileController;
use App\Http\Controllers\Backoffice\PublicPortal\PublicPortalLinkController as BackofficePublicPortalLinkController;
use App\Http\Controllers\Backoffice\PublicPortal\PublicPortalSettingController as BackofficePublicPortalSettingController;
use App\Http\Controllers\Backoffice\RankingSnapshotController as BackofficeRankingSnapshotController;
use App\Http\Controllers\Backoffice\RankingUpdateRunController as BackofficeRankingUpdateRunController;
use App\Http\Controllers\Backoffice\RentCalculationController as BackofficeRentCalculationController;
use App\Http\Controllers\Backoffice\RentManualReviewController as BackofficeRentManualReviewController;
use App\Http\Controllers\Backoffice\RentRuleController as BackofficeRentRuleController;
use App\Http\Controllers\Backoffice\RentRuleSetController as BackofficeRentRuleSetController;
use App\Http\Controllers\Backoffice\Reporting\DashboardDefinitionController as BackofficeDashboardDefinitionController;
use App\Http\Controllers\Backoffice\Reporting\DashboardWidgetController as BackofficeDashboardWidgetController;
use App\Http\Controllers\Backoffice\Reporting\ExecutiveDashboardController as BackofficeExecutiveDashboardController;
use App\Http\Controllers\Backoffice\Reporting\IndicatorController as BackofficeIndicatorController;
use App\Http\Controllers\Backoffice\Reporting\OperationalDashboardController as BackofficeOperationalDashboardController;
use App\Http\Controllers\Backoffice\Reporting\ReportAuditController as BackofficeReportAuditController;
use App\Http\Controllers\Backoffice\Reporting\ReportDefinitionController as BackofficeReportDefinitionController;
use App\Http\Controllers\Backoffice\Reporting\ReportDownloadController as BackofficeReportDownloadController;
use App\Http\Controllers\Backoffice\Reporting\ReportExportController as BackofficeReportExportController;
use App\Http\Controllers\Backoffice\Reporting\ReportFilterPresetController as BackofficeReportFilterPresetController;
use App\Http\Controllers\Backoffice\Reporting\ReportingController as BackofficeReportingController;
use App\Http\Controllers\Backoffice\Reporting\ReportRunController as BackofficeReportRunController;
use App\Http\Controllers\Backoffice\ReserveListController as BackofficeReserveListController;
use App\Http\Controllers\Backoffice\ScoringCriterionController as BackofficeScoringCriterionController;
use App\Http\Controllers\Backoffice\ScoringRuleController as BackofficeScoringRuleController;
use App\Http\Controllers\Backoffice\ScoringRuleSetController as BackofficeScoringRuleSetController;
use App\Http\Controllers\Backoffice\ScoringRunController as BackofficeScoringRunController;
use App\Http\Controllers\Backoffice\Security\AuditController as BackofficeSecurityAuditController;
use App\Http\Controllers\Backoffice\Security\MfaController as BackofficeMfaController;
use App\Http\Controllers\Backoffice\Security\PermissionReviewController as BackofficePermissionReviewController;
use App\Http\Controllers\Backoffice\Security\PrivacyController as BackofficePrivacyController;
use App\Http\Controllers\Backoffice\Security\SecurityDashboardController as BackofficeSecurityDashboardController;
use App\Http\Controllers\Backoffice\Security\SecurityOperationsController as BackofficeSecurityOperationsController;
use App\Http\Controllers\Backoffice\SimulatorConfigurationController as BackofficeSimulatorConfigurationController;
use App\Http\Controllers\Backoffice\SimulatorInsightController as BackofficeSimulatorInsightController;
use App\Http\Controllers\Backoffice\SupportTicketAttachmentController as BackofficeSupportTicketAttachmentController;
use App\Http\Controllers\Backoffice\SupportTicketController as BackofficeSupportTicketController;
use App\Http\Controllers\Backoffice\SupportTicketMessageController as BackofficeSupportTicketMessageController;
use App\Http\Controllers\Backoffice\TemplateVariableController as BackofficeTemplateVariableController;
use App\Http\Controllers\Backoffice\TenantChargeRunController as BackofficeTenantChargeRunController;
use App\Http\Controllers\Backoffice\TenantCommunicationController as BackofficeTenantCommunicationController;
use App\Http\Controllers\Backoffice\TenantInvoiceController as BackofficeTenantInvoiceController;
use App\Http\Controllers\Backoffice\TenantMaintenanceReportController as BackofficeTenantMaintenanceReportController;
use App\Http\Controllers\Backoffice\TenantPaymentController as BackofficeTenantPaymentController;
use App\Http\Controllers\Backoffice\TenantTransitionController as BackofficeTenantTransitionController;
use App\Http\Controllers\Backoffice\TieBreakerRuleController as BackofficeTieBreakerRuleController;
use App\Http\Controllers\Backoffice\TypologyAdequacyRuleController as BackofficeTypologyAdequacyRuleController;
use App\Http\Controllers\Backoffice\VisitAvailabilityController as BackofficeVisitAvailabilityController;
use App\Http\Controllers\Backoffice\VisitSlotController as BackofficeVisitSlotController;
use App\Http\Controllers\Backoffice\WinnerRegistrationController as BackofficeWinnerRegistrationController;
use App\Http\Controllers\Candidate\AdditionalDocumentSubmissionController as CandidateAdditionalDocumentSubmissionController;
use App\Http\Controllers\Candidate\AdditionalInformationResponseController as CandidateAdditionalInformationResponseController;
use App\Http\Controllers\Candidate\AdhesionRegistrationController as CandidateAdhesionRegistrationController;
use App\Http\Controllers\Candidate\AllocationController as CandidateAllocationController;
use App\Http\Controllers\Candidate\AllocationOfferController as CandidateAllocationOfferController;
use App\Http\Controllers\Candidate\AllocationResponseController as CandidateAllocationResponseController;
use App\Http\Controllers\Candidate\ApplicationController as CandidateApplicationController;
use App\Http\Controllers\Candidate\ApplicationPrefillController as CandidateApplicationPrefillController;
use App\Http\Controllers\Candidate\ApplicationReceiptController as CandidateApplicationReceiptController;
use App\Http\Controllers\Candidate\ApplicationSubmissionController as CandidateApplicationSubmissionController;
use App\Http\Controllers\Candidate\CandidateInteractionController;
use App\Http\Controllers\Candidate\CandidateNotificationCenterController;
use App\Http\Controllers\Candidate\CommunicationController as CandidateCommunicationController;
use App\Http\Controllers\Candidate\ComplaintController as CandidateComplaintController;
use App\Http\Controllers\Candidate\ContextualFaqController as CandidateContextualFaqController;
use App\Http\Controllers\Candidate\ContractDepositController as CandidateContractDepositController;
use App\Http\Controllers\Candidate\ControlledWithdrawalController as CandidateControlledWithdrawalController;
use App\Http\Controllers\Candidate\CorrectionRequestController as CandidateCorrectionRequestController;
use App\Http\Controllers\Candidate\CorrectionRequestResponseController as CandidateCorrectionRequestResponseController;
use App\Http\Controllers\Candidate\CorrectionResponseController as CandidateCorrectionResponseController;
use App\Http\Controllers\Candidate\CurrentHousingSituationController as CandidateCurrentHousingSituationController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboardController;
use App\Http\Controllers\Candidate\DocumentChecklistController as CandidateDocumentChecklistController;
use App\Http\Controllers\Candidate\DocumentController as CandidateDocumentController;
use App\Http\Controllers\Candidate\DrawConvocationController as CandidateDrawConvocationController;
use App\Http\Controllers\Candidate\EligibilityController as CandidateEligibilityController;
use App\Http\Controllers\Candidate\Finance\AnnualDocumentUpdateRequestController as CandidateFinanceAnnualDocumentUpdateRequestController;
use App\Http\Controllers\Candidate\Finance\DefaultNoticeController as CandidateFinanceDefaultNoticeController;
use App\Http\Controllers\Candidate\Finance\FinancialAccountController as CandidateFinanceFinancialAccountController;
use App\Http\Controllers\Candidate\Finance\IncomeChangeDeclarationController as CandidateFinanceIncomeChangeDeclarationController;
use App\Http\Controllers\Candidate\Finance\LeasePaymentController as CandidateFinanceLeasePaymentController;
use App\Http\Controllers\Candidate\Finance\PaymentReceiptController as CandidateFinancePaymentReceiptController;
use App\Http\Controllers\Candidate\Finance\RegularizationAgreementController as CandidateFinanceRegularizationAgreementController;
use App\Http\Controllers\Candidate\Finance\RentInstallmentController as CandidateFinanceRentInstallmentController;
use App\Http\Controllers\Candidate\Finance\RentReviewController as CandidateFinanceRentReviewController;
use App\Http\Controllers\Candidate\FutureApplicationDataReuseController as CandidateFutureApplicationDataReuseController;
use App\Http\Controllers\Candidate\GeneratedOfficialDocumentController as CandidateGeneratedOfficialDocumentController;
use App\Http\Controllers\Candidate\HearingController as CandidateHearingController;
use App\Http\Controllers\Candidate\HouseholdController as CandidateHouseholdController;
use App\Http\Controllers\Candidate\HouseholdMemberController as CandidateHouseholdMemberController;
use App\Http\Controllers\Candidate\HousingPreferenceController as CandidateHousingPreferenceController;
use App\Http\Controllers\Candidate\IncomeRecordController as CandidateIncomeRecordController;
use App\Http\Controllers\Candidate\KeyHandoverAppointmentController as CandidateKeyHandoverAppointmentController;
use App\Http\Controllers\Candidate\LeaseContractController as CandidateLeaseContractController;
use App\Http\Controllers\Candidate\LeaseContractDocumentController as CandidateLeaseContractDocumentController;
use App\Http\Controllers\Candidate\MaintenanceAttachmentController as CandidateMaintenanceAttachmentController;
use App\Http\Controllers\Candidate\MaintenanceRequestController as CandidateMaintenanceRequestController;
use App\Http\Controllers\Candidate\NotificationPreferenceController as CandidateNotificationPreferenceController;
use App\Http\Controllers\Candidate\OfficialNotificationController as CandidateOfficialNotificationController;
use App\Http\Controllers\Candidate\PreliminaryHearingSubmissionController as CandidatePreliminaryHearingSubmissionController;
use App\Http\Controllers\Candidate\PrivacyController as CandidatePrivacyController;
use App\Http\Controllers\Candidate\ProcessDashboardController as CandidateProcessDashboardController;
use App\Http\Controllers\Candidate\ProcessTimelineController as CandidateProcessTimelineController;
use App\Http\Controllers\Candidate\ProfileController as CandidateProfileController;
use App\Http\Controllers\Candidate\PropertyInspectionController as CandidatePropertyInspectionController;
use App\Http\Controllers\Candidate\PropertyTechnicalHistoryController as CandidatePropertyTechnicalHistoryController;
use App\Http\Controllers\Candidate\PublishedListController as CandidatePublishedListController;
use App\Http\Controllers\Candidate\RegistrationRenewalController as CandidateRegistrationRenewalController;
use App\Http\Controllers\Candidate\SimulationController as CandidateSimulationController;
use App\Http\Controllers\Candidate\SupportTicketAttachmentController as CandidateSupportTicketAttachmentController;
use App\Http\Controllers\Candidate\SupportTicketController as CandidateSupportTicketController;
use App\Http\Controllers\Candidate\SupportTicketMessageController as CandidateSupportTicketMessageController;
use App\Http\Controllers\Candidate\VisitController as CandidateVisitController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HousingApplicationController;
use App\Http\Controllers\HousingUnitController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicArea\PublishedResultListController;
use App\Http\Controllers\PublicFaqController;
use App\Http\Controllers\PublicPortal\AdvancedSimulatorController;
use App\Http\Controllers\PublicPortal\HousingOfferController;
use App\Http\Controllers\PublicPortal\PublicContestController as PublicPortalContestController;
use App\Http\Controllers\PublicPortal\PublicHousingDocumentController;
use App\Http\Controllers\PublicPortal\PublicHousingMapController;
use App\Http\Controllers\PublicPortal\PublicHousingUnitController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\PublicProgramController;
use App\Http\Controllers\Tenant\CommunicationController as TenantCommunicationController;
use App\Http\Controllers\Tenant\CommunicationMessageController as TenantCommunicationMessageController;
use App\Http\Controllers\Tenant\ContractController as TenantContractController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\InspectionController as TenantInspectionController;
use App\Http\Controllers\Tenant\InvoiceController as TenantInvoiceController;
use App\Http\Controllers\Tenant\MaintenanceRequestController as TenantMaintenanceRequestController;
use App\Http\Controllers\Tenant\PaymentController as TenantPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicPortalController::class)->name('public.portal');
Route::get('/programas', [PublicProgramController::class, 'index'])->name('public.programs.index');
Route::get('/programas/{slug}', [PublicProgramController::class, 'show'])->name('public.programs.show');
Route::get('/oferta-habitacional', [HousingOfferController::class, 'index'])->name('public.housing-offer.index');
Route::get('/oferta-habitacional/concursos', [PublicPortalContestController::class, 'index'])->name('public.contests.index');
Route::get('/oferta-habitacional/concursos/{slug}', [PublicPortalContestController::class, 'show'])->name('public.contests.show');
Route::get('/oferta-habitacional/imoveis', [PublicHousingUnitController::class, 'index'])->name('public.housing-units.index');
Route::get('/oferta-habitacional/imoveis/{slug}/brochura', [PublicHousingUnitController::class, 'brochure'])->name('public.housing-units.brochure');
Route::get('/oferta-habitacional/imoveis/{slug}', [PublicHousingUnitController::class, 'show'])->name('public.housing-units.show');
Route::get('/oferta-habitacional/mapa', [PublicHousingMapController::class, 'index'])->name('public.housing-map.index');
Route::get('/oferta-habitacional/documentos/{document}/download', [PublicHousingDocumentController::class, 'download'])->name('public.housing-documents.download');
Route::get('/concursos', [PublicPortalContestController::class, 'index'])->name('public.contests.legacy.index');
Route::get('/concursos/{slug}', [PublicPortalContestController::class, 'show'])->name('public.contests.legacy.show');
Route::get('/perguntas-frequentes', PublicFaqController::class)->name('public.faq');
Route::get('/resultados', [PublishedResultListController::class, 'index'])->name('public.results.index');
Route::get('/resultados/{listPublication}', [PublishedResultListController::class, 'show'])->name('public.results.show');
Route::get('/simulador', [AdvancedSimulatorController::class, 'show'])->name('public.simulator.show');
Route::post('/simulador', [AdvancedSimulatorController::class, 'simulate'])->middleware('throttle:20,1')->name('public.simulator.simulate');
Route::get('/simulador/resultado/{uuid}', [AdvancedSimulatorController::class, 'result'])->name('public.simulator.result');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor,candidate')
        ->name('dashboard');

    Route::prefix('area-candidato')
        ->name('candidate.')
        ->middleware('role:candidate')
        ->group(function () {
            Route::get('/', CandidateDashboardController::class)->name('dashboard');

            Route::get('/registo', [CandidateAdhesionRegistrationController::class, 'show'])
                ->name('registration.show');
            Route::get('/registo/criar', [CandidateAdhesionRegistrationController::class, 'create'])
                ->name('registration.create');
            Route::post('/registo', [CandidateAdhesionRegistrationController::class, 'store'])
                ->name('registration.store');
            Route::get('/registo/editar', [CandidateAdhesionRegistrationController::class, 'edit'])
                ->name('registration.edit');
            Route::patch('/registo', [CandidateAdhesionRegistrationController::class, 'update'])
                ->name('registration.update');
            Route::post('/registo/finalizar', [CandidateAdhesionRegistrationController::class, 'finalize'])
                ->name('registration.finalize');
            Route::post('/registo/cancelar', [CandidateAdhesionRegistrationController::class, 'cancel'])
                ->name('registration.cancel');
            Route::delete('/registo/remover', [CandidateAdhesionRegistrationController::class, 'remove'])
                ->name('registration.remove');

            Route::get('/agregado', [CandidateHouseholdController::class, 'show'])
                ->name('household.show');
            Route::post('/agregado', [CandidateHouseholdController::class, 'store'])
                ->name('household.store');
            Route::get('/agregado/editar', [CandidateHouseholdController::class, 'edit'])
                ->name('household.edit');
            Route::put('/agregado', [CandidateHouseholdController::class, 'update'])
                ->name('household.update');

            Route::get('/agregado/membros', [CandidateHouseholdMemberController::class, 'index'])
                ->name('household-members.index');
            Route::get('/agregado/membros/criar', [CandidateHouseholdMemberController::class, 'create'])
                ->name('household-members.create');
            Route::post('/agregado/membros', [CandidateHouseholdMemberController::class, 'store'])
                ->name('household-members.store');
            Route::get('/agregado/membros/{member}/editar', [CandidateHouseholdMemberController::class, 'edit'])
                ->name('household-members.edit');
            Route::match(['put', 'patch'], '/agregado/membros/{member}', [CandidateHouseholdMemberController::class, 'update'])
                ->name('household-members.update');
            Route::delete('/agregado/membros/{member}', [CandidateHouseholdMemberController::class, 'destroy'])
                ->name('household-members.destroy');

            Route::get('/rendimentos', [CandidateIncomeRecordController::class, 'index'])
                ->name('income-records.index');
            Route::get('/rendimentos/criar', [CandidateIncomeRecordController::class, 'create'])
                ->name('income-records.create');
            Route::post('/rendimentos', [CandidateIncomeRecordController::class, 'store'])
                ->name('income-records.store');
            Route::get('/rendimentos/{incomeRecord}/editar', [CandidateIncomeRecordController::class, 'edit'])
                ->name('income-records.edit');
            Route::match(['put', 'patch'], '/rendimentos/{incomeRecord}', [CandidateIncomeRecordController::class, 'update'])
                ->name('income-records.update');
            Route::delete('/rendimentos/{incomeRecord}', [CandidateIncomeRecordController::class, 'destroy'])
                ->name('income-records.destroy');

            Route::get('/habitacao-atual', [CandidateCurrentHousingSituationController::class, 'show'])
                ->name('current-housing.show');
            Route::get('/habitacao-atual/editar', [CandidateCurrentHousingSituationController::class, 'edit'])
                ->name('current-housing.edit');
            Route::match(['put', 'patch'], '/habitacao-atual', [CandidateCurrentHousingSituationController::class, 'update'])
                ->name('current-housing.update');

            Route::get('/perfil', CandidateProfileController::class)->name('profile');
            Route::get('/simulacoes', [CandidateSimulationController::class, 'index'])
                ->name('simulations.index');
            Route::get('/simulacoes/criar', [CandidateSimulationController::class, 'create'])
                ->name('simulations.create');
            Route::post('/simulacoes', [CandidateSimulationController::class, 'store'])
                ->name('simulations.store');
            Route::get('/simulacoes/{simulationSession}', [CandidateSimulationController::class, 'show'])
                ->name('simulations.show');
            Route::post('/simulacoes/{simulationSession}/guardar', [CandidateSimulationController::class, 'save'])
                ->name('simulations.save');
            Route::post('/simulacoes/{simulationSession}/pre-preencher', [CandidateSimulationController::class, 'convertToPrefill'])
                ->name('simulations.prefill');
            Route::get('/pre-preenchimentos/{applicationPrefill}', [CandidateApplicationPrefillController::class, 'show'])
                ->name('application-prefills.show');
            Route::post('/pre-preenchimentos/{applicationPrefill}/confirmar', [CandidateApplicationPrefillController::class, 'confirm'])
                ->name('application-prefills.confirm');
            Route::post('/pre-preenchimentos/{applicationPrefill}/aplicar', [CandidateApplicationPrefillController::class, 'apply'])
                ->name('application-prefills.apply');
            Route::post('/pre-preenchimentos/{applicationPrefill}/cancelar', [CandidateApplicationPrefillController::class, 'cancel'])
                ->name('application-prefills.cancel');
            Route::get('/renovacoes-registo', [CandidateRegistrationRenewalController::class, 'index'])
                ->name('registration-renewals.index');
            Route::get('/renovacoes-registo/criar', [CandidateRegistrationRenewalController::class, 'create'])
                ->name('registration-renewals.create');
            Route::post('/renovacoes-registo', [CandidateRegistrationRenewalController::class, 'store'])
                ->name('registration-renewals.store');
            Route::get('/renovacoes-registo/{registrationRenewal}', [CandidateRegistrationRenewalController::class, 'show'])
                ->name('registration-renewals.show');
            Route::match(['put', 'patch'], '/renovacoes-registo/{registrationRenewal}', [CandidateRegistrationRenewalController::class, 'update'])
                ->name('registration-renewals.update');
            Route::post('/renovacoes-registo/{registrationRenewal}/submeter', [CandidateRegistrationRenewalController::class, 'submit'])
                ->name('registration-renewals.submit');
            Route::get('/elegibilidade', [CandidateEligibilityController::class, 'index'])
                ->name('eligibility.index');
            Route::post('/elegibilidade/pre-verificar', [CandidateEligibilityController::class, 'preCheck'])
                ->name('eligibility.pre-check');
            Route::get('/elegibilidade/historico', [CandidateEligibilityController::class, 'history'])
                ->name('eligibility.history');
            Route::get('/elegibilidade/{eligibilityCheck}', [CandidateEligibilityController::class, 'show'])
                ->name('eligibility.show');
            Route::get('/candidaturas', [CandidateApplicationController::class, 'index'])
                ->name('applications.index');
            Route::get('/candidaturas/criar/{contest}', [CandidateApplicationController::class, 'create'])
                ->name('applications.create');
            Route::post('/candidaturas', [CandidateApplicationController::class, 'store'])
                ->name('applications.store');
            Route::get('/candidaturas/{application}', [CandidateApplicationController::class, 'show'])
                ->name('applications.show');
            Route::get('/candidaturas/{application}/editar', [CandidateApplicationController::class, 'edit'])
                ->name('applications.edit');
            Route::match(['put', 'patch'], '/candidaturas/{application}', [CandidateApplicationController::class, 'update'])
                ->name('applications.update');
            Route::get('/candidaturas/{application}/rever', [CandidateApplicationSubmissionController::class, 'review'])
                ->name('applications.review');
            Route::post('/candidaturas/{application}/submeter', [CandidateApplicationSubmissionController::class, 'submit'])
                ->name('applications.submit');
            Route::post('/candidaturas/{application}/desistir', [CandidateApplicationController::class, 'withdraw'])
                ->name('applications.withdraw');
            Route::get('/candidaturas/{application}/comprovativo', [CandidateApplicationReceiptController::class, 'show'])
                ->name('applications.receipt');
            Route::get('/candidaturas/{application}/imprimir', [CandidateApplicationReceiptController::class, 'print'])
                ->name('applications.print');

            Route::get('/visitas', [CandidateVisitController::class, 'index'])
                ->name('visits.index');
            Route::get('/visitas/agendar', [CandidateVisitController::class, 'create'])
                ->name('visits.create');
            Route::post('/visitas', [CandidateVisitController::class, 'store'])
                ->name('visits.store');
            Route::get('/visitas/{housingVisit}', [CandidateVisitController::class, 'show'])
                ->name('visits.show');
            Route::get('/visitas/{housingVisit}/reagendar', [CandidateVisitController::class, 'edit'])
                ->name('visits.reschedule');
            Route::post('/visitas/{housingVisit}/reagendar', [CandidateVisitController::class, 'reschedule'])
                ->name('visits.reschedule.store');
            Route::post('/visitas/{housingVisit}/cancelar', [CandidateVisitController::class, 'cancel'])
                ->name('visits.cancel');

            Route::get('/apoio', [CandidateSupportTicketController::class, 'index'])
                ->name('support-tickets.index');
            Route::get('/apoio/criar', [CandidateSupportTicketController::class, 'create'])
                ->name('support-tickets.create');
            Route::post('/apoio', [CandidateSupportTicketController::class, 'store'])
                ->name('support-tickets.store');
            Route::get('/apoio/{supportTicket}', [CandidateSupportTicketController::class, 'show'])
                ->name('support-tickets.show');
            Route::post('/apoio/{supportTicket}/mensagens', [CandidateSupportTicketMessageController::class, 'store'])
                ->name('support-ticket-messages.store');
            Route::get('/apoio/anexos/{supportTicketAttachment}/download', [CandidateSupportTicketAttachmentController::class, 'download'])
                ->name('support-ticket-attachments.download');

            Route::get('/interacoes', [CandidateInteractionController::class, 'index'])
                ->name('interactions.index');
            Route::get('/faq-contextual', [CandidateContextualFaqController::class, 'index'])
                ->name('contextual-faq.index');

            Route::get('/processos', [CandidateProcessDashboardController::class, 'index'])
                ->name('processes.index');
            Route::get('/processos/{administrativeProcess}', [CandidateProcessDashboardController::class, 'show'])
                ->name('processes.show');
            Route::get('/processos/{administrativeProcess}/timeline', [CandidateProcessTimelineController::class, 'show'])
                ->name('processes.timeline');

            Route::get('/resultados', [CandidatePublishedListController::class, 'index'])
                ->name('results.index');
            Route::get('/resultados/{provisionalList}', [CandidatePublishedListController::class, 'show'])
                ->name('results.show');
            Route::get('/reclamacoes', [CandidateComplaintController::class, 'index'])
                ->name('complaints.index');
            Route::get('/reclamacoes/criar', [CandidateComplaintController::class, 'create'])
                ->name('complaints.create');
            Route::post('/reclamacoes', [CandidateComplaintController::class, 'store'])
                ->name('complaints.store');
            Route::get('/reclamacoes/{complaint}', [CandidateComplaintController::class, 'show'])
                ->name('complaints.show');
            Route::get('/reclamacoes/{complaint}/editar', [CandidateComplaintController::class, 'edit'])
                ->name('complaints.edit');
            Route::match(['put', 'patch'], '/reclamacoes/{complaint}', [CandidateComplaintController::class, 'update'])
                ->name('complaints.update');
            Route::post('/reclamacoes/{complaint}/submeter', [CandidateComplaintController::class, 'submit'])
                ->name('complaints.submit');
            Route::post('/reclamacoes/{complaint}/desistir', [CandidateComplaintController::class, 'withdraw'])
                ->name('complaints.withdraw');

            Route::get('/pedidos-informacao-complementar/{additionalInformationRequest}', [CandidateAdditionalInformationResponseController::class, 'show'])
                ->name('additional-information.show');
            Route::get('/pedidos-informacao-complementar/{additionalInformationRequest}/responder', [CandidateAdditionalInformationResponseController::class, 'create'])
                ->name('additional-information.respond');
            Route::post('/pedidos-informacao-complementar/{additionalInformationRequest}/responder', [CandidateAdditionalInformationResponseController::class, 'store'])
                ->name('additional-information.respond.store');

            Route::get('/audiencias', [CandidateHearingController::class, 'index'])
                ->name('hearings.index');
            Route::get('/audiencias/{hearing}', [CandidateHearingController::class, 'show'])
                ->name('hearings.show');
            Route::get('/audiencias/{hearing}/pronunciar', [CandidatePreliminaryHearingSubmissionController::class, 'create'])
                ->name('hearings.submit');
            Route::post('/audiencias/{hearing}/pronunciar', [CandidatePreliminaryHearingSubmissionController::class, 'store'])
                ->name('hearings.submit.store');

            Route::get('/notificacoes-oficiais', [CandidateOfficialNotificationController::class, 'index'])
                ->name('official-notifications.index');
            Route::get('/notificacoes-oficiais/{officialNotification}', [CandidateOfficialNotificationController::class, 'show'])
                ->name('official-notifications.show');
            Route::post('/notificacoes-oficiais/{officialNotification}/marcar-lida', [CandidateOfficialNotificationController::class, 'markRead'])
                ->name('official-notifications.mark-read');
            Route::post('/notificacoes-oficiais/{officialNotification}/tomar-conhecimento', [CandidateOfficialNotificationController::class, 'acknowledge'])
                ->name('official-notifications.acknowledge');
            Route::post('/notificacoes-oficiais/{officialNotification}/arquivar', [CandidateOfficialNotificationController::class, 'archive'])
                ->name('official-notifications.archive');

            Route::get('/convocatorias-sorteio', [CandidateDrawConvocationController::class, 'index'])
                ->name('draw-convocations.index');
            Route::get('/convocatorias-sorteio/{drawConvocation}', [CandidateDrawConvocationController::class, 'show'])
                ->name('draw-convocations.show');
            Route::post('/convocatorias-sorteio/{drawConvocation}/confirmar-leitura', [CandidateDrawConvocationController::class, 'markRead'])
                ->name('draw-convocations.mark-read');

            Route::get('/entrega-chaves', [CandidateKeyHandoverAppointmentController::class, 'index'])
                ->name('key-handovers.index');
            Route::get('/entrega-chaves/{keyHandoverAppointment}', [CandidateKeyHandoverAppointmentController::class, 'show'])
                ->name('key-handovers.show');

            Route::get('/preferencias-habitacao', [CandidateHousingPreferenceController::class, 'index'])
                ->name('housing-preferences.index');
            Route::get('/preferencias-habitacao/{application}/editar', [CandidateHousingPreferenceController::class, 'edit'])
                ->name('housing-preferences.edit');
            Route::match(['put', 'patch'], '/preferencias-habitacao/{application}', [CandidateHousingPreferenceController::class, 'update'])
                ->name('housing-preferences.update');
            Route::post('/preferencias-habitacao/{application}/submeter', [CandidateHousingPreferenceController::class, 'submit'])
                ->name('housing-preferences.submit');

            Route::get('/atribuicoes', [CandidateAllocationController::class, 'index'])
                ->name('allocations.index');
            Route::get('/atribuicoes/{allocation}', [CandidateAllocationController::class, 'show'])
                ->name('allocations.show');
            Route::post('/atribuicoes/{allocation}/desistir', [CandidateAllocationController::class, 'withdraw'])
                ->name('allocations.withdraw');
            Route::get('/ofertas-atribuicao', [CandidateAllocationOfferController::class, 'index'])
                ->name('allocation-offers.index');
            Route::get('/ofertas-atribuicao/{allocationOffer}', [CandidateAllocationOfferController::class, 'show'])
                ->name('allocation-offers.show');
            Route::post('/ofertas-atribuicao/{allocationOffer}/aceitar', [CandidateAllocationResponseController::class, 'accept'])
                ->name('allocation-offers.accept');
            Route::post('/ofertas-atribuicao/{allocationOffer}/recusar', [CandidateAllocationResponseController::class, 'refuse'])
                ->name('allocation-offers.refuse');

            Route::get('/contratos', [CandidateLeaseContractController::class, 'index'])
                ->name('contracts.index');
            Route::get('/contratos/documentos/{leaseContractDocument}/download', [CandidateLeaseContractDocumentController::class, 'download'])
                ->name('contracts.documents.download');
            Route::get('/contratos/{leaseContract}', [CandidateLeaseContractController::class, 'show'])
                ->name('contracts.show');
            Route::get('/contratos/{leaseContract}/caucao', [CandidateContractDepositController::class, 'show'])
                ->name('contracts.deposit.show');

            Route::prefix('financeiro')->name('finance.')->group(function () {
                Route::get('/', [CandidateFinanceFinancialAccountController::class, 'index'])->name('index');
                Route::get('contas/{tenantFinancialAccount}', [CandidateFinanceFinancialAccountController::class, 'show'])->name('accounts.show');
                Route::get('prestacoes', [CandidateFinanceRentInstallmentController::class, 'index'])->name('installments.index');
                Route::get('prestacoes/{rentInstallment}', [CandidateFinanceRentInstallmentController::class, 'show'])->name('installments.show');
                Route::get('pagamentos', [CandidateFinanceLeasePaymentController::class, 'index'])->name('payments.index');
                Route::get('pagamentos/{leasePayment}', [CandidateFinanceLeasePaymentController::class, 'show'])->name('payments.show');
                Route::get('comprovativos', [CandidateFinancePaymentReceiptController::class, 'index'])->name('receipts.index');
                Route::get('comprovativos/{paymentReceipt}', [CandidateFinancePaymentReceiptController::class, 'show'])->name('receipts.show');
                Route::get('comprovativos/{paymentReceipt}/download', [CandidateFinancePaymentReceiptController::class, 'download'])->name('receipts.download');
                Route::get('avisos-incumprimento', [CandidateFinanceDefaultNoticeController::class, 'index'])->name('default-notices.index');
                Route::get('avisos-incumprimento/{defaultNotice}', [CandidateFinanceDefaultNoticeController::class, 'show'])->name('default-notices.show');
                Route::get('acordos-regularizacao', [CandidateFinanceRegularizationAgreementController::class, 'index'])->name('regularization-agreements.index');
                Route::get('acordos-regularizacao/{regularizationAgreement}', [CandidateFinanceRegularizationAgreementController::class, 'show'])->name('regularization-agreements.show');
                Route::get('revisoes-renda', [CandidateFinanceRentReviewController::class, 'index'])->name('rent-reviews.index');
                Route::get('revisoes-renda/{rentReview}', [CandidateFinanceRentReviewController::class, 'show'])->name('rent-reviews.show');
                Route::get('alteracoes-rendimento', [CandidateFinanceIncomeChangeDeclarationController::class, 'index'])->name('income-changes.index');
                Route::get('alteracoes-rendimento/criar', [CandidateFinanceIncomeChangeDeclarationController::class, 'create'])->name('income-changes.create');
                Route::post('alteracoes-rendimento', [CandidateFinanceIncomeChangeDeclarationController::class, 'store'])->name('income-changes.store');
                Route::get('alteracoes-rendimento/{incomeChangeDeclaration}', [CandidateFinanceIncomeChangeDeclarationController::class, 'show'])->name('income-changes.show');
                Route::post('alteracoes-rendimento/{incomeChangeDeclaration}/submeter', [CandidateFinanceIncomeChangeDeclarationController::class, 'submit'])->name('income-changes.submit');
                Route::get('atualizacao-documental', [CandidateFinanceAnnualDocumentUpdateRequestController::class, 'index'])->name('annual-document-updates.index');
                Route::get('atualizacao-documental/{annualDocumentUpdateRequest}', [CandidateFinanceAnnualDocumentUpdateRequestController::class, 'show'])->name('annual-document-updates.show');
                Route::post('atualizacao-documental/{annualDocumentUpdateRequest}/submeter', [CandidateFinanceAnnualDocumentUpdateRequestController::class, 'submit'])->name('annual-document-updates.submit');
            });

            Route::get('/manutencao', [CandidateMaintenanceRequestController::class, 'overview'])
                ->name('maintenance.index');
            Route::get('/manutencao/pedidos', [CandidateMaintenanceRequestController::class, 'index'])
                ->name('maintenance.requests.index');
            Route::get('/manutencao/pedidos/criar', [CandidateMaintenanceRequestController::class, 'create'])
                ->name('maintenance.requests.create');
            Route::post('/manutencao/pedidos', [CandidateMaintenanceRequestController::class, 'store'])
                ->name('maintenance.requests.store');
            Route::get('/manutencao/pedidos/{maintenanceRequest}', [CandidateMaintenanceRequestController::class, 'show'])
                ->name('maintenance.requests.show');
            Route::post('/manutencao/pedidos/{maintenanceRequest}/attachments', [CandidateMaintenanceAttachmentController::class, 'store'])
                ->name('maintenance.attachments.store');
            Route::get('/manutencao/attachments/{maintenanceAttachment}/download', [CandidateMaintenanceAttachmentController::class, 'download'])
                ->name('maintenance.attachments.download');

            Route::get('/vistorias', [CandidatePropertyInspectionController::class, 'index'])
                ->name('inspections.index');
            Route::get('/vistorias/reports/{propertyInspectionReport}/download', [CandidatePropertyInspectionController::class, 'downloadReport'])
                ->name('inspections.reports.download');
            Route::get('/vistorias/{propertyInspection}', [CandidatePropertyInspectionController::class, 'show'])
                ->name('inspections.show');
            Route::get('/imovel/historico-tecnico', [CandidatePropertyTechnicalHistoryController::class, 'index'])
                ->name('property.technical-history');

            Route::get('/pedidos-aperfeicoamento', [CandidateCorrectionRequestController::class, 'index'])
                ->name('correction-requests.index');
            Route::get('/pedidos-aperfeicoamento/{correctionRequest}', [CandidateCorrectionRequestController::class, 'show'])
                ->name('correction-requests.show');
            Route::get('/pedidos-aperfeicoamento/{correctionRequest}/responder', [CandidateCorrectionResponseController::class, 'create'])
                ->name('correction-requests.respond');
            Route::post('/pedidos-aperfeicoamento/{correctionRequest}/respostas', [CandidateCorrectionResponseController::class, 'store'])
                ->name('correction-requests.responses.store');
            Route::get('/respostas-aperfeicoamento/{correctionResponse}/editar', [CandidateCorrectionResponseController::class, 'edit'])
                ->name('correction-responses.edit');
            Route::match(['put', 'patch'], '/respostas-aperfeicoamento/{correctionResponse}', [CandidateCorrectionResponseController::class, 'update'])
                ->name('correction-responses.update');
            Route::post('/pedidos-aperfeicoamento/{correctionRequest}/submeter', [CandidateCorrectionResponseController::class, 'submit'])
                ->name('correction-requests.submit');

            Route::get('/documentos', [CandidateDocumentController::class, 'index'])
                ->name('documents.index');
            Route::get('/documentos/checklist', CandidateDocumentChecklistController::class)
                ->name('documents.checklist');
            Route::get('/documentos/submeter', [CandidateDocumentController::class, 'create'])
                ->name('documents.create');
            Route::post('/documentos', [CandidateDocumentController::class, 'store'])
                ->name('documents.store');
            Route::get('/documentos/{documentSubmission}', [CandidateDocumentController::class, 'show'])
                ->name('documents.show');
            Route::get('/documentos/{documentSubmission}/substituir', [CandidateDocumentController::class, 'replaceCreate'])
                ->name('documents.replace.create');
            Route::post('/documentos/{documentSubmission}/substituir', [CandidateDocumentController::class, 'replaceStore'])
                ->name('documents.replace.store');
            Route::get('/documentos/{documentSubmission}/download', [CandidateDocumentController::class, 'download'])
                ->name('documents.download');
            Route::delete('/documentos/{documentSubmission}', [CandidateDocumentController::class, 'destroy'])
                ->name('documents.destroy');
            Route::get('/notificacoes', [CandidateNotificationCenterController::class, 'index'])
                ->name('notifications.index');
            Route::get('/notificacoes/{officialNotification}', [CandidateOfficialNotificationController::class, 'show'])
                ->name('notifications.show');
            Route::post('/notificacoes/{officialNotification}/marcar-lida', [CandidateNotificationCenterController::class, 'markRead'])
                ->name('notifications.mark-read');
            Route::post('/notificacoes/{officialNotification}/tomar-conhecimento', [CandidateOfficialNotificationController::class, 'acknowledge'])
                ->name('notifications.acknowledge');
            Route::post('/notificacoes/{officialNotification}/arquivar', [CandidateNotificationCenterController::class, 'archive'])
                ->name('notifications.archive');

            Route::get('/candidaturas/{application}/documentos-adicionais/criar', [CandidateAdditionalDocumentSubmissionController::class, 'create'])
                ->name('additional-documents.create');
            Route::post('/candidaturas/{application}/documentos-adicionais', [CandidateAdditionalDocumentSubmissionController::class, 'store'])
                ->name('additional-documents.store');
            Route::get('/candidaturas/{application}/aperfeicoamentos/{correctionRequest}/responder', [CandidateCorrectionRequestResponseController::class, 'create'])
                ->name('advanced-correction-requests.respond');
            Route::post('/candidaturas/{application}/aperfeicoamentos/{correctionRequest}/responder', [CandidateCorrectionRequestResponseController::class, 'store'])
                ->name('advanced-correction-requests.respond.store');
            Route::get('/candidaturas/{application}/desistencia-controlada', [CandidateControlledWithdrawalController::class, 'create'])
                ->name('controlled-withdrawals.create');
            Route::post('/candidaturas/{application}/desistencia-controlada', [CandidateControlledWithdrawalController::class, 'store'])
                ->name('controlled-withdrawals.store');
            Route::get('/desistencias/{controlledWithdrawal}', [CandidateControlledWithdrawalController::class, 'show'])
                ->name('withdrawals.show');
            Route::post('/desistencias/{controlledWithdrawal}/confirmar', [CandidateControlledWithdrawalController::class, 'confirm'])
                ->name('withdrawals.confirm');
            Route::post('/desistencias/{controlledWithdrawal}/cancelar', [CandidateControlledWithdrawalController::class, 'cancel'])
                ->name('withdrawals.cancel');
            Route::get('/reutilizacao-dados', [CandidateFutureApplicationDataReuseController::class, 'index'])
                ->name('data-reuse.index');
            Route::post('/reutilizacao-dados', [CandidateFutureApplicationDataReuseController::class, 'store'])
                ->name('data-reuse.store');
            Route::post('/reutilizacao-dados/{futureApplicationDataReuse}/confirmar', [CandidateFutureApplicationDataReuseController::class, 'confirm'])
                ->name('data-reuse.confirm');

            Route::get('/comunicacoes', [CandidateCommunicationController::class, 'index'])
                ->name('communications.index');
            Route::get('/comunicacoes/{communicationLog}', [CandidateCommunicationController::class, 'show'])
                ->name('communications.show');

            Route::get('/documentos-oficiais', [CandidateGeneratedOfficialDocumentController::class, 'index'])
                ->name('official-documents.index');
            Route::get('/documentos-oficiais/{generatedOfficialDocument}', [CandidateGeneratedOfficialDocumentController::class, 'show'])
                ->name('official-documents.show');
            Route::get('/documentos-oficiais/{generatedOfficialDocument}/download', [CandidateGeneratedOfficialDocumentController::class, 'download'])
                ->name('official-documents.download');

            Route::prefix('privacidade')->name('privacy.')->group(function () {
                Route::get('/', [CandidatePrivacyController::class, 'index'])->name('index');
                Route::post('pedidos', [CandidatePrivacyController::class, 'storeRequest'])->name('requests.store');
                Route::get('pedidos/{dataSubjectRequest}', [CandidatePrivacyController::class, 'showRequest'])->name('requests.show');
                Route::post('pedidos/{dataSubjectRequest}/exportar', [CandidatePrivacyController::class, 'generateExport'])->name('requests.export');
                Route::get('exportacoes/{dataExportPackage}', [CandidatePrivacyController::class, 'showExport'])->name('exports.show');
                Route::get('exportacoes/{dataExportPackage}/download', [CandidatePrivacyController::class, 'downloadExport'])->name('exports.download');
                Route::post('consentimentos', [CandidatePrivacyController::class, 'grantConsent'])->name('consents.store');
                Route::post('consentimentos/{userConsent}/retirar', [CandidatePrivacyController::class, 'withdrawConsent'])->name('consents.withdraw');
            });

            Route::get('/preferencias-notificacoes', [CandidateNotificationPreferenceController::class, 'edit'])
                ->name('notification-preferences.edit');
            Route::match(['put', 'patch'], '/preferencias-notificacoes', [CandidateNotificationPreferenceController::class, 'update'])
                ->name('notification-preferences.update');
        });

    Route::prefix('area-inquilino')->name('tenant.')->middleware('role:candidate')->group(function () {
        Route::get('/', TenantDashboardController::class)->name('dashboard');
        Route::get('/contratos', [TenantContractController::class, 'index'])->name('contracts.index');
        Route::get('/contratos/{contract}', [TenantContractController::class, 'show'])->name('contracts.show');
        Route::get('/faturas', [TenantInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/faturas/{tenantInvoice}', [TenantInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/pagamentos', [TenantPaymentController::class, 'index'])->name('payments.index');
        Route::get('/pagamentos/{tenantPayment}', [TenantPaymentController::class, 'show'])->name('payments.show');
        Route::get('/manutencao', [TenantMaintenanceRequestController::class, 'index'])->name('maintenance.index');
        Route::get('/manutencao/criar', [TenantMaintenanceRequestController::class, 'create'])->name('maintenance.create');
        Route::post('/manutencao', [TenantMaintenanceRequestController::class, 'store'])->name('maintenance.store');
        Route::get('/manutencao/{maintenanceRequest}', [TenantMaintenanceRequestController::class, 'show'])->name('maintenance.show');
        Route::get('/vistorias', [TenantInspectionController::class, 'index'])->name('inspections.index');
        Route::get('/vistorias/{propertyInspection}', [TenantInspectionController::class, 'show'])->name('inspections.show');
        Route::get('/comunicacoes', [TenantCommunicationController::class, 'index'])->name('communications.index');
        Route::get('/comunicacoes/criar', [TenantCommunicationController::class, 'create'])->name('communications.create');
        Route::post('/comunicacoes', [TenantCommunicationController::class, 'store'])->name('communications.store');
        Route::get('/comunicacoes/{tenantCommunication}', [TenantCommunicationController::class, 'show'])->name('communications.show');
        Route::post('/comunicacoes/{tenantCommunication}/mensagens', [TenantCommunicationMessageController::class, 'store'])->name('communications.messages.store');
    });

    Route::middleware('role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor')
        ->group(function () {
            Route::prefix('admin')->name('admin.')->group(function () {
                Route::post('programs/{program}/publish', [AdminProgramController::class, 'publish'])
                    ->name('programs.publish');
                Route::resource('programs', AdminProgramController::class);

                Route::post('contests/{contest}/publish', [AdminContestController::class, 'publish'])
                    ->name('contests.publish');
                Route::resource('contests', AdminContestController::class);

                Route::resource('document-types', AdminDocumentTypeController::class)
                    ->parameters(['document-types' => 'documentType'])
                    ->except(['show']);
                Route::resource('required-documents', AdminRequiredDocumentController::class)
                    ->parameters(['required-documents' => 'requiredDocument'])
                    ->except(['show']);
                Route::get('document-reviews', [AdminDocumentReviewController::class, 'index'])
                    ->name('document-reviews.index');
                Route::get('document-reviews/{documentSubmission}', [AdminDocumentReviewController::class, 'show'])
                    ->name('document-reviews.show');
                Route::post('document-reviews/{documentSubmission}/under-review', [AdminDocumentReviewController::class, 'underReview'])
                    ->name('document-reviews.under-review');
                Route::post('document-reviews/{documentSubmission}/validate', [AdminDocumentReviewController::class, 'validateDocument'])
                    ->name('document-reviews.validate');
                Route::post('document-reviews/{documentSubmission}/reject', [AdminDocumentReviewController::class, 'reject'])
                    ->name('document-reviews.reject');
                Route::get('document-reviews/{documentSubmission}/download', [AdminDocumentReviewController::class, 'download'])
                    ->name('document-reviews.download');
            });

            Route::prefix('backoffice')->name('backoffice.')->group(function () {
                Route::resource('sorteios', BackofficeLotteryDrawController::class)
                    ->parameters(['sorteios' => 'lotteryDraw'])
                    ->names('lottery-draws')
                    ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
                Route::post('sorteios/{lotteryDraw}/participantes/carregar', [BackofficeLotteryParticipantController::class, 'load'])
                    ->name('lottery-draws.participants.load');
                Route::post('sorteios/{lotteryDraw}/participantes/bloquear', [BackofficeLotteryParticipantController::class, 'lock'])
                    ->name('lottery-draws.participants.lock');
                Route::post('sorteios/{lotteryDraw}/executar', [BackofficeLotteryDrawController::class, 'run'])
                    ->name('lottery-draws.run');
                Route::post('sorteios/{lotteryDraw}/validar', [BackofficeLotteryDrawController::class, 'validateResult'])
                    ->name('lottery-draws.validate');
                Route::post('sorteios/{lotteryDraw}/cancelar', [BackofficeLotteryDrawController::class, 'cancel'])
                    ->name('lottery-draws.cancel');
                Route::get('sorteios/{lotteryDraw}/resultados', [BackofficeLotteryResultController::class, 'index'])
                    ->name('lottery-draws.results.index');
                Route::post('sorteios/{lotteryDraw}/convocatorias/gerar', [BackofficeDrawConvocationController::class, 'generate'])
                    ->name('lottery-draws.convocations.generate');
                Route::get('sorteios/{lotteryDraw}/presencas', [BackofficeDrawAttendanceController::class, 'index'])
                    ->name('lottery-draws.attendance.index');
                Route::post('sorteios/{lotteryDraw}/presencas', [BackofficeDrawAttendanceController::class, 'store'])
                    ->name('lottery-draws.attendance.store');
                Route::post('sorteios/{lotteryDraw}/presencas/lote', [BackofficeDrawAttendanceController::class, 'bulkStore'])
                    ->name('lottery-draws.attendance.bulk-store');
                Route::post('sorteios/{lotteryDraw}/ranking/atualizar', [BackofficeRankingUpdateRunController::class, 'apply'])
                    ->name('lottery-draws.ranking.update');
                Route::post('sorteios/{lotteryDraw}/relatorio-pos-sorteio/gerar', [BackofficePostDrawReportController::class, 'generate'])
                    ->name('lottery-draws.post-draw-report.generate');

                Route::get('convocatorias-sorteio', [BackofficeDrawConvocationController::class, 'index'])
                    ->name('draw-convocations.index');
                Route::get('convocatorias-sorteio/{drawConvocation}', [BackofficeDrawConvocationController::class, 'show'])
                    ->name('draw-convocations.show');
                Route::post('convocatorias-sorteio/{drawConvocation}/enviar', [BackofficeDrawConvocationController::class, 'send'])
                    ->name('draw-convocations.send');

                Route::post('resultados-sorteio/{lotteryResult}/vencedor', [BackofficeWinnerRegistrationController::class, 'store'])
                    ->name('lottery-results.winner.store');

                Route::get('relatorios-pos-sorteio/{postDrawReport}', [BackofficePostDrawReportController::class, 'show'])
                    ->name('post-draw-reports.show');
                Route::get('relatorios-pos-sorteio/{postDrawReport}/download', [BackofficePostDrawReportController::class, 'download'])
                    ->name('post-draw-reports.download');

                Route::get('entrega-chaves', [BackofficeKeyHandoverAppointmentController::class, 'index'])
                    ->name('key-handovers.index');
                Route::get('entrega-chaves/criar', [BackofficeKeyHandoverAppointmentController::class, 'create'])
                    ->name('key-handovers.create');
                Route::post('entrega-chaves', [BackofficeKeyHandoverAppointmentController::class, 'store'])
                    ->name('key-handovers.store');
                Route::get('entrega-chaves/{keyHandoverAppointment}', [BackofficeKeyHandoverAppointmentController::class, 'show'])
                    ->name('key-handovers.show');
                Route::match(['put', 'patch'], 'entrega-chaves/{keyHandoverAppointment}', [BackofficeKeyHandoverAppointmentController::class, 'update'])
                    ->name('key-handovers.update');
                Route::post('entrega-chaves/{keyHandoverAppointment}/concluir', [BackofficeKeyHandoverAppointmentController::class, 'complete'])
                    ->name('key-handovers.complete');
                Route::post('entrega-chaves/{keyHandoverAppointment}/cancelar', [BackofficeKeyHandoverAppointmentController::class, 'cancel'])
                    ->name('key-handovers.cancel');

                Route::get('transicoes-inquilino', [BackofficeTenantTransitionController::class, 'index'])
                    ->name('tenant-transitions.index');
                Route::post('transicoes-inquilino', [BackofficeTenantTransitionController::class, 'run'])
                    ->name('tenant-transitions.run');

                Route::get('fechos-concurso/{contestClosure}', [BackofficeContestClosureController::class, 'show'])
                    ->name('contest-closures.show');
                Route::post('concursos/{contest}/fechar', [BackofficeContestClosureController::class, 'close'])
                    ->name('contests.close');

                Route::prefix('security')
                    ->name('security.')
                    ->middleware(['active.backoffice', 'mfa.backoffice', 'log.backoffice'])
                    ->group(function () {
                        Route::get('/', BackofficeSecurityDashboardController::class)->name('dashboard');

                        Route::prefix('mfa')->name('mfa.')->group(function () {
                            Route::get('/', [BackofficeMfaController::class, 'index'])->name('index');
                            Route::post('enable', [BackofficeMfaController::class, 'enable'])->name('enable');
                            Route::post('verify', [BackofficeMfaController::class, 'verify'])->name('verify');
                            Route::post('{mfaDevice}/confirm', [BackofficeMfaController::class, 'confirm'])->name('confirm');
                            Route::post('{mfaDevice}/disable', [BackofficeMfaController::class, 'disable'])->name('disable');
                            Route::post('recovery-codes/regenerate', [BackofficeMfaController::class, 'regenerate'])->name('recovery-codes.regenerate');
                        });

                        Route::get('permission-reviews', [BackofficePermissionReviewController::class, 'index'])->name('permission-reviews.index');
                        Route::post('permission-reviews', [BackofficePermissionReviewController::class, 'store'])->name('permission-reviews.store');
                        Route::get('permission-reviews/{permissionReview}', [BackofficePermissionReviewController::class, 'show'])->name('permission-reviews.show');
                        Route::post('permission-reviews/{permissionReview}/complete', [BackofficePermissionReviewController::class, 'complete'])->name('permission-reviews.complete');

                        Route::prefix('audit')->name('audit.')->group(function () {
                            Route::get('events', [BackofficeSecurityAuditController::class, 'events'])->name('events.index');
                            Route::get('events/{auditEvent}', [BackofficeSecurityAuditController::class, 'event'])->name('events.show');
                            Route::get('access-logs', [BackofficeSecurityAuditController::class, 'accessLogs'])->name('access-logs.index');
                            Route::get('sensitive-logs', [BackofficeSecurityAuditController::class, 'sensitiveLogs'])->name('sensitive-logs.index');
                        });

                        Route::prefix('privacy')->name('privacy.')->group(function () {
                            Route::get('purposes', [BackofficePrivacyController::class, 'purposes'])->name('purposes.index');
                            Route::post('purposes', [BackofficePrivacyController::class, 'storePurpose'])->name('purposes.store');
                            Route::match(['put', 'patch'], 'purposes/{consentPurpose}', [BackofficePrivacyController::class, 'updatePurpose'])->name('purposes.update');

                            Route::get('requests', [BackofficePrivacyController::class, 'requests'])->name('requests.index');
                            Route::post('requests', [BackofficePrivacyController::class, 'storeRequest'])->name('requests.store');
                            Route::get('requests/{dataSubjectRequest}', [BackofficePrivacyController::class, 'showRequest'])->name('requests.show');
                            Route::post('requests/{dataSubjectRequest}/assign', [BackofficePrivacyController::class, 'assignRequest'])->name('requests.assign');
                            Route::post('requests/{dataSubjectRequest}/complete', [BackofficePrivacyController::class, 'completeRequest'])->name('requests.complete');
                            Route::post('requests/{dataSubjectRequest}/reject', [BackofficePrivacyController::class, 'rejectRequest'])->name('requests.reject');
                            Route::post('requests/{dataSubjectRequest}/exports', [BackofficePrivacyController::class, 'generateExport'])->name('requests.exports.store');
                            Route::get('exports/{dataExportPackage}', [BackofficePrivacyController::class, 'showExport'])->name('exports.show');
                            Route::get('exports/{dataExportPackage}/download', [BackofficePrivacyController::class, 'downloadExport'])->name('exports.download');

                            Route::get('retention', [BackofficePrivacyController::class, 'retention'])->name('retention.index');
                            Route::post('retention', [BackofficePrivacyController::class, 'storeRetention'])->name('retention.store');
                            Route::match(['put', 'patch'], 'retention/{retentionPolicy}', [BackofficePrivacyController::class, 'updateRetention'])->name('retention.update');
                            Route::post('retention/{retentionPolicy}/simulate', [BackofficePrivacyController::class, 'simulateRetention'])->name('retention.simulate');
                            Route::post('retention-executions/{retentionExecution}/approve', [BackofficePrivacyController::class, 'approveRetention'])->name('retention-executions.approve');
                            Route::post('retention-executions/{retentionExecution}/run', [BackofficePrivacyController::class, 'runRetention'])->name('retention-executions.run');

                            Route::get('anonymization', [BackofficePrivacyController::class, 'anonymization'])->name('anonymization.index');
                            Route::post('anonymization', [BackofficePrivacyController::class, 'storeAnonymization'])->name('anonymization.store');
                            Route::get('anonymization/{anonymizationRequest}', [BackofficePrivacyController::class, 'showAnonymization'])->name('anonymization.show');
                            Route::post('anonymization/{anonymizationRequest}/approve', [BackofficePrivacyController::class, 'approveAnonymization'])->name('anonymization.approve');
                            Route::post('anonymization/{anonymizationRequest}/run', [BackofficePrivacyController::class, 'runAnonymization'])->name('anonymization.run');
                        });

                        Route::get('alerts', [BackofficeSecurityOperationsController::class, 'alerts'])->name('alerts.index');
                        Route::post('alert-rules', [BackofficeSecurityOperationsController::class, 'storeAlertRule'])->name('alert-rules.store');
                        Route::match(['put', 'patch'], 'alert-rules/{securityAlertRule}', [BackofficeSecurityOperationsController::class, 'updateAlertRule'])->name('alert-rules.update');
                        Route::post('alerts/{securityAlert}/review', [BackofficeSecurityOperationsController::class, 'reviewAlert'])->name('alerts.review');
                        Route::post('alerts/{securityAlert}/resolve', [BackofficeSecurityOperationsController::class, 'resolveAlert'])->name('alerts.resolve');
                        Route::get('storage', [BackofficeSecurityOperationsController::class, 'storage'])->name('storage.index');
                        Route::get('encrypted-fields', [BackofficeSecurityOperationsController::class, 'encryptedFields'])->name('encrypted-fields.index');
                        Route::get('backups', [BackofficeSecurityOperationsController::class, 'backups'])->name('backups.index');
                        Route::post('backups', [BackofficeSecurityOperationsController::class, 'storeBackupReview'])->name('backups.store');
                        Route::get('checklists', [BackofficeSecurityOperationsController::class, 'checklists'])->name('checklists.index');
                        Route::post('checklists', [BackofficeSecurityOperationsController::class, 'storeChecklist'])->name('checklists.store');
                        Route::get('checklists/{securityChecklist}', [BackofficeSecurityOperationsController::class, 'showChecklist'])->name('checklists.show');
                        Route::match(['put', 'patch'], 'checklist-items/{securityChecklistItem}', [BackofficeSecurityOperationsController::class, 'updateChecklistItem'])->name('checklist-items.update');
                        Route::post('checklists/{securityChecklist}/approve', [BackofficeSecurityOperationsController::class, 'approveChecklist'])->name('checklists.approve');
                    });

                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', BackofficeReportingController::class)->name('index');
                    Route::get('dashboard', BackofficeOperationalDashboardController::class)->name('dashboard');
                    Route::get('operational', BackofficeOperationalDashboardController::class)->name('operational');
                    Route::get('executive', BackofficeExecutiveDashboardController::class)->name('executive');

                    Route::get('indicators', [BackofficeIndicatorController::class, 'index'])->name('indicators.index');
                    Route::get('indicators/{indicatorDefinition}', [BackofficeIndicatorController::class, 'show'])->name('indicators.show');
                    Route::post('indicators', [BackofficeIndicatorController::class, 'store'])->name('indicators.store');
                    Route::match(['put', 'patch'], 'indicators/{indicatorDefinition}', [BackofficeIndicatorController::class, 'update'])->name('indicators.update');

                    Route::resource('definitions', BackofficeReportDefinitionController::class)
                        ->parameters(['definitions' => 'reportDefinition']);
                    Route::get('runs', [BackofficeReportRunController::class, 'index'])->name('runs.index');
                    Route::post('definitions/{reportDefinition}/run', [BackofficeReportRunController::class, 'store'])->name('runs.store');
                    Route::get('runs/{reportRun}', [BackofficeReportRunController::class, 'show'])->name('runs.show');

                    Route::get('exports', [BackofficeReportExportController::class, 'index'])->name('exports.index');
                    Route::post('definitions/{reportDefinition}/export', [BackofficeReportExportController::class, 'store'])->name('exports.store');
                    Route::get('exports/{reportExport}', [BackofficeReportExportController::class, 'show'])->name('exports.show');
                    Route::get('exports/{reportExport}/download', BackofficeReportDownloadController::class)->name('exports.download');

                    Route::get('filter-presets', [BackofficeReportFilterPresetController::class, 'index'])->name('filter-presets.index');
                    Route::post('filter-presets', [BackofficeReportFilterPresetController::class, 'store'])->name('filter-presets.store');
                    Route::match(['put', 'patch'], 'filter-presets/{reportFilterPreset}', [BackofficeReportFilterPresetController::class, 'update'])->name('filter-presets.update');
                    Route::delete('filter-presets/{reportFilterPreset}', [BackofficeReportFilterPresetController::class, 'destroy'])->name('filter-presets.destroy');

                    Route::resource('dashboards', BackofficeDashboardDefinitionController::class)
                        ->parameters(['dashboards' => 'dashboardDefinition'])
                        ->except(['show']);
                    Route::post('widgets', [BackofficeDashboardWidgetController::class, 'store'])->name('widgets.store');
                    Route::match(['put', 'patch'], 'widgets/{dashboardWidget}', [BackofficeDashboardWidgetController::class, 'update'])->name('widgets.update');
                    Route::delete('widgets/{dashboardWidget}', [BackofficeDashboardWidgetController::class, 'destroy'])->name('widgets.destroy');

                    Route::get('access-logs', [BackofficeReportAuditController::class, 'accessLogs'])->name('access-logs.index');
                    Route::get('download-logs', [BackofficeReportAuditController::class, 'downloadLogs'])->name('download-logs.index');
                });

                Route::prefix('operational')->name('operational.')->group(function () {
                    Route::get('dashboard', [BackofficeSprint24OperationalDashboardController::class, 'index'])
                        ->name('dashboard');
                    Route::get('executive-dashboard', [BackofficeSprint24ExecutiveDashboardController::class, 'index'])
                        ->name('executive-dashboard');
                });

                Route::prefix('public-portal')->name('public-portal.')->group(function () {
                    Route::get('settings', [BackofficePublicPortalSettingController::class, 'edit'])->name('settings.edit');
                    Route::match(['put', 'patch'], 'settings', [BackofficePublicPortalSettingController::class, 'update'])->name('settings.update');

                    Route::resource('links', BackofficePublicPortalLinkController::class)
                        ->parameters(['links' => 'link'])
                        ->except(['show']);

                    Route::get('housing-units/{housingUnit}/edit', [BackofficePublicHousingUnitProfileController::class, 'edit'])
                        ->name('housing-units.edit');
                    Route::match(['put', 'patch'], 'housing-units/{housingUnit}', [BackofficePublicHousingUnitProfileController::class, 'update'])
                        ->name('housing-units.update');
                    Route::post('housing-units/{housingUnit}/publish', [BackofficePublicHousingUnitProfileController::class, 'publish'])
                        ->name('housing-units.publish');
                    Route::post('housing-units/{housingUnit}/unpublish', [BackofficePublicHousingUnitProfileController::class, 'unpublish'])
                        ->name('housing-units.unpublish');
                    Route::get('housing-units/{housingUnit}/preview', [BackofficePublicHousingUnitProfileController::class, 'preview'])
                        ->name('housing-units.preview');

                    Route::post('housing-units/{housingUnit}/images', [BackofficePublicHousingUnitImageController::class, 'store'])
                        ->name('housing-units.images.store');
                    Route::match(['put', 'patch'], 'images/{image}', [BackofficePublicHousingUnitImageController::class, 'update'])
                        ->name('images.update');
                    Route::delete('images/{image}', [BackofficePublicHousingUnitImageController::class, 'destroy'])
                        ->name('images.destroy');

                    Route::post('housing-units/{housingUnit}/documents', [BackofficePublicHousingUnitDocumentController::class, 'store'])
                        ->name('housing-units.documents.store');
                    Route::match(['put', 'patch'], 'documents/{document}', [BackofficePublicHousingUnitDocumentController::class, 'update'])
                        ->name('documents.update');
                    Route::delete('documents/{document}', [BackofficePublicHousingUnitDocumentController::class, 'destroy'])
                        ->name('documents.destroy');
                });

                Route::prefix('simulator')->name('simulator.')->group(function () {
                    Route::get('insights', [BackofficeSimulatorInsightController::class, 'index'])
                        ->name('insights.index');
                    Route::get('insights/{simulationSession}', [BackofficeSimulatorInsightController::class, 'show'])
                        ->name('insights.show');
                    Route::get('configuration', [BackofficeSimulatorConfigurationController::class, 'edit'])
                        ->name('configuration.edit');
                    Route::match(['put', 'patch'], 'configuration', [BackofficeSimulatorConfigurationController::class, 'update'])
                        ->name('configuration.update');
                });

                Route::resource('visit-availabilities', BackofficeVisitAvailabilityController::class)
                    ->parameters(['visit-availabilities' => 'visitAvailability']);
                Route::post('visit-availabilities/{visitAvailability}/slots', [BackofficeVisitAvailabilityController::class, 'generateSlots'])
                    ->name('visit-availabilities.slots.generate');

                Route::get('visit-slots', [BackofficeVisitSlotController::class, 'index'])
                    ->name('visit-slots.index');
                Route::post('visit-slots/{visitSlot}/block', [BackofficeVisitSlotController::class, 'block'])
                    ->name('visit-slots.block');
                Route::post('visit-slots/{visitSlot}/unblock', [BackofficeVisitSlotController::class, 'unblock'])
                    ->name('visit-slots.unblock');

                Route::get('housing-visits', [BackofficeHousingVisitController::class, 'index'])
                    ->name('housing-visits.index');
                Route::get('housing-visits/{housingVisit}', [BackofficeHousingVisitController::class, 'show'])
                    ->name('housing-visits.show');
                Route::post('housing-visits/{housingVisit}/confirm', [BackofficeHousingVisitController::class, 'confirm'])
                    ->name('housing-visits.confirm');
                Route::post('housing-visits/{housingVisit}/complete', [BackofficeHousingVisitController::class, 'complete'])
                    ->name('housing-visits.complete');
                Route::post('housing-visits/{housingVisit}/cancel', [BackofficeHousingVisitController::class, 'cancel'])
                    ->name('housing-visits.cancel');
                Route::post('housing-visits/{housingVisit}/reject', [BackofficeHousingVisitController::class, 'reject'])
                    ->name('housing-visits.reject');

                Route::get('support-tickets', [BackofficeSupportTicketController::class, 'index'])
                    ->name('support-tickets.index');
                Route::get('support-tickets/{supportTicket}', [BackofficeSupportTicketController::class, 'show'])
                    ->name('support-tickets.show');
                Route::post('support-tickets/{supportTicket}/assign', [BackofficeSupportTicketController::class, 'assign'])
                    ->name('support-tickets.assign');
                Route::post('support-tickets/{supportTicket}/status', [BackofficeSupportTicketController::class, 'updateStatus'])
                    ->name('support-tickets.status');
                Route::post('support-tickets/{supportTicket}/messages', [BackofficeSupportTicketMessageController::class, 'store'])
                    ->name('support-ticket-messages.store');
                Route::get('support-ticket-attachments/{supportTicketAttachment}/download', [BackofficeSupportTicketAttachmentController::class, 'download'])
                    ->name('support-ticket-attachments.download');

                Route::resource('contextual-faqs', BackofficeContextualFaqController::class)
                    ->parameters(['contextual-faqs' => 'contextualFaq'])
                    ->except(['show']);

                Route::get('application-inconsistencies', [BackofficeApplicationSimulationInconsistencyController::class, 'index'])
                    ->name('application-inconsistencies.index');
                Route::post('application-inconsistencies/{inconsistency}/resolve', [BackofficeApplicationSimulationInconsistencyController::class, 'resolve'])
                    ->name('application-inconsistencies.resolve');

                Route::get('applications', [BackofficeApplicationController::class, 'index'])
                    ->name('applications.index');
                Route::get('applications/{application}', [BackofficeApplicationController::class, 'show'])
                    ->name('applications.show');
                Route::get('applications/{application}/report', [BackofficeApplicationReportController::class, 'show'])
                    ->name('applications.report.show');
                Route::post('applications/{application}/report', [BackofficeApplicationReportController::class, 'generate'])
                    ->name('applications.report.generate');
                Route::get('application-reports/{applicationReport}/download', [BackofficeApplicationReportController::class, 'download'])
                    ->name('application-reports.download');
                Route::get('applications/{application}/document-dossier', [BackofficeDocumentDossierController::class, 'show'])
                    ->name('applications.document-dossier.show');
                Route::post('applications/{application}/document-dossier', [BackofficeDocumentDossierController::class, 'generate'])
                    ->name('applications.document-dossier.generate');
                Route::patch('document-dossiers/{documentDossier}', [BackofficeDocumentDossierController::class, 'update'])
                    ->name('document-dossiers.update');
                Route::get('document-dossiers/{documentDossier}/download', [BackofficeDocumentDossierController::class, 'download'])
                    ->name('document-dossiers.download');
                Route::post('applications/{application}/process-confirmations', [BackofficeProcessConfirmationController::class, 'generate'])
                    ->name('applications.process-confirmations.generate');
                Route::get('applications/{application}/timeline', [BackofficeProcessTimelineController::class, 'show'])
                    ->name('applications.timeline');
                Route::get('applications/{application}/public-status', [BackofficeApplicationPublicStatusController::class, 'show'])
                    ->name('applications.public-status.show');
                Route::put('applications/{application}/public-status', [BackofficeApplicationPublicStatusController::class, 'update'])
                    ->name('applications.public-status.update');
                Route::get('documentos-adicionais/pedidos', [BackofficeAdditionalDocumentRequestController::class, 'index'])
                    ->name('additional-document-requests.index');
                Route::post('applications/{application}/documentos-adicionais/pedidos', [BackofficeAdditionalDocumentRequestController::class, 'store'])
                    ->name('additional-document-requests.store');
                Route::get('documentos-adicionais/submissoes', [BackofficeAdditionalDocumentSubmissionController::class, 'index'])
                    ->name('additional-document-submissions.index');
                Route::get('documentos-adicionais/submissoes/{additionalDocumentSubmission}', [BackofficeAdditionalDocumentSubmissionController::class, 'show'])
                    ->name('additional-document-submissions.show');
                Route::post('documentos-adicionais/submissoes/{additionalDocumentSubmission}/decidir', [BackofficeAdditionalDocumentSubmissionController::class, 'decide'])
                    ->name('additional-document-submissions.decide');
                Route::get('documentos/ia/classificacoes', [BackofficeDocumentAiClassificationController::class, 'index'])
                    ->name('document-ai.classifications.index');
                Route::get('documentos/ia/classificacoes/{analysis}', [BackofficeDocumentAiClassificationController::class, 'show'])
                    ->name('document-ai.classifications.show');
                Route::post('documentos/ia/classificacoes/{analysis}/revisao-manual', [BackofficeDocumentAiClassificationController::class, 'markManualReview'])
                    ->name('document-ai.classifications.manual-review');
                Route::get('documentos/ia/extracoes', [BackofficeDocumentAiExtractionController::class, 'index'])
                    ->name('document-ai.extractions.index');
                Route::get('documentos/ia/extracoes/{analysis}', [BackofficeDocumentAiExtractionController::class, 'show'])
                    ->name('document-ai.extractions.show');
                Route::post('documentos/ia/campos/{field}/revisao', [BackofficeDocumentAiExtractionController::class, 'markFieldForReview'])
                    ->name('document-ai.fields.review');
                Route::get('documentos/ia/assistente', [BackofficeDocumentAiAssistantController::class, 'index'])
                    ->name('document-ai.assistant.index');
                Route::get('documentos/ia/assistente/scores/{score}', [BackofficeDocumentAiAssistantController::class, 'score'])
                    ->name('document-ai.assistant.score');
                Route::get('documentos/ia/assistente/{analysis}', [BackofficeDocumentAiAssistantController::class, 'show'])
                    ->name('document-ai.assistant.show');
                Route::post('documentos/ia/assistente/{analysis}/recalcular', [BackofficeDocumentAiAssistantController::class, 'recalculate'])
                    ->name('document-ai.assistant.recalculate');
                Route::put('documentos/ia/sugestoes/{suggestion}', [BackofficeDocumentAiAssistantController::class, 'updateSuggestion'])
                    ->name('document-ai.assistant.suggestions.update');
                Route::post('documentos/ia/sugestoes/{suggestion}/aceitar', [BackofficeDocumentAiAssistantController::class, 'acceptSuggestion'])
                    ->name('document-ai.assistant.suggestions.accept');
                Route::post('documentos/ia/sugestoes/{suggestion}/descartar', [BackofficeDocumentAiAssistantController::class, 'dismissSuggestion'])
                    ->name('document-ai.assistant.suggestions.dismiss');
                Route::get('documentos/ia/validacoes', [BackofficeDocumentAiValidationController::class, 'index'])
                    ->name('document-ai.validations.index');
                Route::get('documentos/ia/validacoes/detalhe/{validation}', [BackofficeDocumentAiValidationController::class, 'validation'])
                    ->name('document-ai.validations.validation');
                Route::post('documentos/ia/validacoes/detalhe/{validation}/revisao-manual', [BackofficeDocumentAiValidationController::class, 'markManualReview'])
                    ->name('document-ai.validations.manual-review');
                Route::get('documentos/ia/validacoes/{application}', [BackofficeDocumentAiValidationController::class, 'show'])
                    ->name('document-ai.validations.show');
                Route::post('documentos/ia/validacoes/{application}/reprocessar', [BackofficeDocumentAiValidationController::class, 'rerun'])
                    ->name('document-ai.validations.rerun');
                Route::get('desistencias', [BackofficeControlledWithdrawalController::class, 'index'])
                    ->name('withdrawals.index');
                Route::get('desistencias/{controlledWithdrawal}', [BackofficeControlledWithdrawalController::class, 'show'])
                    ->name('withdrawals.show');
                Route::post('desistencias/{controlledWithdrawal}/processar', [BackofficeControlledWithdrawalController::class, 'process'])
                    ->name('withdrawals.process');
                Route::get('reutilizacao-dados', [BackofficeFutureApplicationDataReuseController::class, 'index'])
                    ->name('data-reuse.index');
                Route::get('audiencias-previas', [BackofficePreliminaryHearingSubmissionController::class, 'index'])
                    ->name('preliminary-hearings.index');
                Route::get('audiencias-previas/{preliminaryHearingSubmission}', [BackofficePreliminaryHearingSubmissionController::class, 'show'])
                    ->name('preliminary-hearings.show');
                Route::post('audiencias-previas/{preliminaryHearingSubmission}/decidir', [BackofficePreliminaryHearingSubmissionController::class, 'decide'])
                    ->name('preliminary-hearings.decide');

                Route::get('application-intake', [BackofficeApplicationIntakeController::class, 'index'])
                    ->name('application-intake.index');
                Route::post('application-intake/{application}/create-process', [BackofficeApplicationIntakeController::class, 'createProcess'])
                    ->name('application-intake.create-process');
                Route::post('application-intake/create-processes-batch', [BackofficeApplicationIntakeController::class, 'createProcessesBatch'])
                    ->name('application-intake.create-processes-batch');

                Route::get('administrative-processes', [BackofficeAdministrativeProcessController::class, 'index'])
                    ->name('administrative-processes.index');
                Route::get('administrative-processes/{administrativeProcess}', [BackofficeAdministrativeProcessController::class, 'show'])
                    ->name('administrative-processes.show');
                Route::post('administrative-processes/{administrativeProcess}/assign', [BackofficeAdministrativeProcessController::class, 'assign'])
                    ->name('administrative-processes.assign');
                Route::post('administrative-processes/{administrativeProcess}/start-preliminary-review', [BackofficeAdministrativeProcessController::class, 'startPreliminaryReview'])
                    ->name('administrative-processes.start-preliminary-review');
                Route::post('administrative-processes/{administrativeProcess}/start-document-review', [BackofficeAdministrativeProcessController::class, 'startDocumentReview'])
                    ->name('administrative-processes.start-document-review');
                Route::post('administrative-processes/{administrativeProcess}/start-eligibility-review', [BackofficeAdministrativeProcessController::class, 'startEligibilityReview'])
                    ->name('administrative-processes.start-eligibility-review');
                Route::get('administrative-processes/{administrativeProcess}/timeline', [BackofficeAdministrativeProcessController::class, 'timeline'])
                    ->name('administrative-processes.timeline');

                Route::get('administrative-processes/{administrativeProcess}/reviews/create', [BackofficeApplicationReviewController::class, 'create'])
                    ->name('application-reviews.create');
                Route::post('administrative-processes/{administrativeProcess}/reviews', [BackofficeApplicationReviewController::class, 'store'])
                    ->name('application-reviews.store');
                Route::get('application-reviews/{applicationReview}', [BackofficeApplicationReviewController::class, 'show'])
                    ->name('application-reviews.show');
                Route::post('application-reviews/{applicationReview}/complete', [BackofficeApplicationReviewController::class, 'complete'])
                    ->name('application-reviews.complete');

                Route::get('administrative-processes/{administrativeProcess}/correction-requests', [BackofficeCorrectionRequestController::class, 'index'])
                    ->name('correction-requests.index');
                Route::get('administrative-processes/{administrativeProcess}/correction-requests/create', [BackofficeCorrectionRequestController::class, 'create'])
                    ->name('correction-requests.create');
                Route::post('administrative-processes/{administrativeProcess}/correction-requests', [BackofficeCorrectionRequestController::class, 'store'])
                    ->name('correction-requests.store');
                Route::get('correction-requests/{correctionRequest}', [BackofficeCorrectionRequestController::class, 'show'])
                    ->name('correction-requests.show');
                Route::get('correction-requests/{correctionRequest}/edit', [BackofficeCorrectionRequestController::class, 'edit'])
                    ->name('correction-requests.edit');
                Route::match(['put', 'patch'], 'correction-requests/{correctionRequest}', [BackofficeCorrectionRequestController::class, 'update'])
                    ->name('correction-requests.update');
                Route::post('correction-requests/{correctionRequest}/issue', [BackofficeCorrectionRequestController::class, 'issue'])
                    ->name('correction-requests.issue');
                Route::post('correction-requests/{correctionRequest}/cancel', [BackofficeCorrectionRequestController::class, 'cancel'])
                    ->name('correction-requests.cancel');
                Route::post('correction-requests/{correctionRequest}/close', [BackofficeCorrectionRequestController::class, 'close'])
                    ->name('correction-requests.close');
                Route::post('correction-requests/{correctionRequest}/mark-overdue', [BackofficeCorrectionRequestController::class, 'markOverdue'])
                    ->name('correction-requests.mark-overdue');

                Route::get('correction-responses/{correctionResponse}', [BackofficeCorrectionResponseReviewController::class, 'show'])
                    ->name('correction-responses.show');
                Route::post('correction-responses/{correctionResponse}/accept', [BackofficeCorrectionResponseReviewController::class, 'accept'])
                    ->name('correction-responses.accept');
                Route::post('correction-responses/{correctionResponse}/reject', [BackofficeCorrectionResponseReviewController::class, 'reject'])
                    ->name('correction-responses.reject');
                Route::post('correction-responses/{correctionResponse}/request-more-information', [BackofficeCorrectionResponseReviewController::class, 'requestMoreInformation'])
                    ->name('correction-responses.request-more-information');

                Route::get('administrative-processes/{administrativeProcess}/decisions/create-admission', [BackofficeAdministrativeDecisionController::class, 'createAdmission'])
                    ->name('administrative-decisions.create-admission');
                Route::post('administrative-processes/{administrativeProcess}/decisions/admission', [BackofficeAdministrativeDecisionController::class, 'storeAdmission'])
                    ->name('administrative-decisions.store-admission');
                Route::get('administrative-processes/{administrativeProcess}/decisions/create-non-admission', [BackofficeAdministrativeDecisionController::class, 'createNonAdmission'])
                    ->name('administrative-decisions.create-non-admission');
                Route::post('administrative-processes/{administrativeProcess}/decisions/non-admission', [BackofficeAdministrativeDecisionController::class, 'storeNonAdmission'])
                    ->name('administrative-decisions.store-non-admission');
                Route::get('administrative-decisions/{administrativeDecision}', [BackofficeAdministrativeDecisionController::class, 'show'])
                    ->name('administrative-decisions.show');
                Route::post('administrative-decisions/{administrativeDecision}/approve', [BackofficeAdministrativeDecisionController::class, 'approve'])
                    ->name('administrative-decisions.approve');
                Route::post('administrative-decisions/{administrativeDecision}/cancel', [BackofficeAdministrativeDecisionController::class, 'cancel'])
                    ->name('administrative-decisions.cancel');

                Route::get('administrative-tasks', [BackofficeAdministrativeTaskController::class, 'index'])
                    ->name('administrative-tasks.index');
                Route::post('administrative-processes/{administrativeProcess}/tasks', [BackofficeAdministrativeTaskController::class, 'store'])
                    ->name('administrative-tasks.store');
                Route::match(['put', 'patch'], 'administrative-tasks/{administrativeTask}', [BackofficeAdministrativeTaskController::class, 'update'])
                    ->name('administrative-tasks.update');
                Route::post('administrative-tasks/{administrativeTask}/complete', [BackofficeAdministrativeTaskController::class, 'complete'])
                    ->name('administrative-tasks.complete');
                Route::post('administrative-tasks/{administrativeTask}/cancel', [BackofficeAdministrativeTaskController::class, 'cancel'])
                    ->name('administrative-tasks.cancel');

                Route::post('administrative-processes/{administrativeProcess}/notes', [BackofficeAdministrativeProcessNoteController::class, 'store'])
                    ->name('administrative-notes.store');
                Route::match(['put', 'patch'], 'administrative-notes/{administrativeProcessNote}', [BackofficeAdministrativeProcessNoteController::class, 'update'])
                    ->name('administrative-notes.update');
                Route::delete('administrative-notes/{administrativeProcessNote}', [BackofficeAdministrativeProcessNoteController::class, 'destroy'])
                    ->name('administrative-notes.destroy');

                Route::get('administrative-workflow-configs', [BackofficeAdministrativeWorkflowConfigController::class, 'index'])
                    ->name('administrative-workflow-configs.index');
                Route::get('administrative-workflow-configs/create', [BackofficeAdministrativeWorkflowConfigController::class, 'create'])
                    ->name('administrative-workflow-configs.create');
                Route::post('administrative-workflow-configs', [BackofficeAdministrativeWorkflowConfigController::class, 'store'])
                    ->name('administrative-workflow-configs.store');
                Route::get('administrative-workflow-configs/{administrativeWorkflowConfig}/edit', [BackofficeAdministrativeWorkflowConfigController::class, 'edit'])
                    ->name('administrative-workflow-configs.edit');
                Route::match(['put', 'patch'], 'administrative-workflow-configs/{administrativeWorkflowConfig}', [BackofficeAdministrativeWorkflowConfigController::class, 'update'])
                    ->name('administrative-workflow-configs.update');
                Route::post('administrative-workflow-configs/{administrativeWorkflowConfig}/activate', [BackofficeAdministrativeWorkflowConfigController::class, 'activate'])
                    ->name('administrative-workflow-configs.activate');
                Route::post('administrative-workflow-configs/{administrativeWorkflowConfig}/deactivate', [BackofficeAdministrativeWorkflowConfigController::class, 'deactivate'])
                    ->name('administrative-workflow-configs.deactivate');

                Route::prefix('lists')->name('lists.')->group(function () {
                    Route::get('automation/{contest}', [BackofficeListAutomationController::class, 'index'])
                        ->name('automation.index');
                    Route::post('automation/{contest}/provisional', [BackofficeListAutomationController::class, 'generateProvisional'])
                        ->name('automation.provisional');
                    Route::post('automation/{contest}/definitive', [BackofficeListAutomationController::class, 'generateDefinitive'])
                        ->name('automation.definitive');
                    Route::get('automation-runs/{listAutomationRun}', [BackofficeListAutomationController::class, 'show'])
                        ->name('automation-runs.show');
                    Route::post('automation-runs/{listAutomationRun}/approve', [BackofficeListAutomationController::class, 'approve'])
                        ->name('automation-runs.approve');

                    Route::get('provisional', [BackofficeProvisionalListController::class, 'index'])->name('provisional.index');
                    Route::get('provisional/create', [BackofficeProvisionalListController::class, 'create'])->name('provisional.create');
                    Route::post('provisional', [BackofficeProvisionalListController::class, 'store'])->name('provisional.store');
                    Route::get('provisional/{provisionalList}', [BackofficeProvisionalListController::class, 'show'])->name('provisional.show');
                    Route::post('provisional/{provisionalList}/review', [BackofficeProvisionalListController::class, 'review'])->name('provisional.review');
                    Route::post('provisional/{provisionalList}/approve', [BackofficeProvisionalListController::class, 'approve'])->name('provisional.approve');
                    Route::post('provisional/{provisionalList}/publish', [BackofficeProvisionalListController::class, 'publish'])->name('provisional.publish');
                    Route::post('provisional/{provisionalList}/open-complaint-period', [BackofficeProvisionalListController::class, 'openComplaintPeriod'])->name('provisional.open-complaint-period');
                    Route::post('provisional/{provisionalList}/close-complaint-period', [BackofficeProvisionalListController::class, 'closeComplaintPeriod'])->name('provisional.close-complaint-period');
                    Route::post('provisional/{provisionalList}/cancel', [BackofficeProvisionalListController::class, 'cancel'])->name('provisional.cancel');
                    Route::post('provisional/{provisionalList}/archive', [BackofficeProvisionalListController::class, 'archive'])->name('provisional.archive');

                    Route::get('definitive', [BackofficeDefinitiveListController::class, 'index'])->name('definitive.index');
                    Route::get('definitive/create', [BackofficeDefinitiveListController::class, 'create'])->name('definitive.create');
                    Route::post('definitive', [BackofficeDefinitiveListController::class, 'store'])->name('definitive.store');
                    Route::get('definitive/{definitiveList}', [BackofficeDefinitiveListController::class, 'show'])->name('definitive.show');
                    Route::post('definitive/{definitiveList}/review', [BackofficeDefinitiveListController::class, 'review'])->name('definitive.review');
                    Route::post('definitive/{definitiveList}/approve', [BackofficeDefinitiveListController::class, 'approve'])->name('definitive.approve');
                    Route::post('definitive/{definitiveList}/publish', [BackofficeDefinitiveListController::class, 'publish'])->name('definitive.publish');
                    Route::post('definitive/{definitiveList}/lock', [BackofficeDefinitiveListController::class, 'lock'])->name('definitive.lock');
                    Route::post('definitive/{definitiveList}/archive', [BackofficeDefinitiveListController::class, 'archive'])->name('definitive.archive');
                });

                Route::get('complaints', [BackofficeComplaintController::class, 'index'])->name('complaints.index');
                Route::get('complaints/{complaint}', [BackofficeComplaintController::class, 'show'])->name('complaints.show');
                Route::post('complaints/{complaint}/assign', [BackofficeComplaintController::class, 'assign'])->name('complaints.assign');
                Route::post('complaints/{complaint}/mark-received', [BackofficeComplaintController::class, 'markReceived'])->name('complaints.mark-received');
                Route::post('complaints/{complaint}/start-review', [BackofficeComplaintController::class, 'startReview'])->name('complaints.start-review');
                Route::post('complaints/{complaint}/reviews', [BackofficeComplaintReviewController::class, 'store'])->name('complaints.reviews.store');
                Route::post('complaints/{complaint}/close', [BackofficeComplaintController::class, 'close'])->name('complaints.close');
                Route::get('complaints/{complaint}/decisions/create', [BackofficeComplaintDecisionController::class, 'create'])->name('complaint-decisions.create');
                Route::post('complaints/{complaint}/decisions', [BackofficeComplaintDecisionController::class, 'store'])->name('complaint-decisions.store');
                Route::get('complaint-decisions/{complaintDecision}', [BackofficeComplaintDecisionController::class, 'show'])->name('complaint-decisions.show');
                Route::post('complaint-decisions/{complaintDecision}/approve', [BackofficeComplaintDecisionController::class, 'approve'])->name('complaint-decisions.approve');
                Route::post('complaint-decisions/{complaintDecision}/cancel', [BackofficeComplaintDecisionController::class, 'cancel'])->name('complaint-decisions.cancel');
                Route::get('complaints/{complaint}/additional-information/create', [BackofficeAdditionalInformationRequestController::class, 'create'])->name('additional-information-requests.create');
                Route::post('complaints/{complaint}/additional-information', [BackofficeAdditionalInformationRequestController::class, 'store'])->name('additional-information-requests.store');
                Route::get('additional-information-requests/{additionalInformationRequest}', [BackofficeAdditionalInformationRequestController::class, 'show'])->name('additional-information-requests.show');
                Route::post('additional-information-requests/{additionalInformationRequest}/close', [BackofficeAdditionalInformationRequestController::class, 'close'])->name('additional-information-requests.close');
                Route::post('additional-information-requests/{additionalInformationRequest}/mark-overdue', [BackofficeAdditionalInformationRequestController::class, 'markOverdue'])->name('additional-information-requests.mark-overdue');

                Route::get('hearings', [BackofficeHearingController::class, 'index'])->name('hearings.index');
                Route::get('hearings/create', [BackofficeHearingController::class, 'create'])->name('hearings.create');
                Route::post('hearings', [BackofficeHearingController::class, 'store'])->name('hearings.store');
                Route::get('hearings/{hearing}', [BackofficeHearingController::class, 'show'])->name('hearings.show');
                Route::post('hearings/{hearing}/issue', [BackofficeHearingController::class, 'issue'])->name('hearings.issue');
                Route::post('hearings/{hearing}/close', [BackofficeHearingController::class, 'close'])->name('hearings.close');
                Route::post('hearings/{hearing}/cancel', [BackofficeHearingController::class, 'cancel'])->name('hearings.cancel');
                Route::get('hearing-submissions/{hearingSubmission}', [BackofficeHearingSubmissionReviewController::class, 'show'])->name('hearing-submissions.show');
                Route::post('hearing-submissions/{hearingSubmission}/accept', [BackofficeHearingSubmissionReviewController::class, 'accept'])->name('hearing-submissions.accept');
                Route::post('hearing-submissions/{hearingSubmission}/reject', [BackofficeHearingSubmissionReviewController::class, 'reject'])->name('hearing-submissions.reject');

                Route::get('official-notifications', [BackofficeOfficialNotificationController::class, 'index'])->name('official-notifications.index');
                Route::get('official-notifications/create', [BackofficeOfficialNotificationController::class, 'create'])->name('official-notifications.create');
                Route::post('official-notifications', [BackofficeOfficialNotificationController::class, 'store'])->name('official-notifications.store');
                Route::get('official-notifications/{officialNotification}', [BackofficeOfficialNotificationController::class, 'show'])->name('official-notifications.show');
                Route::post('official-notifications/{officialNotification}/mark-sent', [BackofficeOfficialNotificationController::class, 'markSent'])->name('official-notifications.mark-sent');
                Route::post('official-notifications/{officialNotification}/mark-failed', [BackofficeOfficialNotificationController::class, 'markFailed'])->name('official-notifications.mark-failed');

                Route::get('communications', [BackofficeNotificationCenterController::class, 'index'])
                    ->name('communications.index');
                Route::get('communications/dashboard', [BackofficeNotificationCenterController::class, 'index'])
                    ->name('communications.dashboard');

                Route::prefix('communications')->name('communications.')->group(function () {
                    Route::get('templates', [BackofficeNotificationTemplateController::class, 'index'])->name('templates.index');
                    Route::get('templates/create', [BackofficeNotificationTemplateController::class, 'create'])->name('templates.create');
                    Route::post('templates', [BackofficeNotificationTemplateController::class, 'store'])->name('templates.store');
                    Route::get('templates/{notificationTemplate}', [BackofficeNotificationTemplateController::class, 'show'])->name('templates.show');
                    Route::get('templates/{notificationTemplate}/edit', [BackofficeNotificationTemplateController::class, 'edit'])->name('templates.edit');
                    Route::match(['put', 'patch'], 'templates/{notificationTemplate}', [BackofficeNotificationTemplateController::class, 'update'])->name('templates.update');
                    Route::post('templates/{notificationTemplate}/archive', [BackofficeNotificationTemplateController::class, 'archive'])->name('templates.archive');
                    Route::match(['get', 'post'], 'templates/{notificationTemplate}/preview', [BackofficeNotificationTemplateController::class, 'preview'])->name('templates.preview');

                    Route::post('templates/{notificationTemplate}/versions', [BackofficeNotificationTemplateVersionController::class, 'store'])->name('template-versions.store');
                    Route::get('template-versions/{notificationTemplateVersion}', [BackofficeNotificationTemplateVersionController::class, 'show'])->name('template-versions.show');
                    Route::post('template-versions/{notificationTemplateVersion}/approve', [BackofficeNotificationTemplateVersionController::class, 'approve'])->name('template-versions.approve');
                    Route::post('template-versions/{notificationTemplateVersion}/activate', [BackofficeNotificationTemplateVersionController::class, 'activate'])->name('template-versions.activate');
                    Route::post('template-versions/{notificationTemplateVersion}/archive', [BackofficeNotificationTemplateVersionController::class, 'archive'])->name('template-versions.archive');

                    Route::get('variables', [BackofficeTemplateVariableController::class, 'index'])->name('variables.index');
                    Route::post('variables', [BackofficeTemplateVariableController::class, 'store'])->name('variables.store');
                    Route::match(['put', 'patch'], 'variables/{templateVariable}', [BackofficeTemplateVariableController::class, 'update'])->name('variables.update');

                    Route::get('event-rules', [BackofficeNotificationEventRuleController::class, 'index'])->name('event-rules.index');
                    Route::get('event-rules/create', [BackofficeNotificationEventRuleController::class, 'create'])->name('event-rules.create');
                    Route::post('event-rules', [BackofficeNotificationEventRuleController::class, 'store'])->name('event-rules.store');
                    Route::get('event-rules/{notificationEventRule}/edit', [BackofficeNotificationEventRuleController::class, 'edit'])->name('event-rules.edit');
                    Route::match(['put', 'patch'], 'event-rules/{notificationEventRule}', [BackofficeNotificationEventRuleController::class, 'update'])->name('event-rules.update');
                    Route::post('event-rules/{notificationEventRule}/activate', [BackofficeNotificationEventRuleController::class, 'activate'])->name('event-rules.activate');
                    Route::post('event-rules/{notificationEventRule}/deactivate', [BackofficeNotificationEventRuleController::class, 'deactivate'])->name('event-rules.deactivate');

                    Route::get('logs', [BackofficeCommunicationLogController::class, 'index'])->name('logs.index');
                    Route::post('logs', [BackofficeCommunicationLogController::class, 'store'])->name('logs.store');
                    Route::get('logs/{communicationLog}', [BackofficeCommunicationLogController::class, 'show'])->name('logs.show');
                    Route::post('logs/{communicationLog}/cancel', [BackofficeCommunicationLogController::class, 'cancel'])->name('logs.cancel');
                    Route::post('logs/{communicationLog}/archive', [BackofficeCommunicationLogController::class, 'archive'])->name('logs.archive');
                    Route::post('deliveries/{communicationDelivery}/resend', [BackofficeCommunicationDeliveryController::class, 'resend'])->name('deliveries.resend');
                    Route::post('deliveries/{communicationDelivery}/postal', [BackofficeCommunicationDeliveryController::class, 'registerPostal'])->name('deliveries.postal');
                    Route::get('receipts/{communicationReceipt}/download', [BackofficeCommunicationReceiptController::class, 'download'])->name('receipts.download');
                    Route::get('preferences', [BackofficeNotificationPreferenceController::class, 'index'])->name('preferences.index');
                });

                Route::get('internal-alerts', [BackofficeInternalAlertController::class, 'index'])
                    ->name('internal-alerts.index');
                Route::post('internal-alerts/detect', [BackofficeInternalAlertController::class, 'detect'])
                    ->name('internal-alerts.detect');
                Route::get('internal-alerts/{internalAlert}', [BackofficeInternalAlertController::class, 'show'])
                    ->name('internal-alerts.show');
                Route::post('internal-alerts/{internalAlert}/resolve', [BackofficeInternalAlertController::class, 'resolve'])
                    ->name('internal-alerts.resolve');
                Route::post('internal-alerts/{internalAlert}/dismiss', [BackofficeInternalAlertController::class, 'dismiss'])
                    ->name('internal-alerts.dismiss');

                Route::resource('procedure-templates', BackofficeProcedureTemplateController::class)
                    ->parameters(['procedure-templates' => 'procedureTemplate'])
                    ->except(['destroy']);
                Route::post('procedure-templates/{procedureTemplate}/publish', [BackofficeProcedureTemplateController::class, 'publish'])
                    ->name('procedure-templates.publish');
                Route::match(['get', 'post'], 'procedure-templates/{procedureTemplate}/preview', [BackofficeProcedureTemplateController::class, 'render'])
                    ->name('procedure-templates.preview');
                Route::post('procedure-templates/{procedureTemplate}/documents', [BackofficeProcedureTemplateController::class, 'generateDocument'])
                    ->name('procedure-templates.documents.generate');

                Route::get('generated-procedure-documents', [BackofficeGeneratedProcedureDocumentController::class, 'index'])
                    ->name('generated-documents.index');
                Route::get('generated-procedure-documents/{generatedProcedureDocument}', [BackofficeGeneratedProcedureDocumentController::class, 'show'])
                    ->name('generated-documents.show');
                Route::get('generated-procedure-documents/{generatedProcedureDocument}/download', [BackofficeGeneratedProcedureDocumentController::class, 'download'])
                    ->name('generated-documents.download');
                Route::post('generated-procedure-documents/{generatedProcedureDocument}/issue', [BackofficeGeneratedProcedureDocumentController::class, 'issue'])
                    ->name('generated-documents.issue');

                Route::get('process-confirmations', [BackofficeProcessConfirmationController::class, 'index'])
                    ->name('process-confirmations.index');
                Route::get('process-confirmations/{processConfirmation}', [BackofficeProcessConfirmationController::class, 'show'])
                    ->name('process-confirmations.show');
                Route::post('process-confirmations/{processConfirmation}/send', [BackofficeProcessConfirmationController::class, 'send'])
                    ->name('process-confirmations.send');

                Route::get('procedure-minutes', [BackofficeProcedureMinuteController::class, 'index'])
                    ->name('procedure-minutes.index');
                Route::post('procedure-minutes', [BackofficeProcedureMinuteController::class, 'generate'])
                    ->name('procedure-minutes.generate');
                Route::get('procedure-minutes/{procedureMinute}', [BackofficeProcedureMinuteController::class, 'show'])
                    ->name('procedure-minutes.show');
                Route::get('procedure-minutes/{procedureMinute}/download', [BackofficeProcedureMinuteController::class, 'download'])
                    ->name('procedure-minutes.download');
                Route::post('procedure-minutes/{procedureMinute}/approve', [BackofficeProcedureMinuteController::class, 'approve'])
                    ->name('procedure-minutes.approve');

                Route::get('document-templates', [BackofficeDocumentTemplateController::class, 'index'])->name('document-templates.index');
                Route::get('document-templates/create', [BackofficeDocumentTemplateController::class, 'create'])->name('document-templates.create');
                Route::post('document-templates', [BackofficeDocumentTemplateController::class, 'store'])->name('document-templates.store');
                Route::get('document-templates/{documentTemplate}', [BackofficeDocumentTemplateController::class, 'show'])->name('document-templates.show');
                Route::get('document-templates/{documentTemplate}/edit', [BackofficeDocumentTemplateController::class, 'edit'])->name('document-templates.edit');
                Route::match(['put', 'patch'], 'document-templates/{documentTemplate}', [BackofficeDocumentTemplateController::class, 'update'])->name('document-templates.update');
                Route::post('document-templates/{documentTemplate}/archive', [BackofficeDocumentTemplateController::class, 'archive'])->name('document-templates.archive');
                Route::match(['get', 'post'], 'document-templates/{documentTemplate}/preview', [BackofficeDocumentTemplateController::class, 'preview'])->name('document-templates.preview');
                Route::post('document-templates/{documentTemplate}/versions', [BackofficeDocumentTemplateVersionController::class, 'store'])->name('document-template-versions.store');
                Route::get('document-template-versions/{documentTemplateVersion}', [BackofficeDocumentTemplateVersionController::class, 'show'])->name('document-template-versions.show');
                Route::post('document-template-versions/{documentTemplateVersion}/approve', [BackofficeDocumentTemplateVersionController::class, 'approve'])->name('document-template-versions.approve');
                Route::post('document-template-versions/{documentTemplateVersion}/activate', [BackofficeDocumentTemplateVersionController::class, 'activate'])->name('document-template-versions.activate');

                Route::get('official-documents', [BackofficeGeneratedOfficialDocumentController::class, 'index'])->name('official-documents.index');
                Route::post('official-documents/generate', [BackofficeGeneratedOfficialDocumentController::class, 'generate'])->name('official-documents.generate');
                Route::get('official-documents/{generatedOfficialDocument}', [BackofficeGeneratedOfficialDocumentController::class, 'show'])->name('official-documents.show');
                Route::get('official-documents/{generatedOfficialDocument}/download', [BackofficeGeneratedOfficialDocumentController::class, 'download'])->name('official-documents.download');
                Route::post('official-documents/{generatedOfficialDocument}/issue', [BackofficeGeneratedOfficialDocumentController::class, 'issue'])->name('official-documents.issue');
                Route::post('official-documents/{generatedOfficialDocument}/cancel', [BackofficeGeneratedOfficialDocumentController::class, 'cancel'])->name('official-documents.cancel');

                Route::prefix('allocation')->name('allocation.')->group(function () {
                    Route::get('contest-housing-units', [BackofficeContestHousingUnitController::class, 'index'])->name('contest-housing-units.index');
                    Route::get('contest-housing-units/create', [BackofficeContestHousingUnitController::class, 'create'])->name('contest-housing-units.create');
                    Route::post('contest-housing-units', [BackofficeContestHousingUnitController::class, 'store'])->name('contest-housing-units.store');
                    Route::get('contest-housing-units/{contestHousingUnit}', [BackofficeContestHousingUnitController::class, 'show'])->name('contest-housing-units.show');
                    Route::get('contest-housing-units/{contestHousingUnit}/edit', [BackofficeContestHousingUnitController::class, 'edit'])->name('contest-housing-units.edit');
                    Route::match(['put', 'patch'], 'contest-housing-units/{contestHousingUnit}', [BackofficeContestHousingUnitController::class, 'update'])->name('contest-housing-units.update');
                    Route::delete('contest-housing-units/{contestHousingUnit}', [BackofficeContestHousingUnitController::class, 'destroy'])->name('contest-housing-units.destroy');
                    Route::post('contest-housing-units/{contestHousingUnit}/mark-available', [BackofficeContestHousingUnitController::class, 'markAvailable'])->name('contest-housing-units.mark-available');
                    Route::post('contest-housing-units/{contestHousingUnit}/mark-unavailable', [BackofficeContestHousingUnitController::class, 'markUnavailable'])->name('contest-housing-units.mark-unavailable');

                    Route::get('typology-rules', [BackofficeTypologyAdequacyRuleController::class, 'index'])->name('typology-rules.index');
                    Route::get('typology-rules/create', [BackofficeTypologyAdequacyRuleController::class, 'create'])->name('typology-rules.create');
                    Route::post('typology-rules', [BackofficeTypologyAdequacyRuleController::class, 'store'])->name('typology-rules.store');
                    Route::get('typology-rules/{typologyAdequacyRule}/edit', [BackofficeTypologyAdequacyRuleController::class, 'edit'])->name('typology-rules.edit');
                    Route::match(['put', 'patch'], 'typology-rules/{typologyAdequacyRule}', [BackofficeTypologyAdequacyRuleController::class, 'update'])->name('typology-rules.update');
                    Route::post('typology-rules/{typologyAdequacyRule}/activate', [BackofficeTypologyAdequacyRuleController::class, 'activate'])->name('typology-rules.activate');
                    Route::post('typology-rules/{typologyAdequacyRule}/deactivate', [BackofficeTypologyAdequacyRuleController::class, 'deactivate'])->name('typology-rules.deactivate');

                    Route::get('rule-sets', [BackofficeAllocationRuleSetController::class, 'index'])->name('rule-sets.index');
                    Route::get('rule-sets/create', [BackofficeAllocationRuleSetController::class, 'create'])->name('rule-sets.create');
                    Route::post('rule-sets', [BackofficeAllocationRuleSetController::class, 'store'])->name('rule-sets.store');
                    Route::get('rule-sets/{allocationRuleSet}', [BackofficeAllocationRuleSetController::class, 'show'])->name('rule-sets.show');
                    Route::get('rule-sets/{allocationRuleSet}/edit', [BackofficeAllocationRuleSetController::class, 'edit'])->name('rule-sets.edit');
                    Route::match(['put', 'patch'], 'rule-sets/{allocationRuleSet}', [BackofficeAllocationRuleSetController::class, 'update'])->name('rule-sets.update');
                    Route::post('rule-sets/{allocationRuleSet}/activate', [BackofficeAllocationRuleSetController::class, 'activate'])->name('rule-sets.activate');
                    Route::post('rule-sets/{allocationRuleSet}/archive', [BackofficeAllocationRuleSetController::class, 'archive'])->name('rule-sets.archive');
                    Route::post('rule-sets/{allocationRuleSet}/duplicate', [BackofficeAllocationRuleSetController::class, 'duplicate'])->name('rule-sets.duplicate');

                    Route::get('runs', [BackofficeAllocationRunController::class, 'index'])->name('runs.index');
                    Route::get('runs/create', [BackofficeAllocationRunController::class, 'create'])->name('runs.create');
                    Route::post('runs', [BackofficeAllocationRunController::class, 'store'])->name('runs.store');
                    Route::get('runs/{allocationRun}', [BackofficeAllocationRunController::class, 'show'])->name('runs.show');
                    Route::post('runs/{allocationRun}/run', [BackofficeAllocationRunController::class, 'run'])->name('runs.run');
                    Route::post('runs/{allocationRun}/lock', [BackofficeAllocationRunController::class, 'lock'])->name('runs.lock');
                    Route::post('runs/{allocationRun}/cancel', [BackofficeAllocationRunController::class, 'cancel'])->name('runs.cancel');

                    Route::get('allocations', [BackofficeAllocationController::class, 'index'])->name('allocations.index');
                    Route::get('allocations/manual/create', [BackofficeAllocationController::class, 'createManual'])->name('allocations.manual-create');
                    Route::post('allocations/manual', [BackofficeAllocationController::class, 'storeManual'])->name('allocations.manual-store');
                    Route::get('allocations/{allocation}', [BackofficeAllocationController::class, 'show'])->name('allocations.show');

                    Route::get('offers', [BackofficeAllocationOfferController::class, 'index'])->name('offers.index');
                    Route::get('offers/{allocationOffer}', [BackofficeAllocationOfferController::class, 'show'])->name('offers.show');
                    Route::post('offers/{allocationOffer}/issue', [BackofficeAllocationOfferController::class, 'issue'])->name('offers.issue');
                    Route::post('offers/{allocationOffer}/mark-expired', [BackofficeAllocationOfferController::class, 'markExpired'])->name('offers.mark-expired');

                    Route::get('lotteries', [BackofficeLotteryRunController::class, 'index'])->name('lotteries.index');
                    Route::get('lotteries/create', [BackofficeLotteryRunController::class, 'create'])->name('lotteries.create');
                    Route::post('lotteries', [BackofficeLotteryRunController::class, 'store'])->name('lotteries.store');
                    Route::get('lotteries/{lotteryRun}', [BackofficeLotteryRunController::class, 'show'])->name('lotteries.show');
                    Route::post('lotteries/{lotteryRun}/run', [BackofficeLotteryRunController::class, 'run'])->name('lotteries.run');
                    Route::post('lotteries/{lotteryRun}/lock', [BackofficeLotteryRunController::class, 'lock'])->name('lotteries.lock');
                    Route::get('lotteries/{lotteryRun}/audit', [BackofficeLotteryRunController::class, 'audit'])->name('lotteries.audit');

                    Route::get('reserve-lists', [BackofficeReserveListController::class, 'index'])->name('reserve-lists.index');
                    Route::get('reserve-lists/{reserveList}', [BackofficeReserveListController::class, 'show'])->name('reserve-lists.show');
                    Route::post('reserve-lists/{reserveList}/call-next', [BackofficeReserveListController::class, 'callNext'])->name('reserve-lists.call-next');

                    Route::get('reports', [BackofficeAllocationReportController::class, 'index'])->name('reports.index');
                    Route::post('reports', [BackofficeAllocationReportController::class, 'store'])->name('reports.store');
                    Route::get('reports/{allocationReport}', [BackofficeAllocationReportController::class, 'show'])->name('reports.show');
                    Route::post('reports/{allocationReport}/approve', [BackofficeAllocationReportController::class, 'approve'])->name('reports.approve');
                    Route::get('reports/{allocationReport}/download', [BackofficeAllocationReportController::class, 'download'])->name('reports.download');
                });

                Route::prefix('contracts')->name('contracts.')->group(function () {
                    Route::get('rent-rule-sets', [BackofficeRentRuleSetController::class, 'index'])->name('rent-rule-sets.index');
                    Route::get('rent-rule-sets/create', [BackofficeRentRuleSetController::class, 'create'])->name('rent-rule-sets.create');
                    Route::post('rent-rule-sets', [BackofficeRentRuleSetController::class, 'store'])->name('rent-rule-sets.store');
                    Route::get('rent-rule-sets/{rentRuleSet}', [BackofficeRentRuleSetController::class, 'show'])->name('rent-rule-sets.show');
                    Route::get('rent-rule-sets/{rentRuleSet}/edit', [BackofficeRentRuleSetController::class, 'edit'])->name('rent-rule-sets.edit');
                    Route::match(['put', 'patch'], 'rent-rule-sets/{rentRuleSet}', [BackofficeRentRuleSetController::class, 'update'])->name('rent-rule-sets.update');
                    Route::post('rent-rule-sets/{rentRuleSet}/activate', [BackofficeRentRuleSetController::class, 'activate'])->name('rent-rule-sets.activate');
                    Route::post('rent-rule-sets/{rentRuleSet}/archive', [BackofficeRentRuleSetController::class, 'archive'])->name('rent-rule-sets.archive');
                    Route::post('rent-rule-sets/{rentRuleSet}/duplicate', [BackofficeRentRuleSetController::class, 'duplicate'])->name('rent-rule-sets.duplicate');

                    Route::get('rent-rules', [BackofficeRentRuleController::class, 'index'])->name('rent-rules.index');
                    Route::get('rent-rules/create', [BackofficeRentRuleController::class, 'create'])->name('rent-rules.create');
                    Route::post('rent-rules', [BackofficeRentRuleController::class, 'store'])->name('rent-rules.store');
                    Route::get('rent-rules/{rentRule}/edit', [BackofficeRentRuleController::class, 'edit'])->name('rent-rules.edit');
                    Route::match(['put', 'patch'], 'rent-rules/{rentRule}', [BackofficeRentRuleController::class, 'update'])->name('rent-rules.update');

                    Route::get('rent-calculations', [BackofficeRentCalculationController::class, 'index'])->name('rent-calculations.index');
                    Route::get('rent-calculations/{rentCalculation}', [BackofficeRentCalculationController::class, 'show'])->name('rent-calculations.show');
                    Route::post('rent-calculations/calculate', [BackofficeRentCalculationController::class, 'calculate'])->name('rent-calculations.calculate');
                    Route::post('rent-calculations/{rentCalculation}/approve', [BackofficeRentCalculationController::class, 'approve'])->name('rent-calculations.approve');
                    Route::post('rent-calculations/{rentCalculation}/reject', [BackofficeRentCalculationController::class, 'reject'])->name('rent-calculations.reject');
                    Route::post('rent-calculations/{rentCalculation}/recalculate', [BackofficeRentCalculationController::class, 'recalculate'])->name('rent-calculations.recalculate');
                    Route::post('rent-calculations/{rentCalculation}/manual-reviews', [BackofficeRentManualReviewController::class, 'store'])->name('rent-manual-reviews.store');
                    Route::post('rent-manual-reviews/{rentManualReview}/approve', [BackofficeRentManualReviewController::class, 'approve'])->name('rent-manual-reviews.approve');
                    Route::post('rent-manual-reviews/{rentManualReview}/reject', [BackofficeRentManualReviewController::class, 'reject'])->name('rent-manual-reviews.reject');

                    Route::get('templates', [BackofficeContractTemplateController::class, 'index'])->name('templates.index');
                    Route::get('templates/create', [BackofficeContractTemplateController::class, 'create'])->name('templates.create');
                    Route::post('templates', [BackofficeContractTemplateController::class, 'store'])->name('templates.store');
                    Route::get('templates/{contractTemplate}', [BackofficeContractTemplateController::class, 'show'])->name('templates.show');
                    Route::get('templates/{contractTemplate}/edit', [BackofficeContractTemplateController::class, 'edit'])->name('templates.edit');
                    Route::match(['put', 'patch'], 'templates/{contractTemplate}', [BackofficeContractTemplateController::class, 'update'])->name('templates.update');
                    Route::post('templates/{contractTemplate}/activate', [BackofficeContractTemplateController::class, 'activate'])->name('templates.activate');
                    Route::post('templates/{contractTemplate}/archive', [BackofficeContractTemplateController::class, 'archive'])->name('templates.archive');
                    Route::post('templates/{contractTemplate}/duplicate', [BackofficeContractTemplateController::class, 'duplicate'])->name('templates.duplicate');

                    Route::get('clauses', [BackofficeContractClauseController::class, 'index'])->name('clauses.index');
                    Route::get('clauses/create', [BackofficeContractClauseController::class, 'create'])->name('clauses.create');
                    Route::post('clauses', [BackofficeContractClauseController::class, 'store'])->name('clauses.store');
                    Route::get('clauses/{contractClause}', [BackofficeContractClauseController::class, 'show'])->name('clauses.show');
                    Route::get('clauses/{contractClause}/edit', [BackofficeContractClauseController::class, 'edit'])->name('clauses.edit');
                    Route::match(['put', 'patch'], 'clauses/{contractClause}', [BackofficeContractClauseController::class, 'update'])->name('clauses.update');
                    Route::post('clauses/{contractClause}/activate', [BackofficeContractClauseController::class, 'activate'])->name('clauses.activate');
                    Route::post('clauses/{contractClause}/archive', [BackofficeContractClauseController::class, 'archive'])->name('clauses.archive');

                    Route::get('leases', [BackofficeLeaseContractController::class, 'index'])->name('leases.index');
                    Route::get('leases/create', [BackofficeLeaseContractController::class, 'create'])->name('leases.create');
                    Route::post('leases', [BackofficeLeaseContractController::class, 'store'])->name('leases.store');
                    Route::get('leases/{leaseContract}', [BackofficeLeaseContractController::class, 'show'])->name('leases.show');
                    Route::get('leases/{leaseContract}/edit', [BackofficeLeaseContractController::class, 'edit'])->name('leases.edit');
                    Route::match(['put', 'patch'], 'leases/{leaseContract}', [BackofficeLeaseContractController::class, 'update'])->name('leases.update');
                    Route::post('leases/{leaseContract}/issue', [BackofficeLeaseContractController::class, 'issue'])->name('leases.issue');
                    Route::post('leases/{leaseContract}/activate', [BackofficeLeaseContractController::class, 'activate'])->name('leases.activate');
                    Route::post('leases/{leaseContract}/suspend', [BackofficeLeaseContractController::class, 'suspend'])->name('leases.suspend');
                    Route::post('leases/{leaseContract}/terminate', [BackofficeLeaseContractController::class, 'terminate'])->name('leases.terminate');
                    Route::post('leases/{leaseContract}/cancel', [BackofficeLeaseContractController::class, 'cancel'])->name('leases.cancel');

                    Route::post('leases/{leaseContract}/documents/generate', [BackofficeLeaseContractDocumentController::class, 'generate'])->name('documents.generate');
                    Route::get('documents/{leaseContractDocument}/download', [BackofficeLeaseContractDocumentController::class, 'download'])->name('documents.download');

                    Route::post('leases/{leaseContract}/validations', [BackofficeLeaseContractValidationController::class, 'store'])->name('validations.store');
                    Route::post('validations/{leaseContractValidation}/approve', [BackofficeLeaseContractValidationController::class, 'approve'])->name('validations.approve');
                    Route::post('validations/{leaseContractValidation}/reject', [BackofficeLeaseContractValidationController::class, 'reject'])->name('validations.reject');
                    Route::post('leases/{leaseContract}/signatures', [BackofficeLeaseContractSignatureController::class, 'store'])->name('signatures.store');

                    Route::get('deposits/{contractDeposit}', [BackofficeContractDepositController::class, 'show'])->name('deposits.show');
                    Route::post('deposits/{contractDeposit}/requested', [BackofficeContractDepositController::class, 'markRequested'])->name('deposits.requested');
                    Route::post('deposits/{contractDeposit}/paid', [BackofficeContractDepositController::class, 'markPaid'])->name('deposits.paid');
                    Route::post('deposits/{contractDeposit}/waived', [BackofficeContractDepositController::class, 'markWaived'])->name('deposits.waived');
                    Route::post('deposits/{contractDeposit}/cancel', [BackofficeContractDepositController::class, 'cancel'])->name('deposits.cancel');
                });

                Route::prefix('finance')->name('finance.')->group(function () {
                    Route::get('accounts', [BackofficeFinanceTenantFinancialAccountController::class, 'index'])->name('accounts.index');
                    Route::post('accounts', [BackofficeFinanceTenantFinancialAccountController::class, 'store'])->name('accounts.store');
                    Route::get('accounts/{tenantFinancialAccount}', [BackofficeFinanceTenantFinancialAccountController::class, 'show'])->name('accounts.show');
                    Route::get('accounts/{tenantFinancialAccount}/statement', [BackofficeFinanceAccountStatementController::class, 'show'])->name('accounts.statement');
                    Route::post('accounts/{tenantFinancialAccount}/detect-arrears', [BackofficeFinanceArrearController::class, 'detect'])->name('accounts.detect-arrears');

                    Route::get('schedules', [BackofficeFinanceRentScheduleController::class, 'index'])->name('schedules.index');
                    Route::get('schedules/{rentSchedule}', [BackofficeFinanceRentScheduleController::class, 'show'])->name('schedules.show');
                    Route::post('contracts/{leaseContract}/schedules/generate', [BackofficeFinanceRentScheduleController::class, 'generate'])->name('schedules.generate');

                    Route::get('installments', [BackofficeFinanceRentInstallmentController::class, 'index'])->name('installments.index');
                    Route::get('installments/{rentInstallment}', [BackofficeFinanceRentInstallmentController::class, 'show'])->name('installments.show');
                    Route::post('installments/{rentInstallment}/issue', [BackofficeFinanceRentInstallmentController::class, 'issue'])->name('installments.issue');
                    Route::post('installments/{rentInstallment}/waive', [BackofficeFinanceRentInstallmentController::class, 'waive'])->name('installments.waive');

                    Route::get('payments', [BackofficeFinanceLeasePaymentController::class, 'index'])->name('payments.index');
                    Route::get('payments/create', [BackofficeFinanceLeasePaymentController::class, 'create'])->name('payments.create');
                    Route::post('payments', [BackofficeFinanceLeasePaymentController::class, 'store'])->name('payments.store');
                    Route::get('payments/{leasePayment}', [BackofficeFinanceLeasePaymentController::class, 'show'])->name('payments.show');
                    Route::post('payments/{leasePayment}/confirm', [BackofficeFinanceLeasePaymentController::class, 'confirm'])->name('payments.confirm');
                    Route::post('payments/{leasePayment}/reverse', [BackofficeFinanceLeasePaymentController::class, 'reverse'])->name('payments.reverse');
                    Route::post('payments/{leasePayment}/allocate', [BackofficeFinanceLeasePaymentController::class, 'allocate'])->name('payments.allocate');

                    Route::get('imports', [BackofficeFinancePaymentImportController::class, 'index'])->name('imports.index');
                    Route::get('imports/create', [BackofficeFinancePaymentImportController::class, 'create'])->name('imports.create');
                    Route::post('imports', [BackofficeFinancePaymentImportController::class, 'store'])->name('imports.store');
                    Route::get('imports/{paymentImportBatch}', [BackofficeFinancePaymentImportController::class, 'show'])->name('imports.show');
                    Route::post('imports/{paymentImportBatch}/process', [BackofficeFinancePaymentImportController::class, 'process'])->name('imports.process');

                    Route::get('receipts', [BackofficeFinancePaymentReceiptController::class, 'index'])->name('receipts.index');
                    Route::get('receipts/{paymentReceipt}', [BackofficeFinancePaymentReceiptController::class, 'show'])->name('receipts.show');
                    Route::post('payments/{leasePayment}/receipts/generate', [BackofficeFinancePaymentReceiptController::class, 'generate'])->name('receipts.generate');
                    Route::get('receipts/{paymentReceipt}/download', [BackofficeFinancePaymentReceiptController::class, 'download'])->name('receipts.download');
                    Route::post('receipts/{paymentReceipt}/cancel', [BackofficeFinancePaymentReceiptController::class, 'cancel'])->name('receipts.cancel');

                    Route::get('arrears', [BackofficeFinanceArrearController::class, 'index'])->name('arrears.index');
                    Route::get('arrears/{arrear}', [BackofficeFinanceArrearController::class, 'show'])->name('arrears.show');
                    Route::post('arrears/{arrear}/close', [BackofficeFinanceArrearController::class, 'close'])->name('arrears.close');

                    Route::get('default-notices', [BackofficeFinanceDefaultNoticeController::class, 'index'])->name('default-notices.index');
                    Route::get('default-notices/create', [BackofficeFinanceDefaultNoticeController::class, 'create'])->name('default-notices.create');
                    Route::post('default-notices', [BackofficeFinanceDefaultNoticeController::class, 'store'])->name('default-notices.store');
                    Route::get('default-notices/{defaultNotice}', [BackofficeFinanceDefaultNoticeController::class, 'show'])->name('default-notices.show');
                    Route::post('default-notices/{defaultNotice}/issue', [BackofficeFinanceDefaultNoticeController::class, 'issue'])->name('default-notices.issue');
                    Route::post('default-notices/{defaultNotice}/cancel', [BackofficeFinanceDefaultNoticeController::class, 'cancel'])->name('default-notices.cancel');

                    Route::get('regularization-agreements', [BackofficeFinanceRegularizationAgreementController::class, 'index'])->name('regularization-agreements.index');
                    Route::get('regularization-agreements/create', [BackofficeFinanceRegularizationAgreementController::class, 'create'])->name('regularization-agreements.create');
                    Route::post('regularization-agreements', [BackofficeFinanceRegularizationAgreementController::class, 'store'])->name('regularization-agreements.store');
                    Route::get('regularization-agreements/{regularizationAgreement}', [BackofficeFinanceRegularizationAgreementController::class, 'show'])->name('regularization-agreements.show');
                    Route::post('regularization-agreements/{regularizationAgreement}/approve', [BackofficeFinanceRegularizationAgreementController::class, 'approve'])->name('regularization-agreements.approve');
                    Route::post('regularization-agreements/{regularizationAgreement}/cancel', [BackofficeFinanceRegularizationAgreementController::class, 'cancel'])->name('regularization-agreements.cancel');

                    Route::get('rent-reviews', [BackofficeFinanceRentReviewController::class, 'index'])->name('rent-reviews.index');
                    Route::get('rent-reviews/create', [BackofficeFinanceRentReviewController::class, 'create'])->name('rent-reviews.create');
                    Route::post('rent-reviews', [BackofficeFinanceRentReviewController::class, 'store'])->name('rent-reviews.store');
                    Route::get('rent-reviews/{rentReview}', [BackofficeFinanceRentReviewController::class, 'show'])->name('rent-reviews.show');
                    Route::post('rent-reviews/{rentReview}/calculate', [BackofficeFinanceRentReviewController::class, 'calculate'])->name('rent-reviews.calculate');
                    Route::post('rent-reviews/{rentReview}/approve', [BackofficeFinanceRentReviewController::class, 'approve'])->name('rent-reviews.approve');
                    Route::post('rent-reviews/{rentReview}/reject', [BackofficeFinanceRentReviewController::class, 'reject'])->name('rent-reviews.reject');
                    Route::post('rent-reviews/{rentReview}/apply', [BackofficeFinanceRentReviewController::class, 'apply'])->name('rent-reviews.apply');

                    Route::get('income-changes', [BackofficeFinanceIncomeChangeDeclarationController::class, 'index'])->name('income-changes.index');
                    Route::get('income-changes/{incomeChangeDeclaration}', [BackofficeFinanceIncomeChangeDeclarationController::class, 'show'])->name('income-changes.show');
                    Route::post('income-changes/{incomeChangeDeclaration}/accept', [BackofficeFinanceIncomeChangeDeclarationController::class, 'accept'])->name('income-changes.accept');
                    Route::post('income-changes/{incomeChangeDeclaration}/reject', [BackofficeFinanceIncomeChangeDeclarationController::class, 'reject'])->name('income-changes.reject');

                    Route::get('annual-document-updates', [BackofficeFinanceAnnualDocumentUpdateRequestController::class, 'index'])->name('annual-document-updates.index');
                    Route::post('annual-document-updates', [BackofficeFinanceAnnualDocumentUpdateRequestController::class, 'store'])->name('annual-document-updates.store');
                    Route::get('annual-document-updates/{annualDocumentUpdateRequest}', [BackofficeFinanceAnnualDocumentUpdateRequestController::class, 'show'])->name('annual-document-updates.show');
                    Route::post('annual-document-updates/{annualDocumentUpdateRequest}/accept', [BackofficeFinanceAnnualDocumentUpdateRequestController::class, 'accept'])->name('annual-document-updates.accept');
                    Route::post('annual-document-updates/{annualDocumentUpdateRequest}/reject', [BackofficeFinanceAnnualDocumentUpdateRequestController::class, 'reject'])->name('annual-document-updates.reject');
                });

                Route::prefix('tenant-operations')->name('tenant-operations.')->group(function () {
                    Route::get('dashboard', BackofficeLandlordDashboardController::class)->name('dashboard');
                    Route::get('invoices', [BackofficeTenantInvoiceController::class, 'index'])->name('invoices.index');
                    Route::post('invoices', [BackofficeTenantInvoiceController::class, 'store'])->name('invoices.store');
                    Route::get('invoices/{tenantInvoice}', [BackofficeTenantInvoiceController::class, 'show'])->name('invoices.show');
                    Route::get('payments', [BackofficeTenantPaymentController::class, 'index'])->name('payments.index');
                    Route::post('payments', [BackofficeTenantPaymentController::class, 'store'])->name('payments.store');
                    Route::get('payments/{tenantPayment}', [BackofficeTenantPaymentController::class, 'show'])->name('payments.show');
                    Route::post('payments/{tenantPayment}/confirm', [BackofficeTenantPaymentController::class, 'confirm'])->name('payments.confirm');
                    Route::get('charge-runs', [BackofficeTenantChargeRunController::class, 'index'])->name('charge-runs.index');
                    Route::post('charge-runs', [BackofficeTenantChargeRunController::class, 'store'])->name('charge-runs.store');
                    Route::get('charge-runs/{tenantChargeRun}', [BackofficeTenantChargeRunController::class, 'show'])->name('charge-runs.show');
                    Route::get('communications', [BackofficeTenantCommunicationController::class, 'index'])->name('communications.index');
                    Route::post('communications', [BackofficeTenantCommunicationController::class, 'store'])->name('communications.store');
                    Route::get('communications/{tenantCommunication}', [BackofficeTenantCommunicationController::class, 'show'])->name('communications.show');
                    Route::post('communications/{tenantCommunication}/messages', [BackofficeTenantCommunicationController::class, 'message'])->name('communications.messages.store');
                    Route::get('maintenance-reports', [BackofficeTenantMaintenanceReportController::class, 'index'])->name('maintenance-reports.index');
                });

                Route::get('maintenance', BackofficeMaintenanceDashboardController::class)->name('maintenance.index');
                Route::prefix('maintenance')->name('maintenance.')->group(function () {
                    Route::get('dashboard', BackofficeMaintenanceDashboardController::class)->name('dashboard');
                    Route::get('costs', [BackofficeMaintenanceCostController::class, 'index'])->name('costs.index');
                    Route::get('cost-reports', [BackofficeMaintenanceCostReportController::class, 'index'])->name('cost-reports.index');

                    Route::resource('categories', BackofficeMaintenanceCategoryController::class)
                        ->parameters(['categories' => 'maintenanceCategory'])
                        ->except(['show']);
                    Route::resource('suppliers', BackofficeMaintenanceSupplierController::class)
                        ->parameters(['suppliers' => 'maintenanceSupplier'])
                        ->except(['destroy']);

                    Route::get('requests', [BackofficeMaintenanceRequestController::class, 'index'])->name('requests.index');
                    Route::get('requests/create', [BackofficeMaintenanceRequestController::class, 'create'])->name('requests.create');
                    Route::post('requests', [BackofficeMaintenanceRequestController::class, 'store'])->name('requests.store');
                    Route::get('requests/{maintenanceRequest}', [BackofficeMaintenanceRequestController::class, 'show'])->name('requests.show');
                    Route::get('requests/{maintenanceRequest}/edit', [BackofficeMaintenanceRequestController::class, 'edit'])->name('requests.edit');
                    Route::match(['put', 'patch'], 'requests/{maintenanceRequest}', [BackofficeMaintenanceRequestController::class, 'update'])->name('requests.update');
                    Route::post('requests/{maintenanceRequest}/review', [BackofficeMaintenanceRequestController::class, 'review'])->name('requests.review');
                    Route::post('requests/{maintenanceRequest}/schedule', [BackofficeMaintenanceRequestController::class, 'schedule'])->name('requests.schedule');
                    Route::post('requests/{maintenanceRequest}/start', [BackofficeMaintenanceRequestController::class, 'start'])->name('requests.start');
                    Route::post('requests/{maintenanceRequest}/resolve', [BackofficeMaintenanceRequestController::class, 'resolve'])->name('requests.resolve');
                    Route::post('requests/{maintenanceRequest}/reject', [BackofficeMaintenanceRequestController::class, 'reject'])->name('requests.reject');
                    Route::post('requests/{maintenanceRequest}/close', [BackofficeMaintenanceRequestController::class, 'close'])->name('requests.close');
                    Route::post('requests/{maintenanceRequest}/cancel', [BackofficeMaintenanceRequestController::class, 'cancel'])->name('requests.cancel');
                    Route::post('requests/{maintenanceRequest}/assignments', [BackofficeMaintenanceAssignmentController::class, 'store'])->name('assignments.store');
                    Route::post('assignments/{maintenanceAssignment}/cancel', [BackofficeMaintenanceAssignmentController::class, 'cancel'])->name('assignments.cancel');
                    Route::post('requests/{maintenanceRequest}/interventions', [BackofficeMaintenanceInterventionController::class, 'store'])->name('interventions.store');
                    Route::get('interventions/{maintenanceIntervention}', [BackofficeMaintenanceInterventionController::class, 'show'])->name('interventions.show');
                    Route::post('interventions/{maintenanceIntervention}/start', [BackofficeMaintenanceInterventionController::class, 'start'])->name('interventions.start');
                    Route::post('interventions/{maintenanceIntervention}/complete', [BackofficeMaintenanceInterventionController::class, 'complete'])->name('interventions.complete');
                    Route::post('interventions/{maintenanceIntervention}/cancel', [BackofficeMaintenanceInterventionController::class, 'cancel'])->name('interventions.cancel');
                    Route::post('requests/{maintenanceRequest}/attachments', [BackofficeMaintenanceAttachmentController::class, 'store'])->name('attachments.store');
                    Route::get('attachments/{maintenanceAttachment}/download', [BackofficeMaintenanceAttachmentController::class, 'download'])->name('attachments.download');
                    Route::post('requests/{maintenanceRequest}/costs', [BackofficeMaintenanceCostController::class, 'store'])->name('costs.store');
                    Route::post('costs/{maintenanceCost}/approve', [BackofficeMaintenanceCostController::class, 'approve'])->name('costs.approve');
                    Route::post('costs/{maintenanceCost}/reject', [BackofficeMaintenanceCostController::class, 'reject'])->name('costs.reject');
                });

                Route::prefix('inspections')->name('inspections.')->group(function () {
                    Route::resource('templates', BackofficeInspectionChecklistTemplateController::class)
                        ->parameters(['templates' => 'inspectionChecklistTemplate'])
                        ->except(['show', 'destroy']);
                    Route::get('attachments/{propertyInspectionAttachment}/download', [BackofficePropertyInspectionAttachmentController::class, 'download'])->name('attachments.download');
                    Route::get('reports/{propertyInspectionReport}', [BackofficePropertyInspectionReportController::class, 'show'])->name('reports.show');
                    Route::get('reports/{propertyInspectionReport}/download', [BackofficePropertyInspectionReportController::class, 'download'])->name('reports.download');
                    Route::post('reports/{propertyInspectionReport}/validate', [BackofficePropertyInspectionReportController::class, 'validateReport'])->name('reports.validate');
                    Route::post('reports/{propertyInspectionReport}/cancel', [BackofficePropertyInspectionReportController::class, 'cancel'])->name('reports.cancel');
                    Route::get('/', [BackofficePropertyInspectionController::class, 'index'])->name('index');
                    Route::get('create', [BackofficePropertyInspectionController::class, 'create'])->name('create');
                    Route::post('/', [BackofficePropertyInspectionController::class, 'store'])->name('store');
                    Route::get('{propertyInspection}', [BackofficePropertyInspectionController::class, 'show'])->name('show');
                    Route::get('{propertyInspection}/edit', [BackofficePropertyInspectionController::class, 'edit'])->name('edit');
                    Route::match(['put', 'patch'], '{propertyInspection}', [BackofficePropertyInspectionController::class, 'update'])->name('update');
                    Route::post('{propertyInspection}/start', [BackofficePropertyInspectionController::class, 'start'])->name('start');
                    Route::post('{propertyInspection}/complete', [BackofficePropertyInspectionController::class, 'complete'])->name('complete');
                    Route::post('{propertyInspection}/validate', [BackofficePropertyInspectionController::class, 'validateInspection'])->name('validate');
                    Route::post('{propertyInspection}/close', [BackofficePropertyInspectionController::class, 'close'])->name('close');
                    Route::post('{propertyInspection}/cancel', [BackofficePropertyInspectionController::class, 'cancel'])->name('cancel');
                    Route::post('{propertyInspection}/items', [BackofficePropertyInspectionItemController::class, 'store'])->name('items.store');
                    Route::match(['put', 'patch'], 'items/{propertyInspectionItem}', [BackofficePropertyInspectionItemController::class, 'update'])->name('items.update');
                    Route::post('{propertyInspection}/attachments', [BackofficePropertyInspectionAttachmentController::class, 'store'])->name('attachments.store');
                    Route::post('{propertyInspection}/reports/generate', [BackofficePropertyInspectionReportController::class, 'generate'])->name('reports.generate');
                });

                Route::get('properties/{housingUnit}/technical-history', [BackofficePropertyTechnicalHistoryController::class, 'show'])
                    ->name('properties.technical-history');

                Route::prefix('eligibility')->name('eligibility.')->group(function () {
                    Route::get('rule-sets', [BackofficeEligibilityRuleSetController::class, 'index'])
                        ->name('rule-sets.index');
                    Route::get('rule-sets/create', [BackofficeEligibilityRuleSetController::class, 'create'])
                        ->name('rule-sets.create');
                    Route::post('rule-sets', [BackofficeEligibilityRuleSetController::class, 'store'])
                        ->name('rule-sets.store');
                    Route::get('rule-sets/{eligibilityRuleSet}', [BackofficeEligibilityRuleSetController::class, 'show'])
                        ->name('rule-sets.show');
                    Route::get('rule-sets/{eligibilityRuleSet}/edit', [BackofficeEligibilityRuleSetController::class, 'edit'])
                        ->name('rule-sets.edit');
                    Route::match(['put', 'patch'], 'rule-sets/{eligibilityRuleSet}', [BackofficeEligibilityRuleSetController::class, 'update'])
                        ->name('rule-sets.update');
                    Route::post('rule-sets/{eligibilityRuleSet}/activate', [BackofficeEligibilityRuleSetController::class, 'activate'])
                        ->name('rule-sets.activate');
                    Route::post('rule-sets/{eligibilityRuleSet}/archive', [BackofficeEligibilityRuleSetController::class, 'archive'])
                        ->name('rule-sets.archive');
                    Route::post('rule-sets/{eligibilityRuleSet}/duplicate', [BackofficeEligibilityRuleSetController::class, 'duplicate'])
                        ->name('rule-sets.duplicate');

                    Route::get('rule-sets/{eligibilityRuleSet}/criteria', [BackofficeEligibilityCriterionController::class, 'index'])
                        ->name('criteria.index');
                    Route::get('rule-sets/{eligibilityRuleSet}/criteria/create', [BackofficeEligibilityCriterionController::class, 'create'])
                        ->name('criteria.create');
                    Route::post('rule-sets/{eligibilityRuleSet}/criteria', [BackofficeEligibilityCriterionController::class, 'store'])
                        ->name('criteria.store');
                    Route::get('criteria/{eligibilityCriterion}/edit', [BackofficeEligibilityCriterionController::class, 'edit'])
                        ->name('criteria.edit');
                    Route::match(['put', 'patch'], 'criteria/{eligibilityCriterion}', [BackofficeEligibilityCriterionController::class, 'update'])
                        ->name('criteria.update');
                    Route::post('criteria/{eligibilityCriterion}/activate', [BackofficeEligibilityCriterionController::class, 'activate'])
                        ->name('criteria.activate');
                    Route::post('criteria/{eligibilityCriterion}/inactivate', [BackofficeEligibilityCriterionController::class, 'inactivate'])
                        ->name('criteria.inactivate');

                    Route::get('checks', [BackofficeEligibilityCheckController::class, 'index'])
                        ->name('checks.index');
                    Route::get('checks/{eligibilityCheck}', [BackofficeEligibilityCheckController::class, 'show'])
                        ->name('checks.show');
                    Route::post('checks/{eligibilityCheck}/rerun', [BackofficeEligibilityCheckController::class, 'rerun'])
                        ->name('checks.rerun');
                    Route::post('applications/{application}/run', [BackofficeEligibilityCheckController::class, 'runApplication'])
                        ->name('applications.run');
                });

                Route::prefix('scoring')->name('scoring.')->group(function () {
                    Route::get('rule-sets', [BackofficeScoringRuleSetController::class, 'index'])
                        ->name('rule-sets.index');
                    Route::get('rule-sets/create', [BackofficeScoringRuleSetController::class, 'create'])
                        ->name('rule-sets.create');
                    Route::post('rule-sets', [BackofficeScoringRuleSetController::class, 'store'])
                        ->name('rule-sets.store');
                    Route::get('rule-sets/{scoringRuleSet}', [BackofficeScoringRuleSetController::class, 'show'])
                        ->name('rule-sets.show');
                    Route::get('rule-sets/{scoringRuleSet}/edit', [BackofficeScoringRuleSetController::class, 'edit'])
                        ->name('rule-sets.edit');
                    Route::match(['put', 'patch'], 'rule-sets/{scoringRuleSet}', [BackofficeScoringRuleSetController::class, 'update'])
                        ->name('rule-sets.update');
                    Route::post('rule-sets/{scoringRuleSet}/activate', [BackofficeScoringRuleSetController::class, 'activate'])
                        ->name('rule-sets.activate');
                    Route::post('rule-sets/{scoringRuleSet}/archive', [BackofficeScoringRuleSetController::class, 'archive'])
                        ->name('rule-sets.archive');
                    Route::post('rule-sets/{scoringRuleSet}/duplicate', [BackofficeScoringRuleSetController::class, 'duplicate'])
                        ->name('rule-sets.duplicate');

                    Route::get('rule-sets/{scoringRuleSet}/criteria', [BackofficeScoringCriterionController::class, 'index'])
                        ->name('criteria.index');
                    Route::get('rule-sets/{scoringRuleSet}/criteria/create', [BackofficeScoringCriterionController::class, 'create'])
                        ->name('criteria.create');
                    Route::post('rule-sets/{scoringRuleSet}/criteria', [BackofficeScoringCriterionController::class, 'store'])
                        ->name('criteria.store');
                    Route::get('criteria/{scoringCriterion}/edit', [BackofficeScoringCriterionController::class, 'edit'])
                        ->name('criteria.edit');
                    Route::match(['put', 'patch'], 'criteria/{scoringCriterion}', [BackofficeScoringCriterionController::class, 'update'])
                        ->name('criteria.update');
                    Route::post('criteria/{scoringCriterion}/activate', [BackofficeScoringCriterionController::class, 'activate'])
                        ->name('criteria.activate');
                    Route::post('criteria/{scoringCriterion}/inactivate', [BackofficeScoringCriterionController::class, 'inactivate'])
                        ->name('criteria.inactivate');

                    Route::get('criteria/{scoringCriterion}/rules', [BackofficeScoringRuleController::class, 'index'])
                        ->name('rules.index');
                    Route::get('criteria/{scoringCriterion}/rules/create', [BackofficeScoringRuleController::class, 'create'])
                        ->name('rules.create');
                    Route::post('criteria/{scoringCriterion}/rules', [BackofficeScoringRuleController::class, 'store'])
                        ->name('rules.store');
                    Route::get('rules/{scoringRule}/edit', [BackofficeScoringRuleController::class, 'edit'])
                        ->name('rules.edit');
                    Route::match(['put', 'patch'], 'rules/{scoringRule}', [BackofficeScoringRuleController::class, 'update'])
                        ->name('rules.update');
                    Route::delete('rules/{scoringRule}', [BackofficeScoringRuleController::class, 'destroy'])
                        ->name('rules.destroy');

                    Route::get('rule-sets/{scoringRuleSet}/tie-breakers', [BackofficeTieBreakerRuleController::class, 'index'])
                        ->name('tie-breakers.index');
                    Route::get('rule-sets/{scoringRuleSet}/tie-breakers/create', [BackofficeTieBreakerRuleController::class, 'create'])
                        ->name('tie-breakers.create');
                    Route::post('rule-sets/{scoringRuleSet}/tie-breakers', [BackofficeTieBreakerRuleController::class, 'store'])
                        ->name('tie-breakers.store');
                    Route::get('tie-breakers/{tieBreakerRule}/edit', [BackofficeTieBreakerRuleController::class, 'edit'])
                        ->name('tie-breakers.edit');
                    Route::match(['put', 'patch'], 'tie-breakers/{tieBreakerRule}', [BackofficeTieBreakerRuleController::class, 'update'])
                        ->name('tie-breakers.update');
                    Route::post('tie-breakers/{tieBreakerRule}/activate', [BackofficeTieBreakerRuleController::class, 'activate'])
                        ->name('tie-breakers.activate');
                    Route::post('tie-breakers/{tieBreakerRule}/inactivate', [BackofficeTieBreakerRuleController::class, 'inactivate'])
                        ->name('tie-breakers.inactivate');

                    Route::get('runs', [BackofficeScoringRunController::class, 'index'])
                        ->name('runs.index');
                    Route::get('runs/create', [BackofficeScoringRunController::class, 'create'])
                        ->name('runs.create');
                    Route::post('runs', [BackofficeScoringRunController::class, 'store'])
                        ->name('runs.store');
                    Route::get('runs/{scoringRun}', [BackofficeScoringRunController::class, 'show'])
                        ->name('runs.show');
                    Route::post('runs/{scoringRun}/run', [BackofficeScoringRunController::class, 'run'])
                        ->name('runs.run');
                    Route::post('runs/{scoringRun}/lock', [BackofficeScoringRunController::class, 'lock'])
                        ->name('runs.lock');
                    Route::post('runs/{scoringRun}/cancel', [BackofficeScoringRunController::class, 'cancel'])
                        ->name('runs.cancel');

                    Route::get('application-scores', [BackofficeApplicationScoreController::class, 'index'])
                        ->name('application-scores.index');
                    Route::get('application-scores/{applicationScore}', [BackofficeApplicationScoreController::class, 'show'])
                        ->name('application-scores.show');
                    Route::get('application-scores/{applicationScore}/manual-review', [BackofficeApplicationScoreController::class, 'manualReview'])
                        ->name('application-scores.manual-review');
                    Route::match(['put', 'patch'], 'application-scores/{applicationScore}/manual-review', [BackofficeApplicationScoreController::class, 'updateManualScore'])
                        ->name('application-scores.manual-review.update');
                    Route::post('application-scores/{applicationScore}/lock', [BackofficeApplicationScoreController::class, 'lock'])
                        ->name('application-scores.lock');

                    Route::get('ranking-snapshots', [BackofficeRankingSnapshotController::class, 'index'])
                        ->name('ranking-snapshots.index');
                    Route::get('ranking-snapshots/{rankingSnapshot}', [BackofficeRankingSnapshotController::class, 'show'])
                        ->name('ranking-snapshots.show');
                    Route::post('ranking-snapshots/{rankingSnapshot}/lock', [BackofficeRankingSnapshotController::class, 'lock'])
                        ->name('ranking-snapshots.lock');
                    Route::post('ranking-snapshots/{rankingSnapshot}/archive', [BackofficeRankingSnapshotController::class, 'archive'])
                        ->name('ranking-snapshots.archive');
                });
            });

            Route::resource('citizens', CitizenController::class);
            Route::resource('households', HouseholdController::class);
            Route::resource('housing-units', HousingUnitController::class);
            Route::resource('applications', HousingApplicationController::class);
            Route::resource('contracts', ContractController::class);
            Route::resource('payments', PaymentController::class);
            Route::resource('maintenance-requests', MaintenanceRequestController::class);
            Route::resource('documents', DocumentController::class);
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
