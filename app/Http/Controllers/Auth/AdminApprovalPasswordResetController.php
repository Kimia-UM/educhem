<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AdminApprovalPasswordResetController extends Controller
{
    /**
     * Memproses pengajuan lupa password dari form email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email tidak terdaftar dalam sistem kami.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Pengecualian Akun ADMIN
        if ($user->hasRole('ADMIN')) {
            return back()->withErrors([
                'email' => 'Akun Administrator tidak dapat direset dengan metode ini. Silakan hubungi System Developer.',
            ]);
        }

        // Hapus pengajuan lama milik user ini
        PasswordResetRequest::where('user_id', $user->id)->delete();

        // Buat pengajuan baru
        $token = Str::random(64);
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'token' => $token,
            'status' => 'pending',
        ]);

        return redirect()->route('password-reset-request.waiting', ['token' => $token]);
    }

    /**
     * Menampilkan halaman tunggu atau form input password baru.
     */
    public function waitingView(string $token): Response|RedirectResponse
    {
        $resetRequest = PasswordResetRequest::where('token', $token)->firstOrFail();

        // Jika status sudah selesai, arahkan kembali ke login
        if ($resetRequest->status === 'completed') {
            return redirect()->route('login')->with('status', 'Sesi reset password telah selesai.');
        }

        // Cek kedaluwarsa (30 menit)
        if ($resetRequest->created_at->addMinutes(30)->isPast() && in_array($resetRequest->status, ['pending', 'approved'])) {
            $resetRequest->update(['status' => 'rejected']);
        }

        return Inertia::render('auth/ForgotPasswordWaiting', [
            'token' => $token,
            'status' => $resetRequest->status,
            'email' => $resetRequest->user->email,
        ]);
    }

    /**
     * API Polling untuk memeriksa status request reset password.
     */
    public function checkStatus(string $token): JsonResponse
    {
        $resetRequest = PasswordResetRequest::where('token', $token)->first();

        if (!$resetRequest) {
            return response()->json(['status' => 'not_found']);
        }

        // Cek kedaluwarsa (30 menit)
        if ($resetRequest->created_at->addMinutes(30)->isPast() && in_array($resetRequest->status, ['pending', 'approved'])) {
            $resetRequest->update(['status' => 'rejected']);
        }

        return response()->json(['status' => $resetRequest->status]);
    }

    /**
     * Memproses penyimpanan password baru setelah disetujui.
     */
    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        $resetRequest = PasswordResetRequest::where('token', $token)->firstOrFail();

        if ($resetRequest->status !== 'approved') {
            return back()->withErrors([
                'password' => 'Permintaan reset password belum disetujui atau sudah tidak valid.',
            ]);
        }

        // Cek kedaluwarsa (30 menit)
        if ($resetRequest->created_at->addMinutes(30)->isPast()) {
            $resetRequest->update(['status' => 'rejected']);
            return back()->withErrors([
                'password' => 'Sesi reset password Anda telah kedaluwarsa (lebih dari 30 menit). Silakan ajukan ulang.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $resetRequest->user;
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $resetRequest->update(['status' => 'completed']);

        return redirect()->route('login')->with('status', 'Password berhasil diperbarui! Silakan login.');
    }
}
