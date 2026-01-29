<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE questions ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (setweight(to_tsvector('english', coalesce(title,'')), 'A') || setweight(to_tsvector('english', coalesce(body_markdown,'')), 'B')) STORED");
            DB::statement('CREATE INDEX questions_search_vector_idx ON questions USING GIN (search_vector)');

            DB::statement("ALTER TABLE answers ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (setweight(to_tsvector('english', coalesce(body_markdown,'')), 'B')) STORED");
            DB::statement('CREATE INDEX answers_search_vector_idx ON answers USING GIN (search_vector)');
        } else {
            DB::statement('ALTER TABLE questions ADD COLUMN search_vector TEXT');
            DB::statement('ALTER TABLE answers ADD COLUMN search_vector TEXT');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS questions_search_vector_idx');
            DB::statement('ALTER TABLE questions DROP COLUMN IF EXISTS search_vector');

            DB::statement('DROP INDEX IF EXISTS answers_search_vector_idx');
            DB::statement('ALTER TABLE answers DROP COLUMN IF EXISTS search_vector');
        } else {
            DB::statement('ALTER TABLE questions DROP COLUMN IF EXISTS search_vector');
            DB::statement('ALTER TABLE answers DROP COLUMN IF EXISTS search_vector');
        }
    }
};
