@extends('layouts.app')

@section('title', 'Security Audit Trail')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i> Security Audit Trail & Event Logs</h3>
        <p class="text-muted mb-0">Immutable records of administrative operations, test starts, and security events.</p>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search action name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Audit Log Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 180px;">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                        <th>Payload</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-muted">{{ $log->created_at->toDateTimeString() }}</td>
                            <td class="fw-bold font-sans">
                                {{ $log->user->name ?? 'System' }}
                                @if ($log->user)
                                    <small class="text-muted d-block font-monospace">({{ $log->user->role }})</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $log->action }}</span>
                            </td>
                            <td>
                                @if ($log->entity_type)
                                    <span>{{ $log->entity_type }} #{{ $log->entity_id }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            <td>
                                @if ($log->payload)
                                    <code class="text-secondary">{{ json_encode($log->payload) }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($logs->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
