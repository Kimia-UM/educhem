<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classroom extends Model
{
    use HasFactory;
    
    // Memastikan model ini terhubung ke tabel 'classes' sesuai PRD
    protected $table = 'classes';

    protected $fillable = [
        'teacher_id',
        'class_name',
        'class_code',
        'description',
    ];

    /**
     * Relasi: Kelas ini dibuat/dimiliki oleh 1 Guru
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relasi: Kelas ini memiliki banyak Siswa (Peserta)
     * Menggunakan tabel pivot class_members
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_members', 'class_id', 'user_id')
                    ->withPivot('is_evaluation_sent', 'is_evaluation_finished', 'pre_test_score', 'post_test_score')
                    ->withTimestamps();
    }

    /**
     * Relasi: Kelas ini memiliki akses ke banyak Topik (Bank Materi)
     * Menggunakan tabel pivot class_topic_accesses
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'class_topic_accesses', 'class_id', 'topic_id')
                    ->withPivot('is_open')
                    ->withTimestamps();
    }
}