<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\TopicController;
use App\Http\Controllers\Guru\PhaseController;
use App\Http\Controllers\Siswa\ClassroomController as SiswaClassroomController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\WorksheetController as SiswaWorksheetController;
use App\Http\Controllers\Siswa\ChatbotController;
use App\Http\Controllers\Siswa\DiscussionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('register', [RegisteredUserController::class, 'store']);

// =================================================================
// PENGATUR LALU LINTAS DASHBOARD (MENGGUNAKAN SPATIE)
// =================================================================
Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    // 1. Cek apakah user adalah ADMIN
    if ($user->hasRole(['ADMIN', 'admin', 'Admin'])) {
        return redirect()->route('admin.dashboard');
    }

    // 2. Cek apakah user adalah GURU
    if ($user->hasRole(['GURU', 'guru', 'Guru'])) {
        return redirect()->route('guru.dashboard');
    }
    
    // 3. Jika bukan Guru dan bukan Admin, OTOMATIS dianggap SISWA
    return redirect()->route('siswa.dashboard');
    
})->middleware(['auth', 'verified'])->name('dashboard');

// =================================================================
// AREA KHUSUS ADMIN
// =================================================================
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::post('/users/{user}/upgrade', [UserController::class, 'upgrade'])->name('users.upgrade');
});

// =================================================================
// AREA KHUSUS GURU
// =================================================================
Route::middleware(['auth', 'role:GURU'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('classes', GuruDashboardController::class)->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::get('classes/{classroom}/ai-chat-logs', [\App\Http\Controllers\Guru\AiChatLogController::class, 'index'])->name('classes.ai-chat-logs.index');

    // Rute Toggle Publish (Wajib di atas resource topics)
    Route::post('classes/{classroom}/topics/{topic}/toggle-publish', [TopicController::class, 'togglePublish'])
        ->name('classes.topics.toggle-publish');

    // Rute Resource Topik
    Route::resource('classes.topics', TopicController::class)
        ->parameters(['classes' => 'classroom'])
        ->only(['store', 'update', 'destroy', 'show']);

    // Manajemen Fase (Phase) menggunakan PhaseController
    Route::post('classes/{classroom}/topics/{topic}/phases', [PhaseController::class, 'store'])->name('phases.store');
    Route::get('classes/{classroom}/topics/{topic}/phases/{phase}', [PhaseController::class, 'show'])->name('phases.show');
    Route::put('phases/{phase}', [PhaseController::class, 'update'])->name('phases.update');
    Route::delete('phases/{phase}', [PhaseController::class, 'destroy'])->name('phases.destroy');

    // Manajemen Konten (Content) menggunakan PhaseController
    Route::post('phases/{phase}/contents', [PhaseController::class, 'storeContent'])->name('contents.store');
    Route::put('contents/{content}', [PhaseController::class, 'updateContent'])->name('contents.update');
    Route::delete('contents/{content}', [PhaseController::class, 'destroyContent'])->name('contents.destroy');

    // Rekap Jawaban Siswa
    Route::get('classes/{classroom}/topics/{topic}/phases/{phase}/answers', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'index'])
        ->name('student-answers.index');

    // Detail dan Evaluasi Jawaban Siswa
    Route::get('classes/{classroom}/students/{student}', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'showStudentAnswers'])
        ->name('classes.students.show');
    Route::post('answers/{answer}/evaluate', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'evaluateAnswer'])
        ->name('answers.evaluate');
    Route::post('classes/{classroom}/students/{student}/finish-evaluation', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'finishEvaluation'])
        ->name('classes.students.finish-evaluation');
    Route::post('classes/{classroom}/students/{student}/edit-evaluation', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'editEvaluation'])
        ->name('classes.students.edit-evaluation');
    Route::post('classes/{classroom}/students/{student}/send-evaluation', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'sendEvaluation'])
        ->name('classes.students.send-evaluation');
    Route::post('classes/{classroom}/students/{student}/scores', [\App\Http\Controllers\Guru\StudentAnswerController::class, 'updateScores'])
        ->name('classes.students.scores.update');
});

// =================================================================
// AREA KHUSUS SISWA
// =================================================================
Route::middleware(['auth', 'role:SISWA'])->prefix('siswa')->name('siswa.')->group(function () {
    
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');

    // Kelas Siswa
    Route::get('/classes', [SiswaClassroomController::class, 'index'])->name('classes.index');
    Route::post('/classes/join', [SiswaClassroomController::class, 'join'])->name('classes.join');
    Route::get('/classes/{classroom}', [SiswaClassroomController::class, 'show'])->name('classes.show');
    Route::get('/classes/{classroom}/evaluation-result', [SiswaClassroomController::class, 'evaluationResult'])->name('classes.evaluation-result');

    // Worksheet (Materi Siswa)
    Route::get('classes/{classroom}/topics/{topic}/phases/{phase}', [SiswaWorksheetController::class, 'show'])
        ->name('worksheet.show');

    Route::post('phases/{phase}/answers', [SiswaWorksheetController::class, 'storeAnswer'])
        ->name('answers.store');
        
    // PERBAIKAN DI SINI: Disesuaikan dengan struktur Group Route
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot', [ChatbotController::class, 'store'])->name('chatbot.store');

    // Forum Diskusi
    Route::get('phases/{phase}/discussions', [DiscussionController::class, 'index'])->name('discussions.index');
    Route::post('phases/{phase}/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
});

require __DIR__.'/settings.php';