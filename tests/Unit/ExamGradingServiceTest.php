<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use App\Services\ExamGradingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamGradingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExamGradingService $gradingService;
    protected User $candidate;
    protected Category $category;
    protected Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gradingService = app(ExamGradingService::class);

        $this->candidate = User::create([
            'name'       => 'Test Candidate',
            'email'      => 'candidate@test.local',
            'student_id' => 'STU-001',
            'role'       => 'candidate',
            'password'   => bcrypt('password'),
            'is_active'  => true,
        ]);

        $this->category = Category::create([
            'name' => 'General Science',
            'code' => 'GEN-SCI',
        ]);

        $this->exam = Exam::create([
            'category_id'        => $this->category->id,
            'title'              => 'Sample Science Exam',
            'code'               => 'EXAM-SCI-01',
            'duration_minutes'   => 30,
            'pass_percentage'    => 50.00,
            'total_marks'        => 20.00,
            'negative_marking'   => true,
            'negative_mark_rate' => 0.50,
            'status'             => 'published',
            'is_active'          => true,
        ]);
    }

    public function test_single_choice_grading_awards_correct_points(): void
    {
        $question = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'What is H2O?',
            'question_type' => 'single_choice',
            'points'        => 5.00,
            'is_active'     => true,
        ]);

        $wrongOpt = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Oxygen',
            'is_correct'  => false,
            'order_index' => 1,
        ]);

        $correctOpt = QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Water',
            'is_correct'  => true,
            'order_index' => 2,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(30),
            'total_questions' => 1,
        ]);

        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $question->id,
            'selected_option_ids' => [$correctOpt->id],
        ]);

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        $this->assertEquals(5.00, (float) $graded->score);
        $this->assertEquals(100.00, (float) $graded->percentage);
        $this->assertTrue((bool) $graded->is_passed);
        $this->assertEquals(1, $graded->correct_answers);
    }

    public function test_multiple_choice_awards_partial_credit_without_negative_penalty(): void
    {
        $question = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'Select prime numbers (Select all that apply)',
            'question_type' => 'multiple_choice',
            'points'        => 4.00,
            'is_active'     => true,
        ]);

        $opt2 = QuestionOption::create(['question_id' => $question->id, 'option_text' => '2', 'is_correct' => true, 'order_index' => 1]);
        $opt3 = QuestionOption::create(['question_id' => $question->id, 'option_text' => '3', 'is_correct' => true, 'order_index' => 2]);
        $opt4 = QuestionOption::create(['question_id' => $question->id, 'option_text' => '4', 'is_correct' => false, 'order_index' => 3]);

        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(30),
            'total_questions' => 1,
        ]);

        // Candidate chose 2 out of 2 correct: opt2 only (1 out of 2 = 50% partial credit)
        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $question->id,
            'selected_option_ids' => [$opt2->id],
        ]);

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        $this->assertEquals(2.00, (float) $graded->score); // 50% of 4.00 = 2.00
        $this->assertTrue((bool) $graded->is_passed);
    }

    public function test_short_answer_normalizes_spaces_and_punctuation(): void
    {
        $question = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'What is the capital of France?',
            'question_type' => 'short_answer',
            'points'        => 5.00,
            'is_active'     => true,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => 'Paris',
            'is_correct'  => true,
            'order_index' => 1,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(30),
            'total_questions' => 1,
        ]);

        // User typed with surrounding space and trailing period: "  paris.  "
        AttemptAnswer::create([
            'attempt_id'  => $attempt->id,
            'question_id' => $question->id,
            'text_answer' => '  paris.  ',
        ]);

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        $this->assertEquals(5.00, (float) $graded->score);
        $this->assertTrue((bool) $graded->is_passed);
    }

    public function test_negative_marking_penalty_applies_correctly(): void
    {
        $question = Question::create([
            'exam_id'         => $this->exam->id,
            'question_text'   => 'Is Earth flat?',
            'question_type'   => 'true_false',
            'points'          => 5.00,
            'negative_points' => 1.00, // explicit penalty
            'is_active'       => true,
        ]);

        $optTrue = QuestionOption::create(['question_id' => $question->id, 'option_text' => 'True', 'is_correct' => false, 'order_index' => 1]);
        $optFalse = QuestionOption::create(['question_id' => $question->id, 'option_text' => 'False', 'is_correct' => true, 'order_index' => 2]);

        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(30),
            'total_questions' => 1,
        ]);

        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $question->id,
            'selected_option_ids' => [$optTrue->id], // wrong answer
        ]);

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        $this->assertEquals(0.00, (float) $graded->score); // Floored at 0.00
        $this->assertFalse((bool) $graded->is_passed);
        $this->assertEquals(1, $graded->incorrect_answers);
    }

    public function test_exam_level_negative_marking_rate_applies_when_question_negative_points_is_zero(): void
    {
        // Question 1: Correct answer (5 points)
        $q1 = Question::create([
            'exam_id'         => $this->exam->id,
            'question_text'   => 'What is 2 + 2?',
            'question_type'   => 'single_choice',
            'points'          => 5.00,
            'negative_points' => 0.00, // default - should inherit exam rate (0.50)
            'is_active'       => true,
        ]);
        $q1Correct = QuestionOption::create(['question_id' => $q1->id, 'option_text' => '4', 'is_correct' => true, 'order_index' => 1]);

        // Question 2: Incorrect answer (-0.50 from exam negative_mark_rate)
        $q2 = Question::create([
            'exam_id'         => $this->exam->id,
            'question_text'   => 'Is the sun a planet?',
            'question_type'   => 'true_false',
            'points'          => 5.00,
            'negative_points' => 0.00, // default - should inherit exam rate (0.50)
            'is_active'       => true,
        ]);
        $q2Wrong = QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'True', 'is_correct' => false, 'order_index' => 1]);
        $q2Right = QuestionOption::create(['question_id' => $q2->id, 'option_text' => 'False', 'is_correct' => true, 'order_index' => 2]);

        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(30),
            'total_questions' => 2,
        ]);

        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $q1->id,
            'selected_option_ids' => [$q1Correct->id],
        ]);

        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $q2->id,
            'selected_option_ids' => [$q2Wrong->id], // wrong!
        ]);

        $graded = $this->gradingService->gradeAttempt($attempt, 'completed');

        // Score should be 5.00 - 0.50 = 4.50
        $this->assertEquals(4.50, (float) $graded->score);
        $this->assertEquals(1, $graded->correct_answers);
        $this->assertEquals(1, $graded->incorrect_answers);
    }
}
