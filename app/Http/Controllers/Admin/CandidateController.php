<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'candidate')
            ->withCount([
                'examAttempts as total_attempts',
                'examAttempts as passed_attempts' => fn($q) => $q->where('is_passed', true),
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('student_id', 'ilike', "%{$search}%");
            });
        }

        $candidates = $query->latest()->paginate(15);

        return view('admin.candidates.index', compact('candidates'));
    }

    public function create()
    {
        return view('admin.candidates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'student_id' => ['nullable', 'string', 'max:50', 'unique:users,student_id'],
            'password'   => ['required', 'string', 'min:6'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'role'       => ['required', 'in:candidate,proctor,admin'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        $user = User::create($validated);
        AuditLogService::log('create_user', 'User', $user->id, ['name' => $user->name, 'role' => $user->role]);

        return redirect()->route('admin.candidates.index')->with('success', "Account for '{$user->name}' created.");
    }

    public function show(User $candidate)
    {
        $candidate->load(['examAttempts.exam', 'violations.attempt.exam']);
        return view('admin.candidates.show', compact('candidate'));
    }

    public function toggleStatus(User $candidate)
    {
        $candidate->is_active = !$candidate->is_active;
        $candidate->save();

        AuditLogService::log('toggle_user_status', 'User', $candidate->id, ['is_active' => $candidate->is_active]);

        return back()->with('success', "Candidate status updated to " . ($candidate->is_active ? 'Active' : 'Inactive') . ".");
    }

    public function destroy(User $candidate)
    {
        $name = $candidate->name;
        $candidate->delete();

        AuditLogService::log('delete_user', 'User', null, ['name' => $name]);

        return back()->with('success', "Candidate '{$name}' deleted.");
    }
}
