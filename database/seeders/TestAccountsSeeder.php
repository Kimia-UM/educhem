<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Seed 1 akun GURU dan 2 akun SISWA untuk kebutuhan testing multi-role.
     */
    public function run(): void
    {
        // Reset cached roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Pastikan roles sudah ada (idempotent)
        $roleGuru  = Role::firstOrCreate(['name' => 'GURU']);
        $roleSiswa = Role::firstOrCreate(['name' => 'SISWA']);

        // --- 1 akun GURU ---
        $guru = User::firstOrCreate(
            ['email' => 'guru@test.com'],
            [
                'name'     => 'Guru Kimia',
                'password' => Hash::make('Password123_'),
                'status'   => true,
            ]
        );
        $guru->syncRoles([$roleGuru]);

        // --- 2 akun SISWA ---
        $siswa1 = User::firstOrCreate(
            ['email' => 'siswa1@test.com'],
            [
                'name'     => 'Siswa Satu',
                'password' => Hash::make('Password123_'),
                'status'   => true,
            ]
        );
        $siswa1->syncRoles([$roleSiswa]);

        $siswa2 = User::firstOrCreate(
            ['email' => 'siswa2@test.com'],
            [
                'name'     => 'Siswa Dua',
                'password' => Hash::make('Password123_'),
                'status'   => true,
            ]
        );
        $siswa2->syncRoles([$roleSiswa]);

        $this->command->info('✅ Akun test berhasil dibuat:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['GURU',  'guru@test.com',   'Password123_'],
                ['SISWA', 'siswa1@test.com', 'Password123_'],
                ['SISWA', 'siswa2@test.com', 'Password123_'],
            ]
        );
    }
}
