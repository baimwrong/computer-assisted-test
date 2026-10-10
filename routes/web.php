<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Proctor\LiveMonitorController;
use App\Http\Controllers\Candidate\CandidateDashboardController;
use App\Http\Controllers\Candidate\ExamTakingController;

// Public & Authentication Routes
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->role === 'admin') return redirect()->route('admin.dashboard');
        if ($user->role === 'proctor') return redirect()->route('proctor.monitor');
        return redirect()->route('candidate.dashboard');
    }
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // ==========================================
    // ADMIN ROUTES (Full management & bulk import)
    // ==========================================
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Exams
        Route::resource('exams', ExamController::class);
        Route::post('exams/{exam}/toggle-status', [ExamController::class, 'toggleStatus'])->name('exams.toggle-status');
        Route::get('exams/{exam}/export-results', [ExamController::class, 'exportResults'])->name('exams.export-results');

        // Questions Bank
        Route::resource('questions', QuestionController::class);

        // Categories
        Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);

        // Candidates / Users
        Route::get('candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::get('candidates/create', [CandidateController::class, 'create'])->name('candidates.create');
        Route::post('candidates', [CandidateController::class, 'store'])->name('candidates.store');
        Route::get('candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
        Route::post('candidates/{candidate}/toggle-status', [CandidateController::class, 'toggleStatus'])->name('candidates.toggle-status');
        Route::delete('candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');

        // Bulk Import Center
        Route::get('bulk-import', [BulkImportController::class, 'index'])->name('bulk-import.index');
        Route::post('bulk-import/questions', [BulkImportController::class, 'importQuestions'])->name('bulk-import.questions');
        Route::post('bulk-import/candidates', [BulkImportController::class, 'importCandidates'])->name('bulk-import.candidates');
        Route::get('bulk-import/template/questions', [BulkImportController::class, 'downloadQuestionTemplate'])->name('bulk-import.template.questions');
        Route::get('bulk-import/template/candidates', [BulkImportController::class, 'downloadCandidateTemplate'])->name('bulk-import.template.candidates');

        // Analytics & Psychometrics
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('analytics/exam/{exam}', [AnalyticsController::class, 'exam'])->name('analytics.exam');

        // Audit Trail
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // ==========================================
    // PROCTOR / EXAMINER ROUTES (Live monitoring)
    // ==========================================
    Route::prefix('proctor')->name('proctor.')->middleware('role:proctor,admin')->group(function () {
        Route::get('/monitor', [LiveMonitorController::class, 'index'])->name('monitor');
        Route::get('/monitor/api/sessions', [LiveMonitorController::class, 'apiSessions'])->name('monitor.api.sessions');
        Route::post('/monitor/{attempt}/extra-time', [LiveMonitorController::class, 'grantExtraTime'])->name('monitor.extra-time');
        Route::post('/monitor/{attempt}/force-submit', [LiveMonitorController::class, 'forceSubmit'])->name('monitor.force-submit');
        Route::post('/monitor/{attempt}/terminate', [LiveMonitorController::class, 'terminate'])->name('monitor.terminate');
    });

    // ==========================================
    // CANDIDATE ROUTES (Testing engine & results)
    // ==========================================
    Route::prefix('candidate')->name('candidate.')->middleware('role:candidate')->group(function () {
        Route::get('/', [CandidateDashboardController::class, 'index'])->name('dashboard');
        Route::get('/history', [CandidateDashboardController::class, 'history'])->name('history');

        // Exam Flow
        Route::get('/exam/{exam}/lobby', [ExamTakingController::class, 'lobby'])->name('exam.lobby');
        Route::post('/exam/{exam}/start', [ExamTakingController::class, 'start'])->name('exam.start');
        Route::get('/exam/{attempt}/take', [ExamTakingController::class, 'take'])->name('exam.take');
        Route::post('/exam/{attempt}/autosave', [ExamTakingController::class, 'autosave'])->name('exam.autosave');
        Route::post('/exam/{attempt}/violation', [ExamTakingController::class, 'logViolation'])->name('exam.violation');
        Route::post('/exam/{attempt}/submit', [ExamTakingController::class, 'submit'])->name('exam.submit');
        Route::get('/exam/{attempt}/result', [ExamTakingController::class, 'result'])->name('exam.result');
        Route::get('/exam/{attempt}/review', [ExamTakingController::class, 'review'])->name('exam.review');
    });
});
