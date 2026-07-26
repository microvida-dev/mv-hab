<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_favorites', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('metadata');
            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('navigation_favorites', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
