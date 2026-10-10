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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status')->default('in_progress')->index(); // in_progress, completed, timed_out, terminated_violation
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('answered_questions')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('incorrect_answers')->default(0);
            $table->decimal('score', 8, 2)->default(0.00);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->boolean('is_passed')->default(false);
            $table->unsignedInteger('violation_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('metadata')->nullable(); // Stores questions order, device specs, etc.
            $table->timestamps();

            $table->index(['exam_id', 'user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
