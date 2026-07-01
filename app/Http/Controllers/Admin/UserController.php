<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role; // Tambahkan ini untuk menarik daftar role

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        
        $users = $this->userService->getAllUsersWithRoles($perPage, $search);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    // --- FITUR BARU: Menampilkan Halaman Pembuatan Akun Baru ---
    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::all()
        ]);
    }

    // --- FITUR BARU: Menyimpan Akun Baru di Database ---
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
        ]);

        $this->userService->createUser($request->only(['name', 'email', 'password', 'role']));

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    // --- FITUR BARU: Menampilkan Halaman Edit Role ---
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => Role::all() // Mengirim semua pilihan role ke frontend
        ]);
    }

    // --- FITUR BARU: Memperbarui Role di Database ---
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        $this->userService->updateRole($user, $request->role);

        return redirect()->route('admin.users.index')->with('success', 'Role pengguna berhasil diperbarui.');
    }

    // --- FITUR BARU: Menghapus Pengguna ---
    public function destroy(User $user)
    {
        // Proteksi: Admin tidak boleh menghapus dirinya sendiri
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $this->userService->deleteUser($user);

        return redirect()->back()->with('success', 'Akun pengguna berhasil dihapus.');
    }

    // Fungsi lama untuk tombol "Angkat Jadi Guru" yang spesifik
    public function upgrade(User $user)
    {
        $this->userService->upgradeToGuru($user);
        return redirect()->back();
    }
}