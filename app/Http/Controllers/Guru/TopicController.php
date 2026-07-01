<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Topic;
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
        return back()->with('success');
    }

    public function show(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        // Ambil topic beserta pivot (is_published, is_open) dari relasi classroom
        $topicWithPivot = $classroom->topics()
            ->where('topic_id', $topic->id)
            ->with(['phases' => function($query) {
                $query->orderBy('order', 'asc');
            }])
            ->first();

        abort_unless($topicWithPivot, 404);

        return inertia('Guru/Topics/Show', [
            'classroom' => $classroom,
            'topic' => $topicWithPivot,
        ]);
    }

    public function destroy(Request $request, Classroom $classroom, Topic $topic)
    {
        if ($classroom->teacher_id !== $request->user()->id) { abort(403, 'Akses ditolak.'); }

        $this->topicService->deleteTopic($topic);
        return redirect()->route('guru.classes.show', $classroom->id)->with('success', 'Topik berhasil dihapus!');
    }

   public function togglePublish(Request $request, Classroom $classroom, Topic $topic)
{
    if ($classroom->teacher_id !== $request->user()->id) {
        abort(403, 'Akses ditolak.');
    }

    $this->topicService->togglePublish($classroom, $topic);

    // Kembalikan data topic yang sudah terupdate
    return back()->with('success', 'Status rilis materi berhasil diubah!');
}
}