<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'user_id',
        'attempt_number',
        'status', // in_progress, completed, timed_out, terminated_violation
        'last_seen_at',
        'started_at',
        'submitted_at',
        'expires_at',
        'total_questions',
        'answered_questions',
        'correct_answers',
        'incorrect_answers',
        'score',
        'percentage',
        'is_passed',
        'violation_count',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'last_seen_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expires_at' => 'datetime',
            'total_questions' => 'integer',
            'answered_questions' => 'integer',
            'correct_answers' => 'integer',
            'incorrect_answers' => 'integer',
            'score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_passed' => 'boolean',
            'violation_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class, 'attempt_id');
    }

    public function remainingSeconds(): int
    {
        if ($this->status !== 'in_progress') {
            return 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->expires_at)) {
            return 0;
        }

        return (int) $now->diffInSeconds($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->remainingSeconds() <= 0;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'timed_out', 'terminated_violation'], true);
    }

    public function connectionStatus(): string
    {
        if ($this->status !== 'in_progress') {
            return 'concluded';
        }

        $referenceTime = $this->last_seen_at ?? $this->started_at;
        if (!$referenceTime) {
            return 'offline';
        }

        $diffSeconds = abs(Carbon::now()->diffInSeconds($referenceTime));
        if ($diffSeconds <= 35) {
            return 'online';
        } elseif ($diffSeconds <= 75) {
            return 'lagging';
        }

        return 'offline';
    }

    public function timeSpentFormatted(): string
    {
        if (!$this->started_at) {
            return '0m';
        }

        $end = $this->submitted_at ?? Carbon::now();
        $minutes = (int) $this->started_at->diffInMinutes($end);
        $seconds = (int) $this->started_at->diffInSeconds($end) % 60;

        return "{$minutes}m {$seconds}s";
    }
}
