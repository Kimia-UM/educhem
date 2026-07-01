<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Evaluasi Jawaban Siswa - {{ $student->name }} - {{ $classroom->class_name }}</title>
    <style>
        body {
            font-family: 'Instrument Sans', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 10px;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .meta-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            line-height: 1.6;
        }
        .score-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
        }
        .score-item {
            text-align: center;
        }
        .score-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 2px;
        }
        .score-val {
            font-size: 16px;
            font-weight: 800;
            color: #4f46e5;
        }
        .phase-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .phase-title {
            background-color: #f1f5f9;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #4f46e5;
        }
        .answer-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .question-label {
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }
        .question-text {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
        }
        .answer-box {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .answer-box-title {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 4px;
        }
        .answer-text {
            color: #334155;
            margin: 0;
        }
        .answer-text p {
            margin-top: 0;
            margin-bottom: 6px;
        }
        .answer-text p:last-child {
            margin-bottom: 0;
        }
        .answer-text ul, .answer-text ol {
            margin-top: 4px;
            margin-bottom: 6px;
            padding-left: 20px;
        }
        .answer-text ul:last-child, .answer-text ol:last-child {
            margin-bottom: 0;
        }
        .answer-text .ql-ui {
            display: none;
        }
        .evaluation-row {
            font-size: 11px;
            font-weight: 700;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
            width: 100%;
            clear: both;
        }
        .eval-left {
            float: left;
            width: 50%;
            color: #475569;
        }
        .eval-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .clear-float {
            clear: both;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-benar {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-setengah_benar {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fef3c7;
        }
        .badge-salah {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }
        .badge-unreviewed {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .no-data {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-style: italic;
            border: 1px dashed #e2e8f0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    @php
        $checkAutoGrade = function($answer) {
            $content = $answer->content;
            if (!$content) return null;
            $correctAnswers = $content->correct_answers ?? [];
            $studentAns = $answer->answer_data;
            
            $options = $content->content_data['options'] ?? [];
            $correctAnswersStr = array_map('strval', (array)$correctAnswers);

            if ($content->type === 'eval_mcq') {
                if (is_string($studentAns)) {
                    $idx = array_search($studentAns, $options);
                    if ($idx !== false) {
                        return in_array((string)$idx, $correctAnswersStr);
                    }
                }
                return in_array((string)$studentAns, $correctAnswersStr);
            } elseif ($content->type === 'eval_cmcq') {
                if (is_string($studentAns)) {
                    $decoded = json_decode($studentAns, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $studentAns = $decoded;
                    }
                }
                if (!is_array($studentAns)) {
                    $studentAns = (array)$studentAns;
                }

                $studentIndices = [];
                foreach ($studentAns as $ans) {
                    $idx = array_search($ans, $options);
                    if ($idx !== false) {
                        $studentIndices[] = (string)$idx;
                    } else {
                        $studentIndices[] = (string)$ans;
                    }
                }

                sort($studentIndices);
                sort($correctAnswersStr);

                return $studentIndices === $correctAnswersStr;
            }
            return null;
        };
    @endphp

    <table class="header-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; width: 65%;">
                <h1 style="font-size: 20px; margin: 0 0 5px 0; color: #0f172a;">Hasil Evaluasi & Jawaban Siswa</h1>
                <div class="meta-text">
                    Siswa: <strong>{{ $student->name }}</strong> ({{ $student->email }}) <br>
                    Kelas: {{ $classroom->class_name }} &nbsp;|&nbsp; 
                    Guru Penguji: {{ auth()->user()->name }} <br>
                    Tanggal Cetak: {{ now()->translatedFormat('d F Y, H:i') }}
                </div>
            </td>
            <td style="vertical-align: top; width: 35%; text-align: right;">
                <div class="score-box" style="display: inline-block; text-align: left;">
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td class="score-item" style="padding-right: 15px;">
                                <div class="score-label">Pre-test</div>
                                <div class="score-val">{{ $pivot->pre_test_score ?? '-' }}</div>
                            </td>
                            <td class="score-item" style="border-left: 1px solid #e2e8f0; padding-left: 15px;">
                                <div class="score-label">Post-test</div>
                                <div class="score-val">{{ $pivot->post_test_score ?? '-' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    @foreach($topics as $topic)
        @foreach($topic->phases as $phase)
            @php
                $phaseAnswers = $answers->filter(function($a) use ($phase) {
                    return $a->phase_id === $phase->id;
                });
            @endphp

            <div class="phase-section">
                <div class="phase-title">
                    {{ $topic->title }} - {{ $phase->name }}
                </div>

                @if($phaseAnswers->isEmpty())
                    <div class="no-data">
                        Siswa belum menjawab soal di fase ini.
                    </div>
                @else
                    @foreach($phaseAnswers as $idx => $answer)
                        <div class="answer-card">
                            <div class="question-label">Pertanyaan {{ $idx + 1 }}</div>
                            <div class="question-text">
                                {!! $answer->content->content_data['question'] ?? $answer->content->content_data['label'] ?? $answer->content->content_data['content'] ?? '' !!}
                            </div>

                            <div class="answer-box">
                                <div class="answer-box-title">Jawaban Siswa:</div>
                                @if(in_array($answer->content->type, ['eval_mcq', 'eval_cmcq']))
                                    @php
                                        $options = $answer->content->content_data['options'] ?? [];
                                        $studentAns = $answer->answer_data;
                                    @endphp
                                    <ul style="margin: 0; padding-left: 20px; color: #334155;">
                                        @if($answer->content->type === 'eval_mcq')
                                            <li>{{ isset($options[$studentAns]) ? $options[$studentAns] : $studentAns }}</li>
                                        @else
                                            @php
                                                if (is_string($studentAns)) {
                                                    $studentAns = json_decode($studentAns, true) ?? [];
                                                }
                                                $studentAnsArray = (array)$studentAns;
                                            @endphp
                                            @foreach($studentAnsArray as $ans)
                                                <li>{{ isset($options[$ans]) ? $options[$ans] : $ans }}</li>
                                            @endforeach
                                        @endif
                                    </ul>
                                @elseif($answer->content->type === 'eval_file')
                                    @if(preg_match('/\.(jpeg|jpg|gif|png|webp)/i', $answer->answer_data))
                                        <img src="{{ $answer->answer_data }}" alt="Uploaded file" style="max-height: 200px; display: block; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 5px;" />
                                    @else
                                        <p class="answer-text">
                                            <a href="{{ $answer->answer_data }}" target="_blank" style="color: #4f46e5; font-weight: 700; text-decoration: underline;">
                                                Lihat File Terunggah
                                            </a>
                                        </p>
                                    @endif
                                @else
                                    <div class="answer-text">{!! $answer->answer_data !!}</div>
                                @endif
                            </div>

                            <div class="evaluation-row">
                                <div class="eval-left">Penilaian / Evaluasi:</div>
                                <div class="eval-right">
                                    @if(in_array($answer->content->type, ['eval_mcq', 'eval_cmcq']))
                                        @php
                                            $isAutoCorrect = $checkAutoGrade($answer);
                                        @endphp
                                        @if($isAutoCorrect)
                                            <span class="badge badge-benar">Benar (Auto)</span>
                                        @else
                                            <span class="badge badge-salah">Salah (Auto)</span>
                                        @endif
                                    @else
                                         @if($answer->evaluation === 'benar')
                                             <span class="badge badge-benar">Benar (Skor: 2/2)</span>
                                         @elseif($answer->evaluation === 'setengah_benar')
                                             <span class="badge badge-setengah_benar">Setengah Benar (Skor: 1/2)</span>
                                         @elseif($answer->evaluation === 'salah')
                                             <span class="badge badge-salah">Salah (Skor: 0/2)</span>
                                         @else
                                             <span class="badge badge-unreviewed">Belum Dinilai (Skor: -)</span>
                                         @endif
                                    @endif
                                </div>
                                <div class="clear-float"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    @endforeach
</body>
</html>
