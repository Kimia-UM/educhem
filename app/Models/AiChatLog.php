<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'classroom_id',
        'prompt',
        'response',
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