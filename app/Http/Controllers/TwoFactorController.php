<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Halaman setup: generate secret baru (belum aktif sampai dikonfirmasi).
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')
                ->with('status', '2FA sudah aktif untuk akun kamu.');
        }

        // Generate secret baru tiap kali halaman setup dibuka (belum
        // disimpan permanen sampai user konfirmasi kode pertama, supaya
        // tidak nyangkut "setengah aktif" kalau user batal di tengah jalan).
        $secret = Google2FA::generateSecretKey();
        $request->session()->put('2fa_pending_secret', $secret);

        $qrCodeUrl = Google2FA::getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return Inertia::render('TwoFactor/Setup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            // Tangkap pesan dari redirect middleware (mis. "wajib 2FA untuk
            // admin") — pola sama seperti halaman auth Breeze lainnya.
            'status' => session('status'),
        ]);
    }

    /**
     * Konfirmasi kode pertama kali — baru di sini 2FA benar-benar aktif.
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $secret = $request->session()->get('2fa_pending_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Sesi setup kadaluarsa, mulai ulang dari halaman setup.']);
        }

        if (! Google2FA::verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Kode salah, coba lagi.']);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::random(4).'-'.Str::random(4))
            ->all();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('2fa_pending_secret');

        // PENTING (aturan Inertia): respons untuk request POST WAJIB
        // redirect ke route GET, tidak boleh Inertia::render() langsung
        // dari sini — kalau tidak, Inertia "nempel" di URL POST ini dan
        // reload/refresh berikutnya bakal GET ke sini, padahal route
        // ini cuma terima POST -> error 405 Method Not Allowed.
        // Kode recovery dititip lewat session flash, dibaca sekali di
        // halaman GET tujuan lalu otomatis hilang.
        $request->session()->flash('2fa_recovery_codes', $recoveryCodes);

        return redirect()->route('two-factor.recovery-codes');
    }

    /**
     * Halaman GET terpisah buat nampilin recovery codes sekali—dituju
     * dari redirect confirm() di atas.
     */
    public function recoveryCodes(Request $request)
    {
        $codes = $request->session()->get('2fa_recovery_codes');

        if (! $codes) {
            // Diakses langsung tanpa lewat flow confirm() -> tidak ada
            // apa-apa buat ditampilkan, balik ke profile saja.
            return redirect()->route('profile.edit');
        }

        return Inertia::render('TwoFactor/RecoveryCodes', [
            'recoveryCodes' => $codes,
        ]);
    }

    /**
     * Nonaktifkan 2FA — wajib konfirmasi password dulu (aksi sensitif).
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', '2FA dinonaktifkan.');
    }

    /**
     * Halaman challenge — muncul setelah login berhasil tapi 2FA belum
     * diverifikasi di sesi ini.
     */
    public function challenge(Request $request)
    {
        if (! $request->session()->get('2fa_awaiting_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verifikasi kode TOTP atau recovery code saat login.
     */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('2fa_awaiting_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);
        $code = trim($request->input('code'));

        $verified = Google2FA::verifyKey($user->two_factor_secret, $code);

        // Kalau bukan kode TOTP valid, cek apakah itu recovery code.
        if (! $verified && in_array($code, $user->two_factor_recovery_codes ?? [], true)) {
            $verified = true;

            // Recovery code sekali pakai — hapus setelah dipakai.
            $remaining = array_values(array_diff($user->two_factor_recovery_codes, [$code]));
            $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'Kode salah.']);
        }

        $request->session()->forget('2fa_awaiting_user_id');
        $request->session()->put('2fa_verified', true);

        auth()->login($user, $request->session()->pull('2fa_remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
