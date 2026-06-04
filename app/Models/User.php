<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // Spatie HasRoles sudah terpasang sempurna

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /**
     * UNTUK GURU: Mendapatkan semua kelas yang telah dibuat oleh guru ini.
     * (Mendukung guru memiliki banyak kelas).
     */
    public function taughtClasses(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    /**
     * UNTUK SISWA: Mendapatkan semua kelas yang diikuti oleh siswa ini.
     * (Mendukung siswa bergabung lebih dari satu kelas).
     */
    public function joinedClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'class_members', 'user_id', 'class_id')
                    ->withTimestamps();
    }
    
    // Fungsi booted() dihapus agar tidak bentrok dengan Controller
}