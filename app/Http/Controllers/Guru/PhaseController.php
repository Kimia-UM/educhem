<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\PhaseContent;
use App\Models\PhaseDiscussion;
use App\Services\PhaseService;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    public function __construct(protected PhaseService $phaseService) {}

    // ==========================================
    // MANAJEMEN FASE
    // ==========================================
    public function store(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->phaseService->createPhase($topic, $validated);
        return back()->with('success', 'Fase pembelajaran berhasil ditambahkan!');
    }

    public function show(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $phase->load(['contents' => function($query) {
            $query->orderBy('order', 'asc');
        }]);

        // Ambil data diskusi siswa untuk ditampilkan di panel guru
        $discussions = PhaseDiscussion::where('phase_id', $phase->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'replies' => function ($query) {
                    $query->with('user:id,name')->orderBy('created_at', 'asc');
                },
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return inertia('Guru/Phases/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase,
            'discussions' => $discussions,
        ]);
    }

    public function update(Request $request, TopicPhase $phase)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_ai_enabled' => 'boolean',
            'is_chatbot_enabled' => 'boolean',
            'ai_prompt_setting' => 'nullable|string',
            'chatbot_prompt_setting' => 'nullable|string'
        ]);
        
        $this->phaseService->updatePhase($phase, $validated);
        return back();
    }

    public function destroy(TopicPhase $phase)
    {
        $this->phaseService->deletePhase($phase);
        return back();
    }

    public function reorderPhase(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase, string $direction)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $this->phaseService->reorderPhase($topic, $phase, $direction);
        return back()->with('success', 'Urutan fase berhasil diubah!');
    }

    // ==========================================
    // MANAJEMEN KONTEN FASE (BUILDER BLOK)
    // ==========================================
    public function storeContent(Request $request, TopicPhase $phase)
    {
        $this->phaseService->createContent($phase, [
            'type' => $request->type,
            'content_data' => $request->content_data,
            'correct_answers' => $request->correct_answers
        ]);
        return back();
    }

    public function updateContent(Request $request, PhaseContent $content)
    {
        $this->phaseService->updateContent($content, [
            'content_data' => $request->content_data,
            'correct_answers' => $request->correct_answers
        ]);
        return back();
    }

    public function destroyContent(PhaseContent $content)
    {
        $this->phaseService->deleteContent($content);
        return back();
    }

    public function reorderContent(Request $request, TopicPhase $phase, PhaseContent $content, string $direction)
    {
        if ($phase->topic && $phase->topic->classroom) {
            $classroom = $phase->topic->classroom;
            if ($classroom->teacher_id !== $request->user()->id) {
                abort(403, 'Akses ditolak.');
            }
        }

        $this->phaseService->reorderContent($phase, $content, $direction);
        return back()->with('success', 'Urutan konten berhasil diubah!');
    }
}