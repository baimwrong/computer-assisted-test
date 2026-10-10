@extends('layouts.app')

@section('title', 'Register Candidate')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-person-plus-fill text-primary me-2"></i> Register New User / Candidate</h3>
        <p class="text-muted mb-0">Create candidate or staff accounts for examination participation and proctoring.</p>
    </div>
    <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-card-heading me-1 text-primary"></i> Account Credentials
            </div>
            <div class="card-body">
                <form action="{{ route('admin.candidates.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. John Doe">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="candidate@cat-test.local">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student / Registration ID</label>
                            <input type="text" name="student_id" class="form-control" value="{{ old('student_id') }}" placeholder="e.g. CAT-2026-105">
                            <small class="text-muted">Candidates can sign in using this ID instead of email.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="candidate" {{ old('role') === 'candidate' ? 'selected' : '' }}>Candidate / Student (Test Taker)</option>
                                <option value="proctor" {{ old('role') === 'proctor' ? 'selected' : '' }}>Proctor / Supervisor (Live Monitor)</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>System Administrator</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6" placeholder="••••••••">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Contact Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+1 555-0199">
                        </div>
                    </div>

                    <div class="mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.candidates.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Register Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
