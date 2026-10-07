<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    use HasFactory;

    public const AI_STATUS_NOT_REQUIRED = 'not_required';

    public const AI_STATUS_QUEUED = 'queued';

    public const AI_STATUS_PROCESSING = 'processing';

    public const AI_STATUS_COMPLETED = 'completed';

    public const AI_STATUS_FAILED = 'failed';

    public const AI_STATUS_LEGACY = 'legacy';

    protected $guarded = [];

    protected $casts = [
        'answer_version' => 'integer',
        'ai_requested_at' => 'datetime',
        'ai_completed_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    // Relasi ke pertanyaan (PhaseContent)
    public function content(): BelongsTo
    {
        return $this->belongsTo(PhaseContent::class, 'content_id');
    }

    // Relasi ke fase tempat jawaban disimpan
    public function phase(): BelongsTo
    {
        return $this->belongsTo(TopicPhase::class, 'phase_id');
    }

    // Relasi ke User (Siswa)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function effectiveAiStatus(): string
    {
        if ($this->ai_status) {
            return $this->ai_status;
        }

        return filled($this->ai_feedback)
            ? self::AI_STATUS_COMPLETED
            : self::AI_STATUS_LEGACY;
    }
}
