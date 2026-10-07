<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'classroom_id',
        'prompt',
        'response',
        'status',
        'error_code',
        'completed_at',
        'processing_mode',
        'enqueued_at_ms',
        'processing_started_at',
        'queue_wait_ms',
        'direct_attempt_ms',
        'provider_duration_ms',
        'total_duration_ms',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'enqueued_at_ms' => 'integer',
        'queue_wait_ms' => 'integer',
        'direct_attempt_ms' => 'integer',
        'provider_duration_ms' => 'integer',
        'total_duration_ms' => 'integer',
    ];

    // Relasi ke tabel User (Siswa)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke tabel Classroom
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
