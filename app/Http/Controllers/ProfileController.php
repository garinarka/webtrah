<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Upload / ganti foto profil.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            // max 2048 KB (2MB). mimes dicek ekstensi, image dicek isi
            // file beneran gambar (bukan cuma nama file .jpg palsu).
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format harus JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran file maksimal 2MB.',
        ]);

        $user = $request->user();

        // Hapus foto lama dulu (kalau ada) — cegah file lama menumpuk
        // tanpa pernah kepakai lagi (orphaned files).
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Nama file di-generate ulang (bukan nama asli upload) — cegah
        // path traversal & tabrakan nama antar user.
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->forceFill(['avatar_path' => $path])->save();

        return back()->with('status', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Hapus foto profil, balik ke avatar inisial huruf default.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return back()->with('status', 'Foto profil dihapus.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Bersihkan foto profil juga sebelum akun dihapus — hindari
        // file yatim menumpuk di storage selamanya.
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
