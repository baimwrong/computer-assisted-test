@extends('layouts.app')

@section('title', 'Edit Candidate Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i> Edit User Profile - {{ $candidate->name }}</h3>
        <p class="text-muted mb-0">Update account credentials, student ID, role, or reset password.</p>
    </div>
    <a href="{{ route('admin.candidates.show', $candidate) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-person-gear me-1 text-primary"></i> Account Details
            </div>
            <div class="card-body">
                <form action="{{ route('admin.candidates.update', $candidate) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $candidate->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $candidate->email) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student / Registration ID</label>
                            <input type="text" name="student_id" class="form-control" value="{{ old('student_id', $candidate->student_id) }}" placeholder="e.g. CAT-2026-105">
                            <small class="text-muted">Candidates can sign in using this ID instead of email.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Account Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="candidate" {{ old('role', $candidate->role) === 'candidate' ? 'selected' : '' }}>Candidate / Student (Test Taker)</option>
                                <option value="proctor" {{ old('role', $candidate->role) === 'proctor' ? 'selected' : '' }}>Proctor / Supervisor (Live Monitor)</option>
                                <option value="admin" {{ old('role', $candidate->role) === 'admin' ? 'selected' : '' }}>System Administrator</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reset Password</label>
                            <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep existing password">
                            <small class="text-muted">Only fill this if resetting the candidate's password.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Contact Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $candidate->phone) }}" placeholder="+1 555-0199">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $candidate->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Account Active & Permitted to Login</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.candidates.show', $candidate) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Update Candidate Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
