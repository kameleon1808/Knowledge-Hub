<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->boolean('ai_generated')->default(false)->after('body_html');
            $table->uuid('ai_audit_log_id')->nullable()->after('ai_generated');
            $table->foreign('ai_audit_log_id')->references('id')->on('ai_audit_logs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->dropForeign(['ai_audit_log_id']);
            $table->dropColumn(['ai_generated', 'ai_audit_log_id']);
        });
    }
};
