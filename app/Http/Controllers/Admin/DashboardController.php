<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Dependency Injection untuk UserService
    public function __construct(protected UserService $userService) {}

    public function index()
    {
        // 1. Memanggil service untuk mengambil data statistik
        $stats = $this->userService->getDashboardStats();

        // 2. Mengembalikan response Inertia ke frontend Vue (Dashboard.vue)
        // Pastikan huruf besar/kecil 'Admin/Dashboard' sesuai dengan nama folder/file Anda
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }
}