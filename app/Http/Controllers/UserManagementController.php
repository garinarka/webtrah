<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller implements HasMiddleware
{
    /**
     * Lapis kedua otorisasi (defense-in-depth). Route sudah dijaga
     * middleware role:admin, tapi controller ini tetap self-guard
     * supaya aman meski suatu saat route grup-nya berubah/salah pasang.
     *
     * Catatan Laravel 11: constructor-based $this->middleware() sudah
     * TIDAK berlaku lagi (itu cara Laravel 10 ke bawah). Laravel 11
     * mewajibkan controller implement HasMiddleware + method static
     * middleware() seperti di bawah ini.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin'),
        ];
    }

    /** daftar semua user */
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->withCount([
                'notifications as unread_notifications' => fn ($q) => $q->whereNull('read_at'),
            ])
            ->orderBy('created_at', 'desc');

        // filter role
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        // filter search
        if ($request->filled('search')) {
            $query->where(
                fn ($q) => $q
                    ->where('name', 'ilike', "%{$request->search}%")
                    ->orWhere('email', 'ilike', "%{$request->search}%")
            );
        }

        // filter status (active = email_verified, suspended = tidak)
        if ($request->filled('status')) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->paginate(20)->withQueryString();

        // statistik singkat
        $stats = [
            'total' => User::count(),
            'admins' => User::role('admin')->count(),
            'moderators' => User::role('moderator')->count(),
            'members' => User::role('user')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    /** detail satu user */
    public function show(User $user)
    {
        $user->load('roles');

        // statistik kontribusi
        $contributions = [
            'total_submitted' => Person::where('created_by', $user->id)->count(),
            'active' => Person::where('created_by', $user->id)->where('status', 'active')->count(),
            'pending' => Person::where('created_by', $user->id)->where('status', 'pending')->count(),
            'draft' => Person::where('created_by', $user->id)->where('status', 'draft')->count(),
        ];

        // riwayat approval requestnya
        $approvalHistory = \App\Models\Approval::with('approvable:id,display_name')
            ->where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'status' => $a->status,
                'person_name' => $a->approvable?->display_name ?? 'Data',
                'created_at' => $a->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users/Show', [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'user',
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'contributions' => $contributions,
            'approvalHistory' => $approvalHistory,
            'availableRoles' => Role::orderBy('name')->pluck('name'),
            'can' => [
                'change_role' => auth()->user()->isAdmin() && auth()->id() !== $user->id,
                'suspend' => auth()->user()->isAdmin() && auth()->id() !== $user->id,
            ],
        ]);
    }

    /** ganti role user */
    public function updateRole(Request $request, User $user)
    {
        // admin tidak bisa ganti role dirinya sendiri
        if (auth()->id() === $user->id) {
            return back()->withErrors(['role' => 'Tidak bisa mengubah role diri sendiri.']);
        }

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'moderator', 'user'])],
        ]);

        DB::beginTransaction();
        try {
            $oldRole = $user->roles->first()?->name ?? '—';
            $user->syncRoles([$request->role]);

            // audit log sederhana via session flash
            DB::commit();

            return back()->with('message', "Role {$user->name} diubah dari {$oldRole} menjadi {$request->role}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** reset password — kirim link reset ke email user */
    public function sendPasswordReset(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['reset' => 'Gunakan fitur lupa password untuk diri sendiri.']);
        }

        try {
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

            return back()->with('message', "Link reset password telah dikirim ke {$user->email}.");
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['reset' => "Gagal mengirim email: {$e->getMessage()}"]);
        }
    }

    /**
     * Daftar person aktif yang belum punya akun user.
     * Return JSON untuk autocomplete search di frontend.
     */
    public function peopleWithoutUsers(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $search = $request->get('search', '');

        $people = Person::whereDoesntHave('user')
            ->where('status', 'active')
            ->when($search, fn ($q) => $q->where('display_name', 'ilike', "%{$search}%"))
            ->orderBy('display_name')
            ->limit(30)
            ->get(['id', 'display_name', 'gender', 'birth_date']);

        return response()->json($people->map(fn ($p) => [
            'id' => $p->id,
            'display_name' => $p->display_name,
            'gender' => $p->gender,
            'birth_date' => $p->birth_date?->format('Y-m-d'),
        ]));
    }

    /**
     * Buat akun user baru dari data person yang sudah ada.
     * Nama diambil dari person; admin pilih email + role.
     * Link atur password dikirim otomatis ke email.
     */
    public function storeFromPerson(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'person_id' => ['required', 'uuid', 'exists:people,id'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'moderator', 'user'])],
        ], [
            'person_id.required' => 'Pilih data anggota.',
            'person_id.exists' => 'Data anggota tidak ditemukan.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'role.required' => 'Role wajib dipilih.',
        ]);

        $person = Person::findOrFail($data['person_id']);

        if (User::where('person_id', $person->id)->exists()) {
            return back()->withErrors(['person_id' => 'Data anggota ini sudah terhubung ke akun lain.']);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $person->display_name,
                'email' => $data['email'],
                'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                'person_id' => $person->id,
            ]);

            $user->assignRole($data['role']);

            // Commit di sini — pembuatan akun SELESAI dan tersimpan
            // permanen, terlepas dari apapun yang terjadi pada langkah
            // kirim email di bawah.
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Kirim link reset password: best-effort, DI LUAR transaction.
        // Kalau provider email gagal (mis. keterbatasan testing Resend,
        // rate limit, dsb), akun yang SUDAH DIBUAT tidak ikut batal —
        // admin cukup diberi tau supaya bisa kirim ulang manual nanti
        // lewat tombol "Kirim Reset Password" yang sudah ada di halaman
        // detail user.
        try {
            \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

            return redirect()->route('admin.users.index')
                ->with('message', "Akun untuk {$person->display_name} berhasil dibuat. Link atur password dikirim ke {$user->email}.");
        } catch (\Throwable $e) {
            report($e); // tetap masuk Sentry, supaya kegagalan kirim email tetap termonitor

            return redirect()->route('admin.users.index')
                ->with('message', "Akun untuk {$person->display_name} berhasil dibuat, TAPI link atur password gagal dikirim otomatis ({$e->getMessage()}). Gunakan tombol \"Kirim Reset Password\" di halaman detail user untuk kirim ulang.");
        }
    }
}
