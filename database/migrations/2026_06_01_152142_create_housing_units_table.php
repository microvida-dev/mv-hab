<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('housing_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('address');
            $table->string('typology');
            $table->unsignedTinyInteger('bedrooms');
            $table->decimal('monthly_rent', 10, 2);
            $table->string('status')->default('available')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('housing_units');
    }
};
