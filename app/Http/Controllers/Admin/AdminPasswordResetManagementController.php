<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPasswordResetManagementController extends Controller
{
    /**
     * Menampilkan daftar request reset password.
     */
    public function index(): Response
    {
        // Bersihkan request yang kedaluwarsa secara otomatis
        PasswordResetRequest::where('created_at', '<', now()->subMinutes(30))
            ->whereIn('status', ['pending', 'approved'])
            ->update(['status' => 'rejected']);

        $requests = PasswordResetRequest::with(['user.roles'])
            ->orderByRaw("CASE status 
                WHEN 'pending' THEN 1 
                WHEN 'approved' THEN 2 
                WHEN 'rejected' THEN 3 
                WHEN 'completed' THEN 4 
                ELSE 5 
            END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/PasswordResets', [
            'resetRequests' => $requests,
        ]);
    }

    /**
     * Menyetujui request reset password.
     */
    public function approve(int $id): RedirectResponse
    {
        $resetRequest = PasswordResetRequest::findOrFail($id);

        if ($resetRequest->created_at->addMinutes(30)->isPast()) {
            $resetRequest->update(['status' => 'rejected']);
            return redirect()->back()->with('error', 'Request ini sudah kedaluwarsa (lebih dari 30 menit).');
        }

        $resetRequest->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Permintaan reset password berhasil disetujui.');
    }

    /**
     * Menolak request reset password.
     */
    public function reject(int $id): RedirectResponse
    {
        $resetRequest = PasswordResetRequest::findOrFail($id);
        $resetRequest->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Permintaan reset password telah ditolak.');
    }
}
