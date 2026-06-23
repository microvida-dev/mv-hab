<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('household_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('qualification_level')->nullable()->after('professional_status');
            $table->boolean('has_multiple_disabilities')->default(false)->after('is_disabled');
            $table->boolean('is_pregnant')->default(false)->after('has_multiple_disabilities');
            $table->boolean('is_exempt_from_irs')->default(false)->after('has_no_income');
        });
    }

    public function down(): void
    {
        Schema::table('household_members', function (Blueprint $table) {
            $table->dropColumn([
                'qualification_level',
                'has_multiple_disabilities',
                'is_pregnant',
                'is_exempt_from_irs',
            ]);
        });
    }
};
