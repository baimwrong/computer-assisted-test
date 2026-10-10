<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'category_id',
        'question_text',
        'question_type', // single_choice, multiple_choice, true_false, short_answer
        'points',
        'negative_points',
        'difficulty',
        'explanation',
        'order_index',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'negative_points' => 'decimal:2',
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function getCorrectOptionIdsAttribute(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->values()->all();
    }
}
