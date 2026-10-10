@extends('layouts.app')

@section('title', 'Live Proctoring Center')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0"><i class="bi bi-broadcast text-danger me-1"></i> Live Proctoring Center</h3>
            <span class="badge bg-danger text-white pulse-badge">
                <i class="bi bi-record-circle-fill me-1"></i> REAL-TIME ACTIVE
            </span>
        </div>
        <p class="text-muted mb-0">Supervise active candidates in real time. Detect tab switching, time remaining, and enforce security policies.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" action="{{ route('proctor.monitor') }}" class="d-flex gap-2">
            <select name="exam_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Active Exams</option>
                @foreach ($exams as $ex)
                    <option value="{{ $ex->id }}" {{ $selectedExamId == $ex->id ? 'selected' : '' }}>{{ $ex->title }}</option>
                @endforeach
            </select>
        </form>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- Active Attempts Monitor Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-people-fill text-primary me-1"></i> Active Test Takers (<span id="active-count">{{ $activeAttempts->count() }}</span>)
        </span>
        <small class="text-muted"><i class="bi bi-clock me-1"></i>Auto-refreshing every 10 seconds</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Candidate</th>
                        <th>Exam</th>
                        <th>Progress (Answered)</th>
                        <th>Time Remaining</th>
                        <th>Violations</th>
                        <th>Workstation IP</th>
                        <th>Started At</th>
                        <th class="text-end">Proctor Actions</th>
                    </tr>
                </thead>
                <tbody id="sessions-table-body">
                    @forelse ($activeAttempts as $att)
                        <tr id="session-row-{{ $att->id }}">
                            <td>
                                <div class="fw-bold">{{ $att->user->name }}</div>
                                <small class="text-muted font-monospace">{{ $att->user->student_id ?? $att->user->email }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $att->exam->title }}</span>
                            </td>
                            <td style="min-width: 150px;">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>{{ $att->answered_questions }} / {{ $att->total_questions }} Qs</span>
                                    <span>{{ $att->total_questions > 0 ? round(($att->answered_questions / $att->total_questions) * 100) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $att->total_questions > 0 ? ($att->answered_questions / $att->total_questions) * 100 : 0 }}%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark font-monospace fs-7">
                                    <i class="bi bi-stopwatch me-1"></i> {{ gmdate('H:i:s', max(0, $att->remainingSeconds())) }}
                                </span>
                            </td>
                            <td>
                                @if ($att->violation_count === 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-shield-check me-1"></i> 0 Clean
                                    </span>
                                @elseif ($att->violation_count === 1)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> 1 Warning
                                    </span>
                                @else
                                    <span class="badge bg-danger text-white">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i> {{ $att->violation_count }} Critical
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small class="font-monospace text-muted">{{ $att->ip_address ?? '127.0.0.1' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $att->started_at->toTimeString() }}</small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Extra Time -->
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtraTime-{{ $att->id }}" title="Grant Extra Time">
                                        <i class="bi bi-plus-circle"></i> +Time
                                    </button>

                                    <!-- Force Submit -->
                                    <form action="{{ route('proctor.monitor.force-submit', $att) }}" method="POST" class="d-inline" onsubmit="return confirm('Force submit candidate test?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-warning" title="Force Submit">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                    </form>

                                    <!-- Terminate -->
                                    <form action="{{ route('proctor.monitor.terminate', $att) }}" method="POST" class="d-inline" onsubmit="return confirm('Immediately terminate candidate session for violation?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger" title="Terminate for Breach">
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Extra Time Modal -->
                                <div class="modal fade" id="modalExtraTime-{{ $att->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="{{ route('proctor.monitor.extra-time', $att) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Grant Extra Time - {{ $att->user->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted small">Select the duration in minutes to append to the candidate's active countdown timer:</p>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Extra Minutes</label>
                                                        <select name="minutes" class="form-select">
                                                            <option value="5">+5 Minutes</option>
                                                            <option value="10" selected>+10 Minutes</option>
                                                            <option value="15">+15 Minutes</option>
                                                            <option value="30">+30 Minutes</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary fw-bold">Add Extra Time</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-person-slash fs-1 d-block mb-2"></i>
                                No candidates are currently in an active testing session.
                            </td>
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
    // Live Polling every 10 seconds
    setInterval(() => {
        const examId = "{{ $selectedExamId ?? '' }}";
        const url = "{{ route('proctor.monitor.api.sessions') }}" + (examId ? `?exam_id=${examId}` : '');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                const countBadge = document.getElementById('active-count');
                if (countBadge) countBadge.textContent = data.attempts.length;
            })
            .catch(err => console.warn('Polling error:', err));
    }, 10000);
</script>
@endpush
