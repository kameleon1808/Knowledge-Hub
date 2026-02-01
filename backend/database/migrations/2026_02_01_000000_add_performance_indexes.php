<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->index('created_at', 'questions_created_at_idx');
        });

        Schema::table('answers', function (Blueprint $table): void {
            $table->index('created_at', 'answers_created_at_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->index('question_id', 'bookmarks_question_id_idx');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->index('user_id', 'comments_user_id_idx');
        });

        Schema::table('question_tag', function (Blueprint $table): void {
            $table->index('tag_id', 'question_tag_tag_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex('questions_created_at_idx');
        });

        Schema::table('answers', function (Blueprint $table): void {
            $table->dropIndex('answers_created_at_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table): void {
            $table->dropIndex('bookmarks_question_id_idx');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_user_id_idx');
        });

        Schema::table('question_tag', function (Blueprint $table): void {
            $table->dropIndex('question_tag_tag_id_idx');
        });
    }
};
