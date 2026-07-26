<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_import_batches', function (Blueprint $table): void {
            $table->foreignId('municipality_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_import_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipality_id');
        });
    }
};
