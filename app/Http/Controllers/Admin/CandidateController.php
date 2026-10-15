<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCandidateRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $term = '%' . strtolower($request->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where(DB::raw('LOWER(name)'), 'LIKE', $term)
                  ->orWhere(DB::raw('LOWER(email)'), 'LIKE', $term)
                  ->orWhere(DB::raw('LOWER(student_id)'), 'LIKE', $term);
            });
        }

        $candidates = $query->latest()->paginate(15);

        return view('admin.candidates.index', compact('candidates'));
    }

    public function create()
    {
        return view('admin.candidates.create');
    }

    public function store(StoreCandidateRequest $request)
    {
        $validated = $request->validated();
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
        if ($candidate->examAttempts()->exists()) {
            return back()->with('error', "Cannot delete candidate '{$candidate->name}' because exam attempts exist. Deactivate the account instead to preserve test records.");
        }

        $name = $candidate->name;
        $candidate->delete();

        AuditLogService::log('delete_user', 'User', null, ['name' => $name]);

        return back()->with('success', "Candidate '{$name}' deleted.");
    }
}
