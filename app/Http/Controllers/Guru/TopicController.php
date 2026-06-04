<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\PhaseContent;
use App\Services\TopicService;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function __construct(protected TopicService $topicService) {}

    public function store(Request $request, Classroom $classroom)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->topicService->createTopicForClassroom($classroom, $validated);
        
        return back()->with('success', 'Topik pembelajaran berhasil dibuat!');
    }

    public function update(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->topicService->updateTopic($topic, $validated);
        
        return back()->with('success', 'Informasi topik berhasil diperbarui!');
    }

    public function show(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        // Ambil topik lengkap dengan fase
        $topic->load(['phases' => function($query) {
            $query->orderBy('order', 'asc');
        }]);

        return inertia('Guru/Topics/Show', [
            'classroom' => $classroom,
            'topic' => $topic
        ]);
    }

    public function destroy(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { 
            abort(403, 'Akses ditolak.'); 
        }

        $this->topicService->deleteTopic($topic);
        
        return redirect()->route('guru.classes.show', $classroom->id)
                         ->with('success', 'Topik berhasil dihapus!');
    }

    public function togglePublish(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        // Memanggil fungsi dari TopicService agar lebih clean
        $this->topicService->togglePublish($topic);

        return back()->with('success', 'Status rilis materi berhasil diubah!');
    }

    // =================================================================
    // METODE BARU: DYNAMIC BUILDER (FASE & KONTEN)
    // =================================================================

    public function storePhase(Request $request, Topic $topic) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $topic->phases()->create([
            'name' => $validated['name'], 
            'order' => $topic->phases()->count() + 1
        ]);
        
        return back()->with('success', 'Fase pembelajaran berhasil ditambahkan!');
    }

    public function showPhase(Request $request, Classroom $classroom, Topic $topic, TopicPhase $phase) {
        if ($classroom->teacher_id !== $request->user()->id) { 
            abort(403, 'Akses ditolak.'); 
        }

        $phase->load(['contents' => function($query) {
            $query->orderBy('order', 'asc');
        }]);

        return inertia('Guru/Phases/Show', [
            'classroom' => $classroom,
            'topic' => $topic,
            'phase' => $phase
        ]);
    }

    public function updatePhase(Request $request, TopicPhase $phase) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_ai_enabled' => 'boolean',
            'ai_prompt_setting' => 'nullable|string'
        ]);
        
        $phase->update($validated);
        return back();
    }

    public function destroyPhase(TopicPhase $phase) {
        $phase->delete();
        return back();
    }

    public function storeContent(Request $request, TopicPhase $phase) {
        $phase->contents()->create([
            'type' => $request->type, 
            'content_data' => [], 
            'order' => $phase->contents()->count() + 1
        ]);
        return back();
    }

    public function updateContent(Request $request, PhaseContent $content) {
        $content->update([
            'content_data' => $request->content_data
        ]);
        return back();
    }

    public function destroyContent(PhaseContent $content) {
        $content->delete();
        return back();
    }
}