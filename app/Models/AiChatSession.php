<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChatSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'classroom_id',
        'phase_id',
        'title',
        'is_legacy',
        'last_message_at',
        'cleared_at',
    ];

    protected $casts = [
        'is_legacy' => 'boolean',
        'last_message_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TopicPhase::class, 'phase_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AiChatLog::class, 'session_id');
    }
}
