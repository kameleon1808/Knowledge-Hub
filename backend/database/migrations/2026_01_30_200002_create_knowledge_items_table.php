<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16); // document | email
            $table->string('title');
            $table->json('source_meta')->nullable();
            $table->string('original_content_path')->nullable();
            $table->longText('raw_text')->nullable();
            $table->string('status', 16)->default('pending'); // pending | processed | failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_items');
    }
};
