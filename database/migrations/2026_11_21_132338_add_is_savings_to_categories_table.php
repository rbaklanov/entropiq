<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_savings')->default(false)->after('is_system');
        });

        DB::table('categories')
            ->where('is_system', true)
            ->where('type', 'expense')
            ->where('name->ru', 'Накопления')
            ->update(['is_savings' => true]);
    }
};
