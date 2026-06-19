<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\AiChatLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiChatLogController extends Controller
{
    /**
     * Display chatbot interaction logs of students in the class.
     */
    public function index(Request $request, Classroom $classroom)
    {
        // Pengecekan kepemilikan kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar sebagai guru kelas ini.');
        }

        // Muat relasi kelas yang sama dengan detail kelas
        $classroom->load([
            'topics' => function ($query) {
                $query->latest();
            },
            'students' => function ($query) {
                $query->orderBy('name', 'asc');
            }
        ]);

        // Ambil ID siswa yang terdaftar di kelas ini
        $studentIds = $classroom->students()->pluck('users.id');

        // Query log chat dari siswa-siswa tersebut
        $query = AiChatLog::whereIn('user_id', $studentIds)
            ->with('user:id,name')
            ->latest();

        // Cari berdasarkan nama siswa jika ada query pencarian
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Paginate hasil log chat
        $chatLogs = $query->paginate(20)->withQueryString();

        return Inertia::render('Guru/Classes/Show', [
            'classroom' => $classroom,
            'chatLogs' => $chatLogs,
            'filters' => $request->only(['search']),
            'defaultTab' => 'chatLogs', // Agar Vue memfokuskan tab chatbot log secara otomatis
        ]);
    }

    /**
     * Show print-friendly chatbot interaction logs of students in the class (PDF/Print).
     */
    public function printChatLogs(Request $request, Classroom $classroom)
    {
        // Pengecekan kepemilikan kelas
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Ambil ID siswa yang terdaftar di kelas ini
        $studentIds = $classroom->students()->pluck('users.id');

        // Query SEMUA log chat dari siswa-siswa tersebut (tanpa paginasi untuk dicetak)
        $query = AiChatLog::whereIn('user_id', $studentIds)
            ->with('user:id,name')
            ->latest();

        // Cari berdasarkan nama siswa jika ada query pencarian
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $chatLogs = $query->get();

        return view('print.chat-logs', [
            'classroom' => $classroom,
            'chatLogs' => $chatLogs,
            'search' => $request->query('search'),
        ]);
    }
}
