<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affordable_rent_regulatory_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('parent_profile_id')->nullable();
            $table->string('legal_regime', 40)->index();
            $table->string('code', 100);
            $table->string('version', 60);
            $table->string('name');
            $table->text('legal_basis');
            $table->date('effective_from')->index();
            $table->date('effective_until')->nullable()->index();
            $table->string('status', 30)->index();
            $table->string('configuration_status', 40)->index();
            $table->text('official_source')->nullable();
            $table->string('publication_reference')->nullable();
            $table->string('source_version')->nullable();
            $table->decimal('maximum_effort_rate_percentage', 5, 2)->nullable();
            $table->decimal('minimum_adult_monthly_income', 12, 2)->nullable();
            $table->decimal('annual_income_base_limit', 12, 2)->nullable();
            $table->decimal('second_person_increment', 12, 2)->nullable();
            $table->decimal('additional_person_increment', 12, 2)->nullable();
            $table->unsignedSmallInteger('minimum_contract_months')->nullable();
            $table->unsignedSmallInteger('standard_contract_months')->nullable();
            $table->boolean('rent_limits_configured')->default(false);
            $table->boolean('eligibility_rules_configured')->default(false);
            $table->boolean('typology_rules_configured')->default(false);
            $table->boolean('contract_terms_configured')->default(false);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['code', 'version'], 'arrp_code_version_unique');
            $table->index(
                ['municipality_id', 'legal_regime', 'status', 'effective_from'],
                'arrp_municipality_regime_active_idx',
            );
            $table->foreign('parent_profile_id', 'arrp_parent_fk')
                ->references('id')
                ->on('affordable_rent_regulatory_profiles')
                ->restrictOnDelete();
        });

        Schema::create('regulatory_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('regulatory_profile_id')
                ->constrained('affordable_rent_regulatory_profiles')
                ->restrictOnDelete();
            $table->string('legal_regime', 40)->index();
            $table->string('context', 60)->index();
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->dateTime('reference_date');
            $table->string('profile_code', 100);
            $table->string('profile_version', 60);
            $table->text('legal_basis');
            $table->json('rule_sets')->nullable();
            $table->json('limits')->nullable();
            $table->json('parameters')->nullable();
            $table->json('municipal_overlay')->nullable();
            $table->string('origin', 80);
            $table->char('checksum', 64)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at');
            $table->timestamps();

            $table->unique(
                ['source_type', 'source_id', 'context'],
                'regulatory_snapshot_source_context_unique',
            );
            $table->index(
                ['municipality_id', 'legal_regime', 'reference_date'],
                'regulatory_snapshot_scope_idx',
            );
        });

        $this->addProfileReference('eligibility_rule_sets');
        $this->addProfileReference('rent_rule_sets');
        $this->addProfileReference('typology_adequacy_rules');
        $this->addProfileReference('allocation_rule_sets');

        $this->addRegulatoryContext('programs', includeProfile: true);
        $this->addRegulatoryContext('contests', includeProfile: true);
        $this->addRegulatoryContext('applications');
        $this->addRegulatoryContext('eligibility_checks');
        $this->addRegulatoryContext('rent_calculations');
        $this->addRegulatoryContext('contracts', includeClassification: true);
    }

    public function down(): void
    {
        $this->dropRegulatoryContext('contracts', includeClassification: true);
        $this->dropRegulatoryContext('rent_calculations');
        $this->dropRegulatoryContext('eligibility_checks');
        $this->dropRegulatoryContext('applications');
        $this->dropRegulatoryContext('contests', includeProfile: true);
        $this->dropRegulatoryContext('programs', includeProfile: true);

        $this->dropProfileReference('allocation_rule_sets');
        $this->dropProfileReference('typology_adequacy_rules');
        $this->dropProfileReference('rent_rule_sets');
        $this->dropProfileReference('eligibility_rule_sets');

        Schema::dropIfExists('regulatory_snapshots');
        Schema::dropIfExists('affordable_rent_regulatory_profiles');
    }

    private function addProfileReference(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('regulatory_profile_id')
                ->nullable()
                ->after('id')
                ->constrained('affordable_rent_regulatory_profiles')
                ->restrictOnDelete();
        });
    }

    private function dropProfileReference(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropConstrainedForeignId('regulatory_profile_id');
        });
    }

    private function addRegulatoryContext(
        string $tableName,
        bool $includeProfile = false,
        bool $includeClassification = false,
    ): void {
        Schema::table($tableName, function (Blueprint $table) use ($includeProfile, $includeClassification): void {
            if ($includeProfile) {
                $table->foreignId('regulatory_profile_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('affordable_rent_regulatory_profiles')
                    ->restrictOnDelete();
            }

            $table->foreignId('regulatory_snapshot_id')
                ->nullable()
                ->after($includeProfile ? 'regulatory_profile_id' : 'id')
                ->constrained('regulatory_snapshots')
                ->restrictOnDelete();
            $table->string('legal_regime', 40)
                ->nullable()
                ->after('regulatory_snapshot_id')
                ->index();

            if ($includeClassification) {
                $table->string('regulatory_classification_status', 40)
                    ->nullable()
                    ->after('legal_regime')
                    ->index();
            }
        });
    }

    private function dropRegulatoryContext(
        string $tableName,
        bool $includeProfile = false,
        bool $includeClassification = false,
    ): void {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $includeProfile, $includeClassification): void {
            if ($includeClassification) {
                $table->dropIndex("{$tableName}_regulatory_classification_status_index");
                $table->dropColumn('regulatory_classification_status');
            }

            $table->dropIndex("{$tableName}_legal_regime_index");
            $table->dropColumn('legal_regime');
            $table->dropConstrainedForeignId('regulatory_snapshot_id');

            if ($includeProfile) {
                $table->dropConstrainedForeignId('regulatory_profile_id');
            }
        });
    }
};
