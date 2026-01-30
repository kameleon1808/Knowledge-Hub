<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VECTOR_DIMENSION = 1536;

    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content_text');
            $table->string('content_hash', 64);
            $table->unsignedInteger('tokens_count')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_item_id', 'chunk_index']);
            $table->index('content_hash');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(%d)',
                self::VECTOR_DIMENSION
            ));
            DB::statement('CREATE INDEX knowledge_chunks_embedding_idx ON knowledge_chunks USING hnsw (embedding vector_cosine_ops)');
        } else {
            Schema::table('knowledge_chunks', function (Blueprint $table): void {
                $table->text('embedding')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
