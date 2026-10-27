<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ExamAttempt;
use App\Services\ExamGradingService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduled command to auto-submit overdue exam attempts.
 */
Artisan::command('cat:cleanup-expired-attempts', function (ExamGradingService $gradingService) {
    $this->info('Scanning for expired in-progress exam sessions...');
    
    $expiredAttempts = ExamAttempt::where('status', 'in_progress')
        ->where('expires_at', '<=', Carbon::now())
        ->get();

    $count = 0;
    foreach ($expiredAttempts as $attempt) {
        $gradingService->gradeAttempt($attempt, 'timed_out');
        \App\Services\AuditLogService::log('exam_auto_submitted_timeout', 'ExamAttempt', $attempt->id, [
            'candidate_id' => $attempt->user_id,
            'exam_id'      => $attempt->exam_id,
        ], $attempt->user_id);
        $count++;
    }

    $this->info("Successfully finalized {$count} expired exam sessions.");
})->purpose('Auto-submit expired exam attempts in the background');

Schedule::command('cat:cleanup-expired-attempts')->everyMinute()->withoutOverlapping();
