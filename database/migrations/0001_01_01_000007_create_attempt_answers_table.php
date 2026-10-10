<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->jsonb('selected_option_ids')->nullable(); // Array of chosen option IDs
            $table->text('text_answer')->nullable(); // String response for short_answer
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 5, 2)->default(0.00);
            $table->boolean('is_marked_for_review')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'is_marked_for_review']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
