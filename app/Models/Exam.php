<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'code',
        'description',
        'instructions',
        'duration_minutes',
        'pass_percentage',
        'total_marks',
        'shuffle_questions',
        'shuffle_options',
        'show_result_instant',
        'allow_review',
        'negative_marking',
        'negative_mark_rate',
        'enforce_fullscreen',
        'max_violations',
        'auto_submit_on_violation',
        'start_time',
        'end_time',
        'is_active',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'pass_percentage' => 'decimal:2',
            'total_marks' => 'decimal:2',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_result_instant' => 'boolean',
            'allow_review' => 'boolean',
            'negative_marking' => 'boolean',
            'negative_mark_rate' => 'decimal:2',
            'enforce_fullscreen' => 'boolean',
            'max_violations' => 'integer',
            'auto_submit_on_violation' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->status !== 'published') {
            return false;
        }

        $now = now();
        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }

        return true;
    }
}
