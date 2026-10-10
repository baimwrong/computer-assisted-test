<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BulkImportService
{
    /**
     * Import Questions from a CSV file.
     *
     * @param UploadedFile|string $file
     * @param int|null $defaultExamId
     * @param int|null $defaultCategoryId
     * @return array
     */
    public function importQuestions($file, ?int $defaultExamId = null, ?int $defaultCategoryId = null): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success' => false,
                'message' => 'Unable to read uploaded file.',
                'imported' => 0,
                'errors' => ['File read failure.'],
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'message' => 'Failed to open CSV stream.',
                'imported' => 0,
                'errors' => ['Failed to open stream.'],
            ];
        }

        // Detect BOM and read header
        $rawHeader = fgetcsv($handle, 4096, ',');
        if (!$rawHeader) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Empty CSV file.',
                'imported' => 0,
                'errors' => ['File contains no headers or rows.'],
            ];
        }

        // Clean headers (remove BOM, lowercase, trim)
        $headers = array_map(function ($h) {
            $h = preg_replace('/[\x{FEFF}]/u', '', $h);
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $rawHeader);

        $importedCount = 0;
        $errors = [];
        $rowNum = 1;

        while (($data = fgetcsv($handle, 4096, ',')) !== false) {
            $rowNum++;
            
            // Skip empty rows
            if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            if (count($data) !== count($headers)) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => "Column count mismatch (Expected " . count($headers) . ", found " . count($data) . ").",
                ];
                continue;
            }

            $row = array_combine($headers, $data);

            try {
                DB::transaction(function () use ($row, $rowNum, $defaultExamId, $defaultCategoryId, &$importedCount, &$errors) {
                    $questionText = trim($row['question_text'] ?? $row['question'] ?? '');
                    if (empty($questionText)) {
                        throw new \Exception("Question text is required.");
                    }

                    // Resolve Exam
                    $examId = $defaultExamId;
                    if (!empty($row['exam_code'])) {
                        $exam = Exam::where('code', trim($row['exam_code']))->first();
                        if ($exam) {
                            $examId = $exam->id;
                        }
                    }

                    // Resolve Category
                    $categoryId = $defaultCategoryId;
                    if (!empty($row['category_code'])) {
                        $cat = Category::where('code', trim($row['category_code']))->first();
                        if (!$cat) {
                            $cat = Category::create([
                                'name' => ucwords(str_replace('_', ' ', trim($row['category_code']))),
                                'code' => strtoupper(trim($row['category_code'])),
                            ]);
                        }
                        $categoryId = $cat->id;
                    }

                    $type = strtolower(trim($row['question_type'] ?? $row['type'] ?? 'single_choice'));
                    if (!in_array($type, ['single_choice', 'multiple_choice', 'true_false', 'short_answer'], true)) {
                        $type = 'single_choice';
                    }

                    $points = (float) ($row['points'] ?? 1.00);
                    $negativePoints = (float) ($row['negative_points'] ?? 0.00);
                    $difficulty = strtolower(trim($row['difficulty'] ?? 'medium'));
                    if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                        $difficulty = 'medium';
                    }
                    $explanation = trim($row['explanation'] ?? '');

                    $question = Question::create([
                        'exam_id'         => $examId,
                        'category_id'     => $categoryId,
                        'question_text'   => $questionText,
                        'question_type'   => $type,
                        'points'          => max(0.5, $points),
                        'negative_points' => max(0, $negativePoints),
                        'difficulty'      => $difficulty,
                        'explanation'     => $explanation ?: null,
                        'is_active'       => true,
                    ]);

                    // Parse Options
                    $optionsList = [];
                    $correctAnswer = trim($row['correct_answer'] ?? $row['correct_answers'] ?? $row['answer'] ?? '');

                    if ($type === 'true_false') {
                        $isTrueCorrect = in_array(strtolower($correctAnswer), ['true', 't', '1', 'benar'], true);
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => 'True',
                            'is_correct'  => $isTrueCorrect,
                            'order_index' => 1,
                        ]);
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => 'False',
                            'is_correct'  => !$isTrueCorrect,
                            'order_index' => 2,
                        ]);
                    } elseif ($type === 'short_answer') {
                        // Store the correct answers in options for evaluation
                        $answers = array_filter(array_map('trim', explode('|', $correctAnswer)));
                        foreach ($answers as $idx => $ans) {
                            QuestionOption::create([
                                'question_id' => $question->id,
                                'option_text' => $ans,
                                'is_correct'  => true,
                                'order_index' => $idx + 1,
                            ]);
                        }
                    } else {
                        // Choice questions: support option_a, option_b, ... or pipe options
                        $choices = [];
                        if (isset($row['option_a']) || isset($row['option_b'])) {
                            if (!empty($row['option_a'])) $choices['A'] = trim($row['option_a']);
                            if (!empty($row['option_b'])) $choices['B'] = trim($row['option_b']);
                            if (!empty($row['option_c'])) $choices['C'] = trim($row['option_c']);
                            if (!empty($row['option_d'])) $choices['D'] = trim($row['option_d']);
                            if (!empty($row['option_e'])) $choices['E'] = trim($row['option_e']);
                        } elseif (!empty($row['options'])) {
                            // Split by pipe |
                            $rawChoices = explode('|', $row['options']);
                            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                            foreach ($rawChoices as $idx => $rc) {
                                $key = $letters[$idx] ?? chr(65 + $idx);
                                $choices[$key] = trim($rc);
                            }
                        }

                        $correctTokens = array_map('strtoupper', array_map('trim', explode(',', $correctAnswer)));

                        $order = 1;
                        foreach ($choices as $letter => $optText) {
                            $isCorrect = in_array($letter, $correctTokens, true) 
                                      || in_array(strtoupper($optText), $correctTokens, true);

                            QuestionOption::create([
                                'question_id' => $question->id,
                                'option_text' => $optText,
                                'is_correct'  => $isCorrect,
                                'order_index' => $order++,
                            ]);
                        }
                    }

                    $importedCount++;
                });
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        AuditLogService::log('bulk_import_questions', 'Question', null, [
            'imported_count' => $importedCount,
            'error_count' => count($errors),
        ]);

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Import Candidates from a CSV file.
     *
     * @param UploadedFile|string $file
     * @return array
     */
    public function importCandidates($file): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success' => false,
                'message' => 'Unable to read uploaded file.',
                'imported' => 0,
                'errors' => ['File read failure.'],
            ];
        }

        $handle = fopen($filePath, 'r');
        $rawHeader = fgetcsv($handle, 4096, ',');
        if (!$rawHeader) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Empty CSV file.',
                'imported' => 0,
                'errors' => ['File contains no headers.'],
            ];
        }

        $headers = array_map(function ($h) {
            $h = preg_replace('/[\x{FEFF}]/u', '', $h);
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $rawHeader);

        $importedCount = 0;
        $errors = [];
        $rowNum = 1;

        while (($data = fgetcsv($handle, 4096, ',')) !== false) {
            $rowNum++;

            if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            if (count($data) !== count($headers)) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => "Column count mismatch.",
                ];
                continue;
            }

            $row = array_combine($headers, $data);

            try {
                $name = trim($row['name'] ?? $row['full_name'] ?? '');
                $email = strtolower(trim($row['email'] ?? ''));
                $studentId = trim($row['student_id'] ?? $row['candidate_id'] ?? $row['registration_number'] ?? '');
                $role = strtolower(trim($row['role'] ?? 'candidate'));
                if (!in_array($role, ['admin', 'proctor', 'candidate'], true)) {
                    $role = 'candidate';
                }

                $passwordRaw = trim($row['password'] ?? '');
                if (empty($passwordRaw)) {
                    $passwordRaw = $studentId ?: 'password123';
                }

                $phone = trim($row['phone'] ?? '');

                if (empty($name) || empty($email)) {
                    throw new \Exception("Name and Email are mandatory.");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid email format ({$email}).");
                }

                // Create or update user
                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'       => $name,
                        'student_id' => $studentId ?: null,
                        'role'       => $role,
                        'password'   => Hash::make($passwordRaw),
                        'phone'      => $phone ?: null,
                        'is_active'  => true,
                    ]
                );

                $importedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'message' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        AuditLogService::log('bulk_import_candidates', 'User', null, [
            'imported_count' => $importedCount,
            'error_count' => count($errors),
        ]);

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Generate sample CSV template for Questions.
     */
    public function getQuestionTemplateCsv(): string
    {
        $headers = [
            'exam_code',
            'category_code',
            'question_text',
            'question_type',
            'points',
            'negative_points',
            'difficulty',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'explanation',
        ];

        $sampleRows = [
            [
                'EXAM-2026-CS01',
                'TECH-CS',
                'What is the primary key constraint used for in a relational database like PostgreSQL?',
                'single_choice',
                '2.00',
                '0.50',
                'medium',
                'To uniquely identify each record in a table',
                'To allow duplicate NULL records',
                'To create temporary views',
                'To encrypt database connection strings',
                'A',
                'Primary keys enforce uniqueness and prevent duplicate or null records in relational tables.',
            ],
            [
                'EXAM-2026-CS01',
                'TECH-CS',
                'Which of the following are ACID properties in database management? (Select all that apply)',
                'multiple_choice',
                '3.00',
                '0.00',
                'medium',
                'Atomicity',
                'Consistency',
                'Isolation',
                'Durability',
                'A,B,C,D',
                'ACID stands for Atomicity, Consistency, Isolation, and Durability.',
            ],
            [
                'EXAM-2026-CS01',
                'TECH-CS',
                'PostgreSQL 18 supports JSONB data types natively for high-performance schema-less querying.',
                'true_false',
                '1.00',
                '0.00',
                'easy',
                '',
                '',
                '',
                '',
                'True',
                'PostgreSQL provides native JSON and JSONB indexing and query operators.',
            ],
            [
                'EXAM-2026-CS01',
                'TECH-CS',
                'What is the command keyword used in SQL to retrieve records from a database table?',
                'short_answer',
                '2.00',
                '0.00',
                'easy',
                '',
                '',
                '',
                '',
                'SELECT',
                'The SELECT statement is used to query data from database tables.',
            ],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($sampleRows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Generate sample CSV template for Candidates.
     */
    public function getCandidateTemplateCsv(): string
    {
        $headers = ['name', 'email', 'student_id', 'role', 'password', 'phone'];
        $sampleRows = [
            ['Alice Johnson', 'alice.johnson@cat-test.local', 'REG-2026-001', 'candidate', 'Password@123', '+1555123456'],
            ['Bob Smith', 'bob.smith@cat-test.local', 'REG-2026-002', 'candidate', 'Password@123', '+1555234567'],
            ['Charlie Brown', 'charlie.brown@cat-test.local', 'REG-2026-003', 'candidate', 'Password@123', '+1555345678'],
            ['Diana Prince', 'diana.prince@cat-test.local', 'REG-2026-004', 'candidate', 'Password@123', '+1555456789'],
            ['Evan Wright', 'proctor.evan@cat-test.local', 'STAFF-PROCTOR-01', 'proctor', 'Proctor@2026', '+1555567890'],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($sampleRows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}
