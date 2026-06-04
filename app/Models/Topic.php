<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    // RELASI BARU: Satu topik memiliki banyak fase (Urut berdasarkan kolom 'order')
    public function phases(): HasMany
    {
        return $this->hasMany(TopicPhase::class, 'topic_id')->orderBy('order', 'asc');
    }

    public function classAccesses(): HasMany
    {
        return $this->hasMany(ClassTopicAccess::class, 'topic_id');
    }
}