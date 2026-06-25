<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Log Chatbot AI - {{ $classroom->class_name }}</title>
    <style>
        body {
            font-family: 'Instrument Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 10px;
            background-color: #ffffff;
        }
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0 0 5px 0;
            color: #0f172a;
        }
        .header .meta {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .chat-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .chat-header {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 15px;
            width: 100%;
            clear: both;
        }
        .student-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            float: left;
            width: 60%;
        }
        .chat-date {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
            float: right;
            width: 40%;
            text-align: right;
        }
        .clear-float {
            clear: both;
        }
        .bubble {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
        }
        .prompt-bubble {
            background-color: #f8fafc;
            border-left: 4px solid #6366f1;
        }
        .prompt-title {
            font-size: 10px;
            font-weight: 800;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }
        .response-bubble {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
        }
        .response-title {
            font-size: 10px;
            font-weight: 800;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }
        .bubble-text {
            color: #334155;
            font-weight: 500;
            margin: 0;
            white-space: pre-wrap;
        }
        .response-content {
            color: #334155;
        }
        .response-content p {
            margin: 0 0 10px 0;
        }
        .response-content p:last-child {
            margin-bottom: 0;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-style: italic;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Log Percakapan Chatbot AI</h1>
        <div class="meta">
            Kelas: {{ $classroom->class_name }} &nbsp;|&nbsp; 
            Guru: {{ auth()->user()->name }} &nbsp;|&nbsp; 
            Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}
            @if($search)
                &nbsp;|&nbsp; Filter Nama Siswa: "{{ $search }}"
            @endif
        </div>
    </div>

    @if($chatLogs->isEmpty())
        <div class="no-data">
            Tidak ada log percakapan chatbot AI yang terekam.
        </div>
    @else
        @foreach($chatLogs as $log)
            <div class="chat-card">
                <div class="chat-header">
                    <h3 class="student-name">{{ $log->user?->name ?? 'Siswa Terhapus' }}</h3>
                    <span class="chat-date">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                    <div class="clear-float"></div>
                </div>

                <div class="bubble prompt-bubble">
                    <div class="prompt-title">Pertanyaan Siswa</div>
                    <p class="bubble-text">{{ $log->prompt }}</p>
                </div>

                <div class="bubble response-bubble">
                    <div class="response-title">Jawaban AI Tutor</div>
                    <div class="response-content">
                        @if($log->response)
                            {!! Illuminate\Support\Str::markdown($log->response) !!}
                        @else
                            <em style="color: #94a3b8;">Gagal mendapatkan respon AI (Timeout / Limit).</em>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</body>
</html>
