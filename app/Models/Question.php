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
        'topic_id',
        'type',
        'content',
        'correct_answer',
    ];

    /**
     * Otomatis melakukan konversi JSONB dari PostgreSQL menjadi Array di PHP
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'correct_answer' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExerciseAttempt::class);
    }
}