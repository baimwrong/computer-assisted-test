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
                        @php
                            $connStatus = $att->connectionStatus();
                        @endphp
                        <tr id="session-row-{{ $att->id }}" data-attempt-id="{{ $att->id }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold">{{ $att->user->name }}</span>
                                    @if ($connStatus === 'online')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Active heartbeat received"><i class="bi bi-circle-fill fs-8 me-1 text-success"></i>Live</span>
                                    @elseif ($connStatus === 'lagging')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Heartbeat delayed (30-75s)"><i class="bi bi-circle-fill fs-8 me-1 text-warning"></i>Lagging</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="No heartbeat received (>75s)"><i class="bi bi-circle-fill fs-8 me-1 text-danger"></i>No Ping</span>
                                    @endif
                                </div>
                                <small class="text-muted font-monospace">{{ $att->user->student_id ?? $att->user->email }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $att->exam->title }}</span>
                            </td>
                            <td style="min-width: 150px;">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span class="progress-text">{{ $att->answered_questions }} / {{ $att->total_questions }} Qs</span>
                                    <span class="progress-pct">{{ $att->total_questions > 0 ? round(($att->answered_questions / $att->total_questions) * 100) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $att->total_questions > 0 ? ($att->answered_questions / $att->total_questions) * 100 : 0 }}%"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark font-monospace fs-7 timer-badge" data-seconds="{{ max(0, $att->remainingSeconds()) }}">
                                    <i class="bi bi-stopwatch me-1"></i> {{ gmdate('H:i:s', max(0, $att->remainingSeconds())) }}
                                </span>
                            </td>
                            <td class="violation-cell">
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
                                <small class="font-monospace text-muted ip-text">{{ $att->ip_address ?? '127.0.0.1' }}</small>
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

                                    <!-- Reset Violations -->
                                    <form action="{{ route('proctor.monitor.reset-violations', $att) }}" method="POST" class="d-inline" onsubmit="return confirm('Reset violations to 0 for {{ $att->user->name }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-info" title="Reset Violations to 0">
                                            <i class="bi bi-shield-slash"></i>
                                        </button>
                                    </form>

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
    const csrfToken = "{{ csrf_token() }}";
    const baseActionUrl = "{{ url('/proctor/monitor') }}";

    function formatTime(seconds) {
        if (seconds <= 0) return '00:00:00';
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return [
            String(h).padStart(2, '0'),
            String(m).padStart(2, '0'),
            String(s).padStart(2, '0')
        ].join(':');
    }

    function renderConnectionBadge(status) {
        if (status === 'online') {
            return `<span class="badge bg-success-subtle text-success border border-success-subtle" title="Active heartbeat received"><i class="bi bi-circle-fill fs-8 me-1 text-success"></i>Live</span>`;
        } else if (status === 'lagging') {
            return `<span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="Heartbeat delayed (30-75s)"><i class="bi bi-circle-fill fs-8 me-1 text-warning"></i>Lagging</span>`;
        } else {
            return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="No heartbeat received (>75s)"><i class="bi bi-circle-fill fs-8 me-1 text-danger"></i>No Ping</span>`;
        }
    }

    function renderViolationBadge(count) {
        if (count === 0) {
            return `<span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-shield-check me-1"></i> 0 Clean
            </span>`;
        } else if (count === 1) {
            return `<span class="badge bg-warning text-dark">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> 1 Warning
            </span>`;
        } else {
            return `<span class="badge bg-danger text-white">
                <i class="bi bi-exclamation-octagon-fill me-1"></i> ${count} Critical
            </span>`;
        }
    }

    function buildRowHtml(att) {
        const pct = att.total_questions > 0 ? Math.round((att.answered_questions / att.total_questions) * 100) : 0;
        return `
            <tr id="session-row-${att.id}" data-attempt-id="${att.id}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold">${att.candidate_name}</span>
                        <span class="conn-badge-slot">${renderConnectionBadge(att.connection_status)}</span>
                    </div>
                    <small class="text-muted font-monospace">${att.student_id}</small>
                </td>
                <td>
                    <span class="fw-semibold">${att.exam_title}</span>
                </td>
                <td style="min-width: 150px;">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span class="progress-text">${att.answered_questions} / ${att.total_questions} Qs</span>
                        <span class="progress-pct">${pct}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: ${pct}%"></div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-dark font-monospace fs-7 timer-badge" data-seconds="${att.remaining_seconds}">
                        <i class="bi bi-stopwatch me-1"></i> ${formatTime(att.remaining_seconds)}
                    </span>
                </td>
                <td class="violation-cell">
                    ${renderViolationBadge(att.violation_count)}
                </td>
                <td>
                    <small class="font-monospace text-muted ip-text">${att.ip_address || '127.0.0.1'}</small>
                </td>
                <td>
                    <small class="text-muted">${att.started_at}</small>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExtraTime-${att.id}" title="Grant Extra Time">
                            <i class="bi bi-plus-circle"></i> +Time
                        </button>
                        <form action="${baseActionUrl}/${att.id}/reset-violations" method="POST" class="d-inline" onsubmit="return confirm('Reset violations to 0 for ${att.candidate_name}?');">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn btn-outline-info" title="Reset Violations to 0">
                                <i class="bi bi-shield-slash"></i>
                            </button>
                        </form>
                        <form action="${baseActionUrl}/${att.id}/force-submit" method="POST" class="d-inline" onsubmit="return confirm('Force submit candidate test?');">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn btn-outline-warning" title="Force Submit">
                                <i class="bi bi-check2-all"></i>
                            </button>
                        </form>
                        <form action="${baseActionUrl}/${att.id}/terminate" method="POST" class="d-inline" onsubmit="return confirm('Immediately terminate candidate session for violation?');">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn btn-outline-danger" title="Terminate for Breach">
                                <i class="bi bi-x-octagon"></i>
                            </button>
                        </form>
                    </div>

                    <div class="modal fade" id="modalExtraTime-${att.id}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                                <form action="${baseActionUrl}/${att.id}/extra-time" method="POST">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Grant Extra Time - ${att.candidate_name}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted small">Select extra minutes to append to candidate countdown:</p>
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
        `;
    }

    function syncSessionsTable(attempts) {
        const countBadge = document.getElementById('active-count');
        if (countBadge) countBadge.textContent = attempts.length;

        const tbody = document.getElementById('sessions-table-body');
        if (!tbody) return;

        if (attempts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-person-slash fs-1 d-block mb-2"></i>
                        No candidates are currently in an active testing session.
                    </td>
                </tr>
            `;
            return;
        }

        // Remove empty placeholder row if present
        const emptyRow = tbody.querySelector('.bi-person-slash');
        if (emptyRow) tbody.innerHTML = '';

        const currentIds = new Set(attempts.map(a => String(a.id)));

        // Remove rows that concluded
        tbody.querySelectorAll('tr[data-attempt-id]').forEach(row => {
            const rowId = row.dataset.attemptId;
            if (!currentIds.has(rowId)) {
                row.remove();
            }
        });

        attempts.forEach(att => {
            const existingRow = document.getElementById(`session-row-${att.id}`);
            const pct = att.total_questions > 0 ? Math.round((att.answered_questions / att.total_questions) * 100) : 0;

            if (existingRow) {
                // Update connection status
                const connSlot = existingRow.querySelector('.conn-badge-slot');
                if (connSlot) connSlot.innerHTML = renderConnectionBadge(att.connection_status);

                // Update progress
                const pText = existingRow.querySelector('.progress-text');
                const pPct = existingRow.querySelector('.progress-pct');
                const pBar = existingRow.querySelector('.progress-bar');
                if (pText) pText.textContent = `${att.answered_questions} / ${att.total_questions} Qs`;
                if (pPct) pPct.textContent = `${pct}%`;
                if (pBar) pBar.style.width = `${pct}%`;

                // Update timer
                const tBadge = existingRow.querySelector('.timer-badge');
                if (tBadge) {
                    tBadge.dataset.seconds = att.remaining_seconds;
                    tBadge.innerHTML = `<i class="bi bi-stopwatch me-1"></i> ${formatTime(att.remaining_seconds)}`;
                }

                // Update violations
                const vCell = existingRow.querySelector('.violation-cell');
                if (vCell) vCell.innerHTML = renderViolationBadge(att.violation_count);

                // Update IP
                const ipText = existingRow.querySelector('.ip-text');
                if (ipText) ipText.textContent = att.ip_address || '127.0.0.1';
            } else {
                tbody.insertAdjacentHTML('beforeend', buildRowHtml(att));
            }
        });
    }

    // Live Polling every 10 seconds
    setInterval(() => {
        const examId = "{{ $selectedExamId ?? '' }}";
        const url = "{{ route('proctor.monitor.api.sessions') }}" + (examId ? `?exam_id=${examId}` : '');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (data.attempts) {
                    syncSessionsTable(data.attempts);
                }
            })
            .catch(err => console.warn('Polling error:', err));
    }, 10000);

    // Smooth real-time countdown decrement every second for active timers
    setInterval(() => {
        document.querySelectorAll('.timer-badge').forEach(badge => {
            let secs = parseInt(badge.dataset.seconds, 10);
            if (!isNaN(secs) && secs > 0) {
                secs--;
                badge.dataset.seconds = secs;
                badge.innerHTML = `<i class="bi bi-stopwatch me-1"></i> ${formatTime(secs)}`;
            }
        });
    }, 1000);
</script>
@endpush
