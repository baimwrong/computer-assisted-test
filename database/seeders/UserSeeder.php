<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@cat-system.local'],
            [
                'name'       => 'System Administrator',
                'student_id' => 'ADMIN-001',
                'role'       => 'admin',
                'password'   => Hash::make('Admin@2026!'),
                'phone'      => '+1555100100',
                'is_active'  => true,
            ]
        );

        // Proctor / Examiner
        User::updateOrCreate(
            ['email' => 'proctor@cat-system.local'],
            [
                'name'       => 'Chief Proctor / Examiner',
                'student_id' => 'PROCTOR-001',
                'role'       => 'proctor',
                'password'   => Hash::make('Proctor@2026!'),
                'phone'      => '+1555200200',
                'is_active'  => true,
            ]
        );

        // Candidates
        User::updateOrCreate(
            ['email' => 'candidate1@cat-system.local'],
            [
                'name'       => 'John Doe',
                'student_id' => 'CAT-2026-001',
                'role'       => 'candidate',
                'password'   => Hash::make('Candidate@2026!'),
                'phone'      => '+1555300301',
                'is_active'  => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'candidate2@cat-system.local'],
            [
                'name'       => 'Jane Smith',
                'student_id' => 'CAT-2026-002',
                'role'       => 'candidate',
                'password'   => Hash::make('Candidate@2026!'),
                'phone'      => '+1555300302',
                'is_active'  => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'candidate3@cat-system.local'],
            [
                'name'       => 'Alex Turner',
                'student_id' => 'CAT-2026-003',
                'role'       => 'candidate',
                'password'   => Hash::make('Candidate@2026!'),
                'phone'      => '+1555300303',
                'is_active'  => true,
            ]
        );
    }
}
