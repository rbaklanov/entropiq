<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpi_values', function (Blueprint $table) {
            $table->id();
            $table->date('period');
            $table->string('category_code', 20);
            $table->decimal('value', 8, 2);
            $table->string('source', 50)->default('emiss');
            $table->timestamps();

            $table->unique(['period', 'category_code']);
            $table->index('category_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpi_values');
    }
};
