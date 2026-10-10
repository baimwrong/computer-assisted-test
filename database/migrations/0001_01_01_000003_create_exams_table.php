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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('title');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('pass_percentage', 5, 2)->default(60.00);
            $table->decimal('total_marks', 8, 2)->default(100.00);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_result_instant')->default(true);
            $table->boolean('allow_review')->default(true);
            $table->boolean('negative_marking')->default(false);
            $table->decimal('negative_mark_rate', 5, 2)->default(0.25);
            $table->boolean('enforce_fullscreen')->default(true);
            $table->unsignedInteger('max_violations')->default(3);
            $table->boolean('auto_submit_on_violation')->default(true);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('published'); // draft, published, archived
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for fast lookup
            $table->index(['category_id', 'is_active', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
