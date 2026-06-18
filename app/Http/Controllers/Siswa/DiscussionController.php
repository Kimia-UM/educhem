<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PhaseDiscussion;
use App\Models\TopicPhase;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    /**
     * Mengambil semua komentar diskusi untuk sebuah fase (JSON response)
     */
    public function index(TopicPhase $phase)
    {
        $discussions = PhaseDiscussion::where('phase_id', $phase->id)
            ->whereNull('parent_id') // Hanya ambil komentar level atas
            ->with([
                'user:id,name',
                'replies' => function ($query) {
                    $query->with('user:id,name')->orderBy('created_at', 'asc');
                },
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($discussions);
    }

    /**
     * Menyimpan komentar diskusi baru
     */
    public function store(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:phase_discussions,id',
            'content_id' => 'required|integer', // Untuk identifikasi blok diskusi mana
        ]);

        PhaseDiscussion::create([
            'phase_id' => $phase->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Komentar berhasil dikirim!');
    }
}
