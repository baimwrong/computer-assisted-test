@extends('layouts.app')

@section('title', 'Bulk Data Import Center')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i> Bulk Data Import Center</h3>
        <p class="text-muted mb-0">High-speed batch ingestion of question banks and candidate directories via CSV.</p>
    </div>
</div>

<!-- Import Results / Error Report Banner if available -->
@if (session('import_result'))
    @php
        $res = session('import_result');
    @endphp
    <div class="card border-0 shadow-sm rounded-3 mb-4 {{ count($res['errors']) > 0 ? 'border-start border-warning border-4' : 'border-start border-success border-4' }}">
        <div class="card-body">
            <h5 class="fw-bold mb-2 d-flex align-items-center gap-2">
                @if (count($res['errors']) === 0)
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                    <span>Bulk Import Completed Successfully!</span>
                @else
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
                    <span>Bulk Import Completed with Some Warnings/Errors</span>
                @endif
            </h5>
            <div class="d-flex gap-4 my-2">
                <div>
                    <span class="text-muted small d-block">Records Ingested</span>
                    <strong class="fs-4 text-success">{{ $res['imported'] }}</strong>
                </div>
                <div>
                    <span class="text-muted small d-block">Rows Skipped / Failed</span>
                    <strong class="fs-4 text-danger">{{ count($res['errors']) }}</strong>
                </div>
            </div>

            @if (count($res['errors']) > 0)
                <div class="mt-3">
                    <h6 class="fw-bold text-danger mb-2">Row-by-Row Error Log:</h6>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">CSV Row</th>
                                    <th>Error Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($res['errors'] as $err)
                                    <tr>
                                        <td class="fw-bold text-center text-danger">{{ $err['row'] ?? '?' }}</td>
                                        <td>{{ $err['message'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="row g-4">
    <!-- SECTION 1: QUESTION BANK IMPORT -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6">
                    <i class="bi bi-collection-play-fill text-primary me-2"></i> Bulk Import Question Bank
                </span>
                <a href="{{ route('admin.bulk-import.template.questions') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Download Template CSV
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Upload questions in CSV format. Supports Multiple Choice (Single/Multi answer), True/False, and Short Answer questions.
                </p>

                <form action="{{ route('admin.bulk-import.questions') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Target Exam (Optional)</label>
                        <select name="exam_id" class="form-select">
                            <option value="">-- Auto-resolve via CSV `exam_code` or Global Pool --</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                                    {{ $exam->title }} ({{ $exam->code }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">If blank, questions will be linked to the exam matching the `exam_code` column.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Subject / Category (Optional)</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Auto-resolve via CSV `category_code` --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv, text/csv, text/plain" required>
                        <small class="text-muted">Maximum file size: 10MB. Standard comma-delimited UTF-8.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload & Process Questions
                    </button>
                </form>

                <hr class="my-4">

                <!-- Format Guide -->
                <div class="bg-light p-3 rounded-3 small">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> CSV Column Specification:</h6>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><code>exam_code</code>: Unique code of exam (e.g., <code>EXAM-2026-CS01</code>)</li>
                        <li><code>category_code</code>: Category code (e.g., <code>TECH-CS</code>)</li>
                        <li><code>question_text</code>: The full question prompt</li>
                        <li><code>question_type</code>: <code>single_choice</code>, <code>multiple_choice</code>, <code>true_false</code>, <code>short_answer</code></li>
                        <li><code>points</code>: Numeric weight (e.g. <code>2.0</code>)</li>
                        <li><code>negative_points</code>: Deduction for wrong answer (e.g. <code>0.5</code>)</li>
                        <li><code>difficulty</code>: <code>easy</code>, <code>medium</code>, or <code>hard</code></li>
                        <li><code>option_a</code>, <code>option_b</code>, <code>option_c</code>, <code>option_d</code>: Choices for MCQs</li>
                        <li><code>correct_answer</code>: <code>A</code>, <code>A,B</code> (for multi-choice), <code>True</code>/<code>False</code>, or text</li>
                        <li><code>explanation</code>: Background rationale shown upon review</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: CANDIDATE DIRECTORY IMPORT -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6">
                    <i class="bi bi-people-fill text-success me-2"></i> Bulk Import Candidates / Users
                </span>
                <a href="{{ route('admin.bulk-import.template.candidates') }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i> Download Template CSV
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Batch register students, test candidates, and supervisors. Automatically sets up credentials for offline authentication.
                </p>

                <form action="{{ route('admin.bulk-import.candidates') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Candidates CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv, text/csv, text/plain" required>
                        <small class="text-muted">Upload formatted CSV with candidate credentials.</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                        <i class="bi bi-person-check-fill me-1"></i> Upload & Ingest Candidates
                    </button>
                </form>

                <hr class="my-4">

                <!-- Format Guide -->
                <div class="bg-light p-3 rounded-3 small">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Candidate CSV Column Specification:</h6>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><code>name</code>: Full name of candidate (e.g. <code>Alice Johnson</code>)</li>
                        <li><code>email</code>: Unique email address (e.g. <code>alice@cat-test.local</code>)</li>
                        <li><code>student_id</code>: Unique registration/test number (e.g. <code>REG-2026-001</code>)</li>
                        <li><code>role</code>: <code>candidate</code>, <code>proctor</code>, or <code>admin</code> (defaults to <code>candidate</code>)</li>
                        <li><code>password</code>: Initial password (if blank, defaults to student_id or <code>password123</code>)</li>
                        <li><code>phone</code>: Contact telephone number (optional)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
