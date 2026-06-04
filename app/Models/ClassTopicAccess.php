<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTopicAccess extends Model
{
    use HasFactory;

    protected $table = 'class_topic_accesses';

    protected $fillable = [
        'class_id',
        'topic_id',
        'is_open',
    ];

    /**
     * Konversi tipe data otomatis dari PostgreSQL ke tipe boolean PHP
     */
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    /**
     * Relasi ke Kelas
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Relasi ke Topik
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}