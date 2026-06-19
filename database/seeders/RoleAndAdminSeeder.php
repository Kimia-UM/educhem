<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat roles
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleGuru = Role::firstOrCreate(['name' => 'GURU']);
        $roleSiswa = Role::firstOrCreate(['name' => 'SISWA']);

        // 3. Buat akun Admin Default
        $admin = User::firstOrCreate(
            ['email' => 'educhem.ai@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('adminedu123_'), // Ganti dengan password yang lebih aman di production
                'status' => true,
            ]
        );

        // 4. Gunakan syncRoles untuk menimpa default role 'SISWA' dari Eloquent Event
        $admin->syncRoles([$roleAdmin]);
    }
}