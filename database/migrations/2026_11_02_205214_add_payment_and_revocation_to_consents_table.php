<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'type', 'version']);
            $table->index(['user_id', 'type']);

            $table->foreignId('payment_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('accepted_at');
        });
    }
};
