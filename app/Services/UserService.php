<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Mengambil statistik untuk Dashboard Admin
     */
    public function getDashboardStats(): array
    {
        return [
            'total_users' => User::count(),
            // Menggunakan scope 'role' bawaan Spatie Laravel Permission
            'total_guru'  => User::role('GURU')->count(), 
            'total_siswa' => User::role('SISWA')->count(),
        ];
    }

    /**
     * Mengambil semua user beserta role-nya, mendukung fitur pencarian (Search)
     */
    public function getAllUsersWithRoles($perPage = 10, $search = null)
    {
        $query = User::with('roles');

        // Fitur Pencarian menggunakan 'ilike' (khusus PostgreSQL agar case-insensitive)
        if ($search) {
            $query->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function upgradeToGuru(User $user)
    {
        $user->syncRoles(['GURU']);
    }

    public function updateRole(User $user, string $roleName)
    {
        $user->syncRoles([$roleName]);
    }

    public function deleteUser(User $user)
    {
        $user->delete();
    }
}