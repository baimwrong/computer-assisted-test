@extends('layouts.app')

@section('title', 'Item Analysis - ' . $exam->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-cpu-fill text-primary me-2"></i> Psychometrics & Item Analysis</h3>
        <p class="text-muted mb-0">Exam: <strong>{{ $exam->title }}</strong> ({{ $exam->code }})</p>
    </div>
    <a href="{{ route('admin.analytics.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Analytics
    </a>
</div>

<!-- Score Distribution Histogram -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-bar-chart-steps me-1 text-primary"></i> Score Distribution Histogram (N = {{ $totalCompleted }})
            </div>
            <div class="card-body">
                <div style="height: 260px; width: 100%;">
                    <canvas id="histogramChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-info-circle me-1 text-primary"></i> Assessment Psychometrics Guide
            </div>
            <div class="card-body">
                <h6 class="fw-bold">Item Difficulty Index ($P$-Value):</h6>
                <p class="small text-muted mb-3">
                    Calculated as \( P = \frac{\text{Correct Responses}}{\text{Total Responses}} \). Measures the proportion of test takers who answered the question correctly.
                </p>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><span class="badge bg-danger me-2">Difficult</span> \( P < 0.30 \)</span>
                        <span class="text-muted">High difficulty; may need review or rewording.</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><span class="badge bg-success me-2">Optimal</span> \( 0.30 \le P \le 0.80 \)</span>
                        <span class="text-muted">Ideal discrimination power for CAT exams.</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><span class="badge bg-warning text-dark me-2">Easy</span> \( P > 0.80 \)</span>
                        <span class="text-muted">Mastered by vast majority of test takers.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Item Analysis Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 fw-bold">
        <i class="bi bi-list-check me-1"></i> Question Item-by-Item Diagnostics
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Question Prompt</th>
                        <th>Type</th>
                        <th>Total Answered</th>
                        <th>Correct Count</th>
                        <th>Difficulty Index (\(P\))</th>
                        <th>Assessment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $idx => $q)
                        <tr>
                            <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-medium">{{ Str::limit($q['text'], 80) }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $q['type'])) }}</span></td>
                            <td>{{ $q['total_answered'] }}</td>
                            <td><strong class="text-success">{{ $q['correct_count'] }}</strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold">{{ $q['difficulty_index'] }}</span>
                                    <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $q['difficulty_index'] * 100 }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($q['evaluation'] === 'Optimal')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Optimal</span>
                                @elseif ($q['evaluation'] === 'Difficult')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Difficult</span>
                                @elseif ($q['evaluation'] === 'Easy')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Easy</span>
                                @else
                                    <span class="badge bg-light text-muted border">No Data</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No questions found for this exam.</td>
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
    document.addEventListener('DOMContentLoaded', function () {
        const ctxHist = document.getElementById('histogramChart');
        if (ctxHist) {
            new Chart(ctxHist, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(array_keys($buckets)) !!},
                    datasets: [{
                        label: 'Candidate Count',
                        data: {!! json_encode(array_values($buckets)) !!},
                        backgroundColor: '#6366f1',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    });
</script>
@endpush
