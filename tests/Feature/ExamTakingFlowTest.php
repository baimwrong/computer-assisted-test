<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamTakingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $candidate;
    protected User $otherCandidate;
    protected Category $category;
    protected Exam $exam;
    protected Question $question1;
    protected Question $question2;
    protected QuestionOption $q1OptA;
    protected QuestionOption $q1OptB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->candidate = User::create([
            'name'       => 'Jane Candidate',
            'email'      => 'jane@test.local',
            'student_id' => 'CAND-001',
            'role'       => 'candidate',
            'password'   => bcrypt('secret123'),
            'is_active'  => true,
        ]);

        $this->otherCandidate = User::create([
            'name'       => 'Bob Candidate',
            'email'      => 'bob@test.local',
            'student_id' => 'CAND-002',
            'role'       => 'candidate',
            'password'   => bcrypt('secret123'),
            'is_active'  => true,
        ]);

        $this->category = Category::create([
            'name' => 'Computer Science',
            'code' => 'CS-101',
        ]);

        $this->exam = Exam::create([
            'category_id'        => $this->category->id,
            'title'              => 'Algorithms Assessment',
            'code'               => 'EXAM-CS-ALG',
            'duration_minutes'   => 45,
            'pass_percentage'    => 60.00,
            'total_marks'        => 20.00,
            'negative_marking'   => false,
            'negative_mark_rate' => 0.00,
            'status'             => 'published',
            'is_active'          => true,
            'max_attempts'       => 1,
            'shuffle_questions'  => false,
            'shuffle_options'    => false,
            'allow_review'       => true,
        ]);

        $this->question1 = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'What is the time complexity of binary search?',
            'question_type' => 'single_choice',
            'points'        => 10.00,
            'order_index'   => 1,
            'is_active'     => true,
        ]);

        $this->q1OptA = QuestionOption::create([
            'question_id' => $this->question1->id,
            'option_text' => 'O(log n)',
            'is_correct'  => true,
            'order_index' => 1,
        ]);

        $this->q1OptB = QuestionOption::create([
            'question_id' => $this->question1->id,
            'option_text' => 'O(n)',
            'is_correct'  => false,
            'order_index' => 2,
        ]);

        $this->question2 = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'A stack operates in LIFO order.',
            'question_type' => 'true_false',
            'points'        => 10.00,
            'order_index'   => 2,
            'is_active'     => true,
        ]);

        QuestionOption::create([
            'question_id' => $this->question2->id,
            'option_text' => 'True',
            'is_correct'  => true,
            'order_index' => 1,
        ]);

        QuestionOption::create([
            'question_id' => $this->question2->id,
            'option_text' => 'False',
            'is_correct'  => false,
            'order_index' => 2,
        ]);
    }

    public function test_candidate_can_access_exam_lobby(): void
    {
        $response = $this->actingAs($this->candidate)
            ->get(route('candidate.exam.lobby', $this->exam));

        $response->assertStatus(200);
        $response->assertSee('Algorithms Assessment');
        $response->assertSee('45');
    }

    public function test_candidate_can_start_exam_attempt(): void
    {
        $response = $this->actingAs($this->candidate)
            ->post(route('candidate.exam.start', $this->exam));

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'user_id' => $this->candidate->id,
            'status'  => 'in_progress',
        ]);

        $attempt = ExamAttempt::where('user_id', $this->candidate->id)->first();
        $this->assertNotNull($attempt);
        $response->assertRedirect(route('candidate.exam.take', $attempt));
    }

    public function test_candidate_cannot_start_attempt_beyond_max_attempts(): void
    {
        // Create an existing completed attempt
        ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'completed',
            'started_at'      => Carbon::now()->subMinutes(30),
            'expires_at'      => Carbon::now(),
            'submitted_at'    => Carbon::now(),
            'total_questions' => 2,
        ]);

        $response = $this->actingAs($this->candidate)
            ->post(route('candidate.exam.start', $this->exam));

        $response->assertSessionHas('error');
        $this->assertEquals(1, ExamAttempt::where('user_id', $this->candidate->id)->count());
    }

    public function test_candidate_can_autosave_response(): void
    {
        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(45),
            'total_questions' => 2,
        ]);

        $response = $this->actingAs($this->candidate)
            ->postJson(route('candidate.exam.autosave', $attempt), [
                'question_id'          => $this->question1->id,
                'selected_option_ids'  => [$this->q1OptA->id],
                'is_marked_for_review' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'            => true,
            'answered_questions' => 1,
            'total_questions'    => 2,
        ]);

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id'  => $attempt->id,
            'question_id' => $this->question1->id,
        ]);
    }

    public function test_candidate_heartbeat_returns_live_attempt_status(): void
    {
        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(45),
            'total_questions' => 2,
        ]);

        $response = $this->actingAs($this->candidate)
            ->postJson(route('candidate.exam.heartbeat', $attempt));

        $response->assertStatus(200);
        $response->assertJson([
            'status'          => 'in_progress',
            'total_questions' => 2,
        ]);
        $this->assertArrayHasKey('remaining_seconds', $response->json());
    }

    public function test_candidate_cannot_access_or_autosave_another_candidates_attempt(): void
    {
        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(45),
            'total_questions' => 2,
        ]);

        // Attempt accessed by Bob (otherCandidate)
        $response = $this->actingAs($this->otherCandidate)
            ->postJson(route('candidate.exam.autosave', $attempt), [
                'question_id'         => $this->question1->id,
                'selected_option_ids' => [$this->q1OptA->id],
            ]);

        $response->assertStatus(403);
    }

    public function test_candidate_can_submit_exam_and_view_results(): void
    {
        $attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(45),
            'total_questions' => 2,
        ]);

        AttemptAnswer::create([
            'attempt_id'          => $attempt->id,
            'question_id'         => $this->question1->id,
            'selected_option_ids' => [$this->q1OptA->id],
        ]);

        $response = $this->actingAs($this->candidate)
            ->post(route('candidate.exam.submit', $attempt));

        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertEquals(10.00, (float) $attempt->score);
        $this->assertEquals(50.00, (float) $attempt->percentage);
        $this->assertFalse($attempt->is_passed); // Needed 60%

        $response->assertRedirect(route('candidate.exam.result', $attempt));

        $resultPage = $this->actingAs($this->candidate)
            ->get(route('candidate.exam.result', $attempt));
        $resultPage->assertStatus(200);
        $resultPage->assertSee('50.00%');
    }
}
