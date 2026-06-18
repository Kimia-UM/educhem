<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhaseDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'phase_id',
        'user_id',
        'parent_id',
        'message',
    ];

    /**
     * Relasi ke Fase (TopicPhase)
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(TopicPhase::class, 'phase_id');
    }

    /**
     * Relasi ke User yang menulis komentar
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke komentar parent (untuk nested reply)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relasi ke komentar balasan (children)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at', 'asc');
    }
}
