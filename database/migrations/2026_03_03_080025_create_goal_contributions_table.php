<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount');
            $table->date('date');
            $table->timestamps();

            $table->index('goal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
    }
};
