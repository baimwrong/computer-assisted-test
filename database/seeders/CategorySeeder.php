<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Database & PostgreSQL Engineering',
                'code'        => 'DB-PGSQL',
                'description' => 'Relational database architecture, PostgreSQL 18 optimizations, indexing, ACID transactions, and query tuning.',
            ],
            [
                'name'        => 'Software Engineering & Algorithms',
                'code'        => 'TECH-SWE',
                'description' => 'System design, OOP principles, data structures, algorithms, and web application architecture.',
            ],
            [
                'name'        => 'General Aptitude & Logical Reasoning',
                'code'        => 'GEN-APT',
                'description' => 'Quantitative analysis, critical thinking, deductive logic, and pattern recognition.',
            ],
            [
                'name'        => 'Cybersecurity & Network Protocols',
                'code'        => 'SEC-NET',
                'description' => 'Authentication, encryption, HTTP/TLS protocols, security headers, and anti-tamper mechanisms.',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
