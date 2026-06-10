<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke pertanyaan (PhaseContent)
    public function content()
    {
        return $this->belongsTo(PhaseContent::class, 'content_id');
    }

    // Relasi ke User (Siswa)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}