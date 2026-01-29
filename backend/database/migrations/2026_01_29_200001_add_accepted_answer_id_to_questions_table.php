<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('accepted_answer_id')
                ->nullable()
                ->constrained('answers')
                ->nullOnDelete();

            $table->index('accepted_answer_id');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['accepted_answer_id']);
            $table->dropIndex(['accepted_answer_id']);
            $table->dropColumn('accepted_answer_id');
        });
    }
};
