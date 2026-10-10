<nav class="navbar navbar-expand-lg navbar-dark navbar-cat sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            <i class="bi bi-mortarboard-fill fs-4 text-warning"></i>
            <span>CAT Platform <span class="badge bg-light text-dark fs-8 fw-semibold ms-1">v2.0</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @auth
                    @if (Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}" href="{{ route('admin.exams.index') }}">
                                <i class="bi bi-journal-text me-1"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}" href="{{ route('admin.questions.index') }}">
                                <i class="bi bi-question-diamond me-1"></i> Question Bank
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.bulk-import.*') ? 'active' : '' }}" href="{{ route('admin.bulk-import.index') }}">
                                <i class="bi bi-file-earmark-arrow-up me-1"></i> Bulk Import
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">
                                <i class="bi bi-graph-up me-1"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('proctor.monitor') ? 'active' : '' }}" href="{{ route('proctor.monitor') }}">
                                <i class="bi bi-broadcast me-1 text-info"></i> Live Proctor
                            </a>
                        </li>
                    @elseif (Auth::user()->isProctor())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('proctor.monitor') ? 'active' : '' }}" href="{{ route('proctor.monitor') }}">
                                <i class="bi bi-broadcast me-1"></i> Live Proctoring Monitor
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('candidate.dashboard') ? 'active' : '' }}" href="{{ route('candidate.dashboard') }}">
                                <i class="bi bi-house-door me-1"></i> My Tests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('candidate.history') ? 'active' : '' }}" href="{{ route('candidate.history') }}">
                                <i class="bi bi-clock-history me-1"></i> Test History & Scorecards
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span>{{ Auth::user()->name }}</span>
                            <span class="badge bg-warning text-dark text-uppercase fs-8">{{ Auth::user()->role }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <div class="px-3 py-1">
                                    <small class="text-muted d-block">Signed in as</small>
                                    <span class="fw-semibold">{{ Auth::user()->email }}</span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile') }}">
                                    <i class="bi bi-person-gear me-2"></i> Account Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Sign In</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
