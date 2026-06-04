<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\TopicController;
use App\Http\Controllers\Siswa\ClassroomController as SiswaClassroomController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\WorksheetController as SiswaWorksheetController;
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

    // Rute Toggle Publish (Wajib di atas resource topics)
    Route::post('classes/{classroom}/topics/{topic}/toggle-publish', [TopicController::class, 'togglePublish'])
        ->name('classes.topics.toggle-publish');

    // Rute Resource Topik
    Route::resource('classes.topics', TopicController::class)
        ->parameters(['classes' => 'classroom'])
        ->only(['store', 'update', 'destroy', 'show']);

    // Manajemen Fase (Phase)
    Route::post('topics/{topic}/phases', [TopicController::class, 'storePhase'])->name('phases.store');
    Route::get('classes/{classroom}/topics/{topic}/phases/{phase}', [TopicController::class, 'showPhase'])->name('phases.show');
    Route::put('phases/{phase}', [TopicController::class, 'updatePhase'])->name('phases.update');
    Route::delete('phases/{phase}', [TopicController::class, 'destroyPhase'])->name('phases.destroy');

    // Manajemen Konten (Content)
    Route::post('phases/{phase}/contents', [TopicController::class, 'storeContent'])->name('contents.store');
    Route::put('contents/{content}', [TopicController::class, 'updateContent'])->name('contents.update');
    Route::delete('contents/{content}', [TopicController::class, 'destroyContent'])->name('contents.destroy');
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

    // Worksheet (Materi Siswa)
    // Disesuaikan agar sinkron dengan WorksheetController
    Route::get('classes/{classroom}/topics/{topic}/phases/{phase}', [SiswaWorksheetController::class, 'show'])
        ->name('worksheet.show');

    Route::post('phases/{phase}/answers', [SiswaWorksheetController::class, 'storeAnswer'])
        ->name('answers.store');
});

require __DIR__.'/settings.php';