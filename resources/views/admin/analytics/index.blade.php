@extends('layouts.app')

@section('title', 'Assessment Analytics & Psychometrics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-graph-up text-primary me-2"></i> Assessment Analytics & Psychometrics</h3>
        <p class="text-muted mb-0">System-wide scoring patterns, pass/fail ratios, and proctoring violation distributions.</p>
    </div>
</div>

<!-- High-level Visual Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-pie-chart me-1 text-primary"></i> Overall Pass / Fail Ratio
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div style="height: 240px; width: 100%;">
                    <canvas id="passFailChart"></canvas>
                </div>
                <div class="d-flex justify-content-around w-100 mt-3 pt-2 border-top text-center">
                    <div>
                        <span class="text-muted small">Passed</span>
                        <h5 class="fw-bold text-success mb-0">{{ $passedCount }}</h5>
                    </div>
                    <div>
                        <span class="text-muted small">Failed</span>
                        <h5 class="fw-bold text-danger mb-0">{{ $failedCount }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-bar-chart me-1 text-primary"></i> Average Scores by Exam
            </div>
            <div class="card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="scoresBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-shield-exclamation me-1 text-danger"></i> Proctor Violations Profile
            </div>
            <div class="card-body">
                <div style="height: 280px; width: 100%;">
                    <canvas id="violationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Exams Deep-Dive Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 fw-bold">
        <i class="bi bi-table me-1"></i> Exam Performance & Item Analysis Selector
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Exam Title</th>
                        <th>Category</th>
                        <th>Questions</th>
                        <th>Total Submissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $exam->title }}</div>
                                <small class="text-muted font-monospace">{{ $exam->code }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $exam->category->name }}</span></td>
                            <td>{{ $exam->questions_count }} Qs</td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary fw-bold">{{ $exam->attempts_count }} attempts</span></td>
                            <td>
                                <a href="{{ route('admin.analytics.exam', $exam) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-cpu me-1"></i> Deep-Dive Item Analysis
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No exams available for analysis.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Offline Local Chart.js Initialization
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Pass / Fail Chart
        const ctxPF = document.getElementById('passFailChart');
        if (ctxPF) {
            new Chart(ctxPF, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [{{ $passedCount }}, {{ $failedCount }}],
                        backgroundColor: ['#15803d', '#b91c1c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // 2. Scores Bar Chart
        const ctxScores = document.getElementById('scoresBarChart');
        if (ctxScores) {
            const scoreLabels = {!! json_encode($examScores->pluck('title')->map(fn($t) => Str::limit($t, 18))) !!};
            const scoreData = {!! json_encode($examScores->pluck('avg_pct')) !!};

            new Chart(ctxScores, {
                type: 'bar',
                data: {
                    labels: scoreLabels.length ? scoreLabels : ['No Data'],
                    datasets: [{
                        label: 'Average Score (%)',
                        data: scoreData.length ? scoreData : [0],
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });
        }

        // 3. Violations Breakdown
        const ctxViolations = document.getElementById('violationsChart');
        if (ctxViolations) {
            const vTypes = {!! json_encode(array_keys($violationsBreakdown)) !!};
            const vCounts = {!! json_encode(array_values($violationsBreakdown)) !!};

            new Chart(ctxViolations, {
                type: 'polarArea',
                data: {
                    labels: vTypes.length ? vTypes.map(t => t.replace('_', ' ').toUpperCase()) : ['None'],
                    datasets: [{
                        data: vCounts.length ? vCounts : [0],
                        backgroundColor: ['#ef4444', '#f59e0b', '#8b5cf6', '#ec4899']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    });
</script>
@endpush
