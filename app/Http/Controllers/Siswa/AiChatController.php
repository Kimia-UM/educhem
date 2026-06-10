<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\TopicPhase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiChatController extends Controller
{
    public function chat(Request $request, TopicPhase $phase)
    {
        $request->validate(['message' => 'required|string']);

        // 1. Ambil seluruh konten materi sebagai konteks
        $materi = $phase->contents->pluck('content_data')->toJson();

        // 2. Siapkan Prompt
        $prompt = "Kamu adalah Tutor AI ahli kimia. Gunakan materi berikut sebagai referensi utama:\n{$materi}\n\nPertanyaan siswa: {$request->message}";

        // 3. Tembak Gemini (Native HTTP)
        $apiKey = env('GEMINI_API_KEY');
        $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        return response()->json([
            'reply' => $response->json('candidates.0.content.parts.0.text')
        ]);
    }
}