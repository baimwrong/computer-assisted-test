<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $candidate;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'      => 'System Administrator',
            'email'     => 'admin@test.local',
            'role'      => 'admin',
            'password'  => bcrypt('admin1234'),
            'is_active' => true,
        ]);

        $this->candidate = User::create([
            'name'       => 'Jane Doe',
            'email'      => 'jane@test.local',
            'student_id' => 'STU-999',
            'role'       => 'candidate',
            'password'   => bcrypt('secret123'),
            'is_active'  => true,
        ]);

        $this->category = Category::create([
            'name' => 'Physics',
            'code' => 'PHY-101',
        ]);
    }

    public function test_category_cannot_be_deleted_when_exams_are_attached(): void
    {
        Exam::create([
            'category_id'        => $this->category->id,
            'title'              => 'Mechanics Final',
            'code'               => 'EXAM-PHY',
            'duration_minutes'   => 90,
            'pass_percentage'    => 50.00,
            'total_marks'        => 100.00,
            'negative_marking'   => false,
            'negative_mark_rate' => 0.00,
            'status'             => 'published',
            'is_active'          => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $this->category));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
        ]);
    }

    public function test_category_can_be_deleted_when_no_exams_exist(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $this->category));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', [
            'id' => $this->category->id,
        ]);
    }

    public function test_admin_can_update_candidate_details_without_changing_password(): void
    {
        $oldPasswordHash = $this->candidate->password;

        $response = $this->actingAs($this->admin)
            ->put(route('admin.candidates.update', $this->candidate), [
                'name'                  => 'Jane Updated',
                'email'                 => 'jane_updated@test.local',
                'student_id'            => 'STU-999-NEW',
                'is_active'             => '1',
                'password'              => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('admin.candidates.show', $this->candidate));
        $response->assertSessionHas('success');

        $this->candidate->refresh();
        $this->assertEquals('Jane Updated', $this->candidate->name);
        $this->assertEquals('jane_updated@test.local', $this->candidate->email);
        $this->assertEquals('STU-999-NEW', $this->candidate->student_id);
        $this->assertEquals($oldPasswordHash, $this->candidate->password);
    }

    public function test_admin_can_reset_candidate_password(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.candidates.update', $this->candidate), [
                'name'                  => 'Jane Doe',
                'email'                 => 'jane@test.local',
                'student_id'            => 'STU-999',
                'is_active'             => '1',
                'password'              => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

        $response->assertRedirect(route('admin.candidates.show', $this->candidate));
        $response->assertSessionHas('success');

        $this->candidate->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->candidate->password));
    }

    public function test_candidate_cannot_access_admin_candidate_management(): void
    {
        $response = $this->actingAs($this->candidate)
            ->get(route('admin.candidates.edit', $this->candidate));

        $response->assertStatus(403);
    }
}
