<?php

namespace App\Services;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\AiChatLog;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Mengambil statistik lengkap untuk Dashboard Admin
     */
    public function getDashboardStats(): array
    {
        $totalAiRequests = AiChatLog::count();
        $aiSuccessCount  = AiChatLog::whereNotNull('response')->where('response', '!=', '')->count();
        $aiSuccessRate   = $totalAiRequests > 0
            ? round(($aiSuccessCount / $totalAiRequests) * 100, 1)
            : 100;

        return [
            // --- Statistik Angka ---
            'total_users'       => User::count(),
            'total_guru'        => User::role('GURU')->count(),
            'total_siswa'       => User::role('SISWA')->count(),
            'total_kelas'       => Classroom::count(),
            'total_topik'       => Topic::count(),
            'total_ai_requests' => $totalAiRequests,
            'ai_success_rate'   => $aiSuccessRate,

            // --- Tabel: 5 User Terbaru ---
            'recent_users' => User::with('roles')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($u) => [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'role'       => $u->roles->first()?->name ?? '-',
                    'status'     => $u->status ? 'Active' : 'Inactive',
                    'created_at' => $u->created_at->diffForHumans(),
                ]),

            // --- Card: Kelas Aktif ---
            'active_classes' => Classroom::with('teacher')
                ->withCount(['students', 'topics'])
                ->latest()
                ->take(4)
                ->get()
                ->map(fn ($c) => [
                    'id'       => $c->id,
                    'name'     => $c->class_name,
                    'code'     => $c->class_code,
                    'teacher'  => $c->teacher?->name ?? '-',
                    'students' => $c->students_count,
                    'topics'   => $c->topics_count,
                ]),

            // --- System Health ---
            'system_health' => $this->getSystemHealth(),
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

    /**
     * Mendapatkan status kesehatan sistem
     */
    private function getSystemHealth(): array
    {
        return [
            'db'    => $this->checkDatabaseConnection(),
            'queue' => $this->checkQueueStatus(),
        ];
    }

    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();
            return 'online';
        } catch (\Exception $e) {
            return 'offline';
        }
    }

    private function checkQueueStatus(): string
    {
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count();
            return $failedJobs > 0 ? 'warning' : 'online';
        } catch (\Exception $e) {
            return 'online'; // Tabel failed_jobs mungkin belum ada
        }
    }
}