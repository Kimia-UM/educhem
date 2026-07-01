<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\PhaseContent;

class PhaseService
{
    public function createPhase(Topic $topic, array $data)
    {
        $data['order'] = $topic->phases()->count() + 1;
        return $topic->phases()->create($data);
    }

    public function updatePhase(TopicPhase $phase, array $data)
    {
        $phase->update($data);
        return $phase;
    }

    public function deletePhase(TopicPhase $phase)
    {
        return $phase->delete();
    }

    // Mengelola Konten di dalam Fase (Builder)
    public function createContent(TopicPhase $phase, array $data)
    {
        $data['order'] = $phase->contents()->count() + 1;
        // Default content_data jika tidak ada
        if (!isset($data['content_data'])) {
            $data['content_data'] = [];
        }
        if (!isset($data['correct_answers'])) {
            $data['correct_answers'] = [];
        }
        return $phase->contents()->create($data);
    }

    public function updateContent(PhaseContent $content, array $data)
    {
        $content->update($data);
        return $content;
    }

    public function deleteContent(PhaseContent $content)
    {
        return $content->delete();
    }

    public function reorderContent(TopicPhase $phase, PhaseContent $content, string $direction)
    {
        $contents = $phase->contents()->orderBy('order', 'asc')->get();
        foreach ($contents as $index => $c) {
            $c->update(['order' => $index + 1]);
        }

        $contents = $phase->contents()->orderBy('order', 'asc')->get();
        $currentIndex = $contents->search(fn($c) => $c->id === $content->id);

        if ($currentIndex === false) {
            return false;
        }

        if ($direction === 'up' && $currentIndex > 0) {
            $swapContent = $contents[$currentIndex - 1];
            $content->update(['order' => $currentIndex]);
            $swapContent->update(['order' => $currentIndex + 1]);
        } elseif ($direction === 'down' && $currentIndex < count($contents) - 1) {
            $swapContent = $contents[$currentIndex + 1];
            $content->update(['order' => $currentIndex + 2]);
            $swapContent->update(['order' => $currentIndex + 1]);
        }

        return true;
    }

    public function reorderPhase(Topic $topic, TopicPhase $phase, string $direction)
    {
        $phases = $topic->phases()->orderBy('order', 'asc')->get();
        foreach ($phases as $index => $p) {
            $p->update(['order' => $index + 1]);
        }

        $phases = $topic->phases()->orderBy('order', 'asc')->get();
        $currentIndex = $phases->search(fn($p) => $p->id === $phase->id);

        if ($currentIndex === false) {
            return false;
        }

        if ($direction === 'up' && $currentIndex > 0) {
            $swapPhase = $phases[$currentIndex - 1];
            $phase->update(['order' => $currentIndex]);
            $swapPhase->update(['order' => $currentIndex + 1]);
        } elseif ($direction === 'down' && $currentIndex < count($phases) - 1) {
            $swapPhase = $phases[$currentIndex + 1];
            $phase->update(['order' => $currentIndex + 2]);
            $swapPhase->update(['order' => $currentIndex + 1]);
        }

        return true;
    }
}