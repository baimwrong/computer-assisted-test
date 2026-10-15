<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamAttempt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LiveMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected User $proctor;
    protected User $candidate;
    protected Category $category;
    protected Exam $exam;
    protected ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proctor = User::create([
            'name'      => 'Chief Proctor',
            'email'     => 'proctor@test.local',
            'role'      => 'proctor',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->candidate = User::create([
            'name'       => 'Test Candidate',
            'email'      => 'candidate@test.local',
            'student_id' => 'STU-100',
            'role'       => 'candidate',
            'password'   => bcrypt('password'),
            'is_active'  => true,
        ]);

        $this->category = Category::create([
            'name' => 'Mathematics',
            'code' => 'MATH-01',
        ]);

        $this->exam = Exam::create([
            'category_id'        => $this->category->id,
            'title'              => 'Calculus I Midterm',
            'code'               => 'EXAM-CALC',
            'duration_minutes'   => 60,
            'pass_percentage'    => 50.00,
            'total_marks'        => 10.00,
            'negative_marking'   => false,
            'negative_mark_rate' => 0.00,
            'status'             => 'published',
            'is_active'          => true,
            'max_violations'     => 3,
        ]);

        $question = Question::create([
            'exam_id'       => $this->exam->id,
            'question_text' => 'Derivative of x^2?',
            'question_type' => 'single_choice',
            'points'        => 10.00,
            'is_active'     => true,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'option_text' => '2x',
            'is_correct'  => true,
            'order_index' => 1,
        ]);

        $this->attempt = ExamAttempt::create([
            'exam_id'         => $this->exam->id,
            'user_id'         => $this->candidate->id,
            'attempt_number'  => 1,
            'status'          => 'in_progress',
            'started_at'      => Carbon::now(),
            'expires_at'      => Carbon::now()->addMinutes(60),
            'total_questions' => 1,
        ]);
    }

    public function test_proctor_can_access_live_monitor(): void
    {
        $response = $this->actingAs($this->proctor)
            ->get(route('proctor.monitor'));

        $response->assertStatus(200);
        $response->assertSee('Calculus I Midterm');
    }

    public function test_candidate_is_forbidden_from_proctor_monitor(): void
    {
        $response = $this->actingAs($this->candidate)
            ->get(route('proctor.monitor'));

        $response->assertStatus(403);
    }

    public function test_api_sessions_returns_active_attempts_json(): void
    {
        $response = $this->actingAs($this->proctor)
            ->getJson(route('proctor.monitor.api.sessions'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'attempts' => [
                '*' => [
                    'id',
                    'candidate_name',
                    'exam_title',
                    'status',
                    'violation_count',
                    'remaining_seconds',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'candidate_name' => 'Test Candidate',
        ]);
    }

    public function test_proctor_can_grant_extra_time_to_candidate(): void
    {
        $initialExpiry = $this->attempt->expires_at;

        $response = $this->actingAs($this->proctor)
            ->post(route('proctor.monitor.extra-time', $this->attempt), [
                'minutes' => 15,
            ]);

        $response->assertSessionHas('success');
        $this->attempt->refresh();
        $this->assertTrue($this->attempt->expires_at->gt($initialExpiry));
        $this->assertEquals(15, $initialExpiry->diffInMinutes($this->attempt->expires_at));
    }

    public function test_proctor_can_force_submit_in_progress_attempt(): void
    {
        $response = $this->actingAs($this->proctor)
            ->post(route('proctor.monitor.force-submit', $this->attempt));

        $response->assertSessionHas('success');
        $this->attempt->refresh();
        $this->assertEquals('completed', $this->attempt->status);
        $this->assertNotNull($this->attempt->submitted_at);
    }

    public function test_proctor_can_terminate_attempt_on_violation(): void
    {
        $response = $this->actingAs($this->proctor)
            ->post(route('proctor.monitor.terminate', $this->attempt));

        $response->assertSessionHas('success');
        $this->attempt->refresh();
        $this->assertEquals('terminated_violation', $this->attempt->status);
        $this->assertNotNull($this->attempt->submitted_at);
    }
}
