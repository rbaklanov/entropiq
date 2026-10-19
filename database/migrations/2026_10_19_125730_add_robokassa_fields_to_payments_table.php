<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('plan', 20)->nullable()->after('subscription_id');
            $table->boolean('is_recurring')->default(false)->after('plan');
            $table->foreignId('parent_payment_id')->nullable()->after('is_recurring')->constrained('payments')->nullOnDelete();
            $table->bigInteger('refunded_amount')->default(0)->after('amount');
            $table->string('refund_request_id')->nullable()->after('provider_payment_id');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });
    }
};
