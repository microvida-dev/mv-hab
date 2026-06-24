<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mfa_required')->default(false)->after('last_login_at');
            $table->text('internal_notes')->nullable()->after('mfa_required');
            $table->timestamp('deactivated_at')->nullable()->after('internal_notes');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reactivated_at')->nullable()->after('deactivated_by');
            $table->foreignId('reactivated_by')->nullable()->after('reactivated_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('municipal_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 40)->default('active')->index();
            $table->json('functional_scopes')->nullable();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('municipal_team_user', function (Blueprint $table) {
            $table->foreignId('municipal_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_in_team', 120)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->primary(['municipal_team_id', 'user_id']);
            $table->index(['user_id', 'left_at'], 'municipal_team_user_active_idx');
        });

        Schema::create('access_change_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 120)->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('municipal_team_id')->nullable()->constrained()->nullOnDelete();
            $table->text('justification');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['target_user_id', 'event_code'], 'access_events_target_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_change_events');
        Schema::dropIfExists('municipal_team_user');
        Schema::dropIfExists('municipal_teams');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reactivated_by');
            $table->dropColumn('reactivated_at');
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn([
                'deactivated_at',
                'internal_notes',
                'mfa_required',
            ]);
        });
    }
};
