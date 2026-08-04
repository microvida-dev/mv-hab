<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateExists = DB::table('municipalities')
            ->whereNotNull('contact_email')
            ->where('contact_email', '!=', '')
            ->selectRaw('LOWER(contact_email) AS normalized_email, COUNT(*) AS total')
            ->groupByRaw('LOWER(contact_email)')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateExists) {
            throw new RuntimeException(
                'Não é possível criar o índice único de email institucional: existem Municípios duplicados.',
            );
        }

        Schema::table('municipalities', function (Blueprint $table): void {
            $table->unique('contact_email', 'municipalities_contact_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table): void {
            $table->dropUnique('municipalities_contact_email_unique');
        });
    }
};
