<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->string('status', 20)->default('active');
            $table->string('icon', 20)->nullable();
            $table->bigInteger('target_amount');
            $table->bigInteger('current_amount')->default(0);
            $table->string('currency_code', 3)->default('RUB');
            $table->date('started_at');
            $table->date('target_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
