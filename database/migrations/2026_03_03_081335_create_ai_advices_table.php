<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('basis_data')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_advices');
    }
};
