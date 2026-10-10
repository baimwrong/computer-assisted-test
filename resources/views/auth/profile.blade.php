@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h3 class="fw-bold mb-4"><i class="bi bi-person-circle text-primary me-2"></i> User Profile & Security</h3>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-person-lines-fill me-1"></i> Personal Information
                        </div>
                        <div class="card-body">
                            <form action="{{ route('profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly>
                                    <small class="text-muted">Email is locked to prevent session disruption.</small>
                                </div>

                                @if ($user->student_id)
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Registration / Student ID</label>
                                        <input type="text" class="form-control bg-light" value="{{ $user->student_id }}" readonly>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Contact</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                                </div>

                                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-shield-lock me-1"></i> Change Password
                        </div>
                        <div class="card-body">
                            <form action="{{ route('profile.password') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Confirm New Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-warning">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
