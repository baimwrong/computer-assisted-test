<div class="sidebar py-3">
    <div class="px-3 mb-3">
        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Administration</small>
    </div>
    <nav class="nav flex-column">
        <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-fill"></i> Dashboard
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}" href="{{ route('admin.exams.index') }}">
            <i class="bi bi-journal-check"></i> Examination Manager
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}" href="{{ route('admin.questions.index') }}">
            <i class="bi bi-collection-fill"></i> Question Bank
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
            <i class="bi bi-tags-fill"></i> Categories / Subjects
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.candidates.*') ? 'active' : '' }}" href="{{ route('admin.candidates.index') }}">
            <i class="bi bi-people-fill"></i> Candidate Management
        </a>

        <div class="px-3 mt-3 mb-2">
            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.05em;">Tools & Supervision</small>
        </div>
        <a class="sidebar-link {{ request()->routeIs('admin.bulk-import.*') ? 'active' : '' }}" href="{{ route('admin.bulk-import.index') }}">
            <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i> Bulk Data Import
        </a>
        <a class="sidebar-link {{ request()->routeIs('proctor.monitor*') ? 'active' : '' }}" href="{{ route('proctor.monitor') }}">
            <i class="bi bi-broadcast text-danger"></i> Live Proctor Center
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">
            <i class="bi bi-pie-chart-fill text-primary"></i> Assessment Analytics
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
            <i class="bi bi-shield-check text-secondary"></i> Security Audit Logs
        </a>
    </nav>
</div>
