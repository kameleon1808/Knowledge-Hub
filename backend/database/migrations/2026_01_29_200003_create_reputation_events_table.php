<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reputation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('event_type', 30);
            $table->integer('points');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'actor_user_id', 'subject_type', 'subject_id', 'event_type'], 'rep_events_unique');
            $table->index(['subject_type', 'subject_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reputation_events');
    }
};
