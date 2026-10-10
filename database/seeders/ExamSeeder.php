<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Exam;
use App\Models\User;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $dbCategory = Category::where('code', 'DB-PGSQL')->first();
        $sweCategory = Category::where('code', 'TECH-SWE')->first();
        $aptCategory = Category::where('code', 'GEN-APT')->first();

        // Exam 1: PostgreSQL 18
        Exam::updateOrCreate(
            ['code' => 'EXAM-PGSQL-2026'],
            [
                'category_id'              => $dbCategory->id,
                'title'                    => 'PostgreSQL 18 Database Architecture & Query Mastery',
                'description'              => 'Comprehensive assessment on PostgreSQL 18 features, JSONB queries, indexing strategies, VACUUM optimizations, and transactional concurrency.',
                'instructions'             => '1. Total Duration: 45 Minutes.
2. Fullscreen mode is enforced. Leaving the screen or switching tabs will be counted as a proctor violation.
3. Autosave is active. Your responses are committed asynchronously.
4. Once time expires, the assessment auto-submits.',
                'duration_minutes'         => 45,
                'pass_percentage'          => 65.00,
                'total_marks'              => 50.00,
                'shuffle_questions'        => true,
                'shuffle_options'          => true,
                'show_result_instant'      => true,
                'allow_review'             => true,
                'negative_marking'         => true,
                'negative_mark_rate'       => 0.25,
                'enforce_fullscreen'       => true,
                'max_violations'           => 3,
                'auto_submit_on_violation' => true,
                'status'                   => 'published',
                'is_active'                => true,
                'created_by'               => $admin?->id,
            ]
        );

        // Exam 2: Software Engineering & Architecture
        Exam::updateOrCreate(
            ['code' => 'EXAM-SWE-2026'],
            [
                'category_id'              => $sweCategory->id,
                'title'                    => 'Full-Stack Software Architecture & Laravel 13 Engineering',
                'description'              => 'Core engineering test covering modern Laravel principles, MVC patterns, RESTful API contract design, and caching layers.',
                'instructions'             => 'Ensure you are in a quiet room with a stable internal network connection. Read all question prompts carefully before selecting an answer.',
                'duration_minutes'         => 60,
                'pass_percentage'          => 60.00,
                'total_marks'              => 40.00,
                'shuffle_questions'        => false,
                'shuffle_options'          => true,
                'show_result_instant'      => true,
                'allow_review'             => true,
                'negative_marking'         => false,
                'negative_mark_rate'       => 0.00,
                'enforce_fullscreen'       => true,
                'max_violations'           => 3,
                'auto_submit_on_violation' => true,
                'status'                   => 'published',
                'is_active'                => true,
                'created_by'               => $admin?->id,
            ]
        );

        // Exam 3: General Aptitude
        Exam::updateOrCreate(
            ['code' => 'EXAM-APT-2026'],
            [
                'category_id'              => $aptCategory->id,
                'title'                    => 'Cognitive Aptitude & Logical Problem Solving',
                'description'              => 'Evaluation of quantitative aptitude, pattern synthesis, and critical business logic.',
                'instructions'             => 'Calculators are not permitted. Use scratch paper if needed.',
                'duration_minutes'         => 30,
                'pass_percentage'          => 50.00,
                'total_marks'              => 20.00,
                'shuffle_questions'        => true,
                'shuffle_options'          => true,
                'show_result_instant'      => true,
                'allow_review'             => true,
                'negative_marking'         => false,
                'enforce_fullscreen'       => false,
                'max_violations'           => 5,
                'auto_submit_on_violation' => false,
                'status'                   => 'published',
                'is_active'                => true,
                'created_by'               => $admin?->id,
            ]
        );
    }
}
