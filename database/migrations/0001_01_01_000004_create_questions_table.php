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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->text('question_text');
            $table->string('question_type')->default('single_choice'); // single_choice, multiple_choice, true_false, short_answer
            $table->decimal('points', 5, 2)->default(1.00);
            $table->decimal('negative_points', 5, 2)->default(0.00);
            $table->string('difficulty')->default('medium'); // easy, medium, hard
            $table->text('explanation')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['exam_id', 'is_active']);
            $table->index(['category_id', 'difficulty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
