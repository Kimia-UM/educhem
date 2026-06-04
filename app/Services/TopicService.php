<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class TopicService
{
    public function createTopicForClassroom(Classroom $classroom, array $data)
    {
        return DB::transaction(function () use ($classroom, $data) {
            $topic = Topic::create($data);
            $classroom->topics()->attach($topic->id);
            return $topic;
        });
    }

    public function updateTopic(Topic $topic, array $data)
    {
        $topic->update($data);
        return $topic;
    }

    public function deleteTopic(Topic $topic)
    {
        return $topic->delete();
    }

    // ==========================================
    // LOGIKA BARU: PUBLISH / UNPUBLISH TOPIK
    // ==========================================
    public function togglePublish(Topic $topic)
    {
        // Langsung balikkan nilainya di tabel master topics
        $topic->update([
            'is_published' => !$topic->is_published
        ]);
        
        return $topic->is_published;
    }
}