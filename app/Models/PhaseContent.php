<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_phase_id',
        'type',
        'content_data',
        'correct_answers',
        'order',
    ];

    // PENTING: Cast content_data menjadi array agar otomatis di-serialize/deserialize dari JSON
    protected $casts = [
        'content_data' => 'array',
        'correct_answers' => 'array',
    ];

    // Relasi balik ke TopicPhase
    public function phase(): BelongsTo
    {
        return $this->belongsTo(TopicPhase::class, 'topic_phase_id');
    }
    
}