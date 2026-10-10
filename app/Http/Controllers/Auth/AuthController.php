<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Allow login by email or student_id
        $user = User::where('email', $credentials['email'])
            ->orWhere('student_id', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        if (!$user->is_active) {
            return back()->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditLogService::log('user_login', 'User', $user->id, [
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return $this->redirectBasedOnRole($user);
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userId) {
            AuditLogService::log('user_logout', 'User', $userId);
        }

        return redirect()->route('login')->with('success', 'You have been successfully logged out.');
    }

    public function showProfile()
    {
        return view('auth.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);
        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    protected function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'admin'     => redirect()->route('admin.dashboard'),
            'proctor'   => redirect()->route('proctor.monitor'),
            'candidate' => redirect()->route('candidate.dashboard'),
            default     => redirect('/'),
        };
    }
}
