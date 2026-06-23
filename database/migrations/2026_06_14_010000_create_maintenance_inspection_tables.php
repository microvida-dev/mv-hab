<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_urgency', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id', 'maint_cat_parent_fk')->references('id')->on('maintenance_categories')->nullOnDelete();
        });

        Schema::create('maintenance_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('tax_number', 80)->nullable();
            $table->text('service_scope')->nullable();
            $table->string('status', 80)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('request_number')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('lease_contract_id')->nullable()->after('citizen_id');
            $table->unsignedBigInteger('application_id')->nullable()->after('lease_contract_id');
            $table->unsignedBigInteger('user_id')->nullable()->after('application_id');
            $table->unsignedBigInteger('maintenance_category_id')->nullable()->after('user_id');
            $table->string('source', 80)->default('municipal_technician')->after('maintenance_category_id');
            $table->string('urgency', 80)->default('normal')->after('priority');
            $table->string('technical_priority', 80)->nullable()->after('urgency');
            $table->string('location_in_property')->nullable()->after('description');
            $table->text('tenant_availability')->nullable()->after('location_in_property');
            $table->text('access_instructions')->nullable()->after('tenant_availability');
            $table->text('review_notes')->nullable()->after('access_instructions');
            $table->text('resolution_summary')->nullable()->after('review_notes');
            $table->text('rejection_reason')->nullable()->after('resolution_summary');
            $table->text('closure_notes')->nullable()->after('rejection_reason');
            $table->timestamp('scheduled_for')->nullable()->after('reported_at');
            $table->timestamp('reviewed_at')->nullable()->after('resolved_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            $table->timestamp('closed_at')->nullable()->after('reviewed_by');
            $table->unsignedBigInteger('closed_by')->nullable()->after('closed_at');
            $table->timestamp('cancelled_at')->nullable()->after('closed_by');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('created_by')->nullable()->after('cancelled_by');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'maint_req_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('application_id', 'maint_req_application_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('user_id', 'maint_req_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('maintenance_category_id', 'maint_req_category_fk')->references('id')->on('maintenance_categories')->nullOnDelete();
            $table->foreign('reviewed_by', 'maint_req_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by', 'maint_req_closed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'maint_req_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'maint_req_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'maint_req_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'urgency'], 'maint_req_status_urgency_idx');
            $table->index(['housing_unit_id', 'status'], 'maint_req_unit_status_idx');
            $table->index(['lease_contract_id', 'status'], 'maint_req_contract_status_idx');
        });

        Schema::create('maintenance_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->string('from_status', 80)->nullable();
            $table->string('to_status', 80);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('maintenance_request_id', 'mr_hist_request_fk')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('changed_by', 'mr_hist_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('maintenance_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->string('assignment_type', 80);
            $table->string('status', 80)->default('assigned')->index();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('maintenance_supplier_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('maintenance_request_id', 'mr_assign_request_fk')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('assigned_user_id', 'mr_assign_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('maintenance_supplier_id', 'mr_assign_supplier_fk')->references('id')->on('maintenance_suppliers')->nullOnDelete();
            $table->foreign('assigned_by', 'mr_assign_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('maintenance_interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('performed_by_user_id')->nullable();
            $table->unsignedBigInteger('maintenance_supplier_id')->nullable();
            $table->string('status', 80)->default('planned')->index();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('work_description')->nullable();
            $table->text('materials_used')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('next_steps')->nullable();
            $table->boolean('requires_follow_up')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('maintenance_request_id', 'mr_inter_request_fk')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('housing_unit_id', 'mr_inter_unit_fk')->references('id')->on('housing_units')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'mr_inter_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('performed_by_user_id', 'mr_inter_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('maintenance_supplier_id', 'mr_inter_supplier_fk')->references('id')->on('maintenance_suppliers')->nullOnDelete();
            $table->foreign('created_by', 'mr_inter_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('maintenance_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('maintenance_intervention_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('attachment_type', 80)->default('photo');
            $table->string('original_filename');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();
            $table->boolean('visible_to_tenant')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('maintenance_request_id', 'mr_attach_request_fk')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('maintenance_intervention_id', 'mr_attach_inter_fk')->references('id')->on('maintenance_interventions')->nullOnDelete();
            $table->foreign('uploaded_by', 'mr_attach_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('maintenance_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_request_id');
            $table->unsignedBigInteger('maintenance_intervention_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('maintenance_supplier_id')->nullable();
            $table->string('cost_type', 80);
            $table->string('status', 80)->default('estimated')->index();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('invoice_reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('maintenance_request_id', 'mr_cost_request_fk')->references('id')->on('maintenance_requests')->cascadeOnDelete();
            $table->foreign('maintenance_intervention_id', 'mr_cost_inter_fk')->references('id')->on('maintenance_interventions')->nullOnDelete();
            $table->foreign('housing_unit_id', 'mr_cost_unit_fk')->references('id')->on('housing_units')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'mr_cost_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('maintenance_supplier_id', 'mr_cost_supplier_fk')->references('id')->on('maintenance_suppliers')->nullOnDelete();
            $table->foreign('registered_by', 'mr_cost_registered_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'mr_cost_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['housing_unit_id', 'status'], 'mr_cost_unit_status_idx');
        });

        Schema::create('inspection_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('inspection_type', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version_number')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'insp_template_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('inspection_checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_checklist_template_id');
            $table->string('code');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('area')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('inspection_checklist_template_id', 'insp_tpl_item_tpl_fk')->references('id')->on('inspection_checklist_templates')->cascadeOnDelete();
        });

        Schema::create('property_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number')->unique();
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('inspection_checklist_template_id')->nullable();
            $table->string('inspection_type', 80);
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedBigInteger('inspector_user_id')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->string('general_condition', 80)->nullable();
            $table->text('summary')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('tenant_visible')->default(false);
            $table->boolean('tenant_present')->default(false);
            $table->text('tenant_observations')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('housing_unit_id', 'prop_insp_unit_fk')->references('id')->on('housing_units')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'prop_insp_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('application_id', 'prop_insp_application_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('inspection_checklist_template_id', 'prop_insp_template_fk')->references('id')->on('inspection_checklist_templates')->nullOnDelete();
            $table->foreign('inspector_user_id', 'prop_insp_inspector_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('validated_by', 'prop_insp_validated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'prop_insp_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['housing_unit_id', 'status'], 'prop_insp_unit_status_idx');
        });

        Schema::create('property_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_inspection_id');
            $table->unsignedBigInteger('inspection_checklist_template_item_id')->nullable();
            $table->string('code')->nullable();
            $table->string('label');
            $table->string('area')->nullable();
            $table->string('condition', 80)->nullable();
            $table->text('observations')->nullable();
            $table->boolean('requires_maintenance')->default(false);
            $table->unsignedBigInteger('maintenance_request_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('property_inspection_id', 'prop_item_insp_fk')->references('id')->on('property_inspections')->cascadeOnDelete();
            $table->foreign('inspection_checklist_template_item_id', 'prop_item_tpl_item_fk')->references('id')->on('inspection_checklist_template_items')->nullOnDelete();
            $table->foreign('maintenance_request_id', 'prop_item_mr_fk')->references('id')->on('maintenance_requests')->nullOnDelete();
        });

        Schema::create('property_inspection_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_inspection_id');
            $table->unsignedBigInteger('property_inspection_item_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('attachment_type', 80)->default('photo');
            $table->string('original_filename');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();
            $table->boolean('visible_to_tenant')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_inspection_id', 'prop_attach_insp_fk')->references('id')->on('property_inspections')->cascadeOnDelete();
            $table->foreign('property_inspection_item_id', 'prop_attach_item_fk')->references('id')->on('property_inspection_items')->nullOnDelete();
            $table->foreign('uploaded_by', 'prop_attach_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('property_inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_inspection_id');
            $table->string('report_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('checksum')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('property_inspection_id', 'prop_report_insp_fk')->references('id')->on('property_inspections')->cascadeOnDelete();
            $table->foreign('generated_by', 'prop_report_generated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('validated_by', 'prop_report_validated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('property_history_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('maintenance_request_id')->nullable();
            $table->unsignedBigInteger('maintenance_intervention_id')->nullable();
            $table->unsignedBigInteger('maintenance_cost_id')->nullable();
            $table->unsignedBigInteger('property_inspection_id')->nullable();
            $table->unsignedBigInteger('property_inspection_report_id')->nullable();
            $table->string('event_type', 120)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->boolean('visible_to_tenant')->default(false);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('housing_unit_id', 'prop_hist_unit_fk')->references('id')->on('housing_units')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'prop_hist_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('maintenance_request_id', 'prop_hist_request_fk')->references('id')->on('maintenance_requests')->nullOnDelete();
            $table->foreign('maintenance_intervention_id', 'prop_hist_inter_fk')->references('id')->on('maintenance_interventions')->nullOnDelete();
            $table->foreign('maintenance_cost_id', 'prop_hist_cost_fk')->references('id')->on('maintenance_costs')->nullOnDelete();
            $table->foreign('property_inspection_id', 'prop_hist_insp_fk')->references('id')->on('property_inspections')->nullOnDelete();
            $table->foreign('property_inspection_report_id', 'prop_hist_report_fk')->references('id')->on('property_inspection_reports')->nullOnDelete();
            $table->foreign('actor_id', 'prop_hist_actor_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['housing_unit_id', 'event_type'], 'prop_hist_unit_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_history_events');
        Schema::dropIfExists('property_inspection_reports');
        Schema::dropIfExists('property_inspection_attachments');
        Schema::dropIfExists('property_inspection_items');
        Schema::dropIfExists('property_inspections');
        Schema::dropIfExists('inspection_checklist_template_items');
        Schema::dropIfExists('inspection_checklist_templates');
        Schema::dropIfExists('maintenance_costs');
        Schema::dropIfExists('maintenance_attachments');
        Schema::dropIfExists('maintenance_interventions');
        Schema::dropIfExists('maintenance_assignments');
        Schema::dropIfExists('maintenance_request_status_histories');

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropForeign('maint_req_contract_fk');
            $table->dropForeign('maint_req_application_fk');
            $table->dropForeign('maint_req_user_fk');
            $table->dropForeign('maint_req_category_fk');
            $table->dropForeign('maint_req_reviewed_by_fk');
            $table->dropForeign('maint_req_closed_by_fk');
            $table->dropForeign('maint_req_cancelled_by_fk');
            $table->dropForeign('maint_req_created_by_fk');
            $table->dropForeign('maint_req_updated_by_fk');
            $table->dropIndex('maint_req_status_urgency_idx');
            $table->dropIndex('maint_req_unit_status_idx');
            $table->dropIndex('maint_req_contract_status_idx');
            $table->dropColumn([
                'request_number',
                'lease_contract_id',
                'application_id',
                'user_id',
                'maintenance_category_id',
                'source',
                'urgency',
                'technical_priority',
                'location_in_property',
                'tenant_availability',
                'access_instructions',
                'review_notes',
                'resolution_summary',
                'rejection_reason',
                'closure_notes',
                'scheduled_for',
                'reviewed_at',
                'reviewed_by',
                'closed_at',
                'closed_by',
                'cancelled_at',
                'cancelled_by',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('maintenance_suppliers');
        Schema::dropIfExists('maintenance_categories');
    }
};
