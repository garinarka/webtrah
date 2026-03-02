<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /** daftar semua user */
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->withCount([
                'notifications as unread_notifications' => fn($q) => $q->whereNull('read_at'),
            ])
            ->orderBy('created_at', 'desc');

        // filter role
        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        // filter search
        if ($request->filled('search')) {
            $query->where(
                fn($q) => $q
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
            'total'      => User::count(),
            'admins'     => User::role('admin')->count(),
            'moderators' => User::role('moderator')->count(),
            'members'    => User::role('user')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles'   => Role::orderBy('name')->pluck('name'),
        ]);
    }

    /** detail satu user */
    public function show(User $user)
    {
        $user->load('roles');

        // statistik kontribusi
        $contributions = [
            'total_submitted' => Person::where('created_by', $user->id)->count(),
            'active'          => Person::where('created_by', $user->id)->where('status', 'active')->count(),
            'pending'         => Person::where('created_by', $user->id)->where('status', 'pending')->count(),
            'draft'           => Person::where('created_by', $user->id)->where('status', 'draft')->count(),
        ];

        // riwayat approval requestnya
        $approvalHistory = \App\Models\Approval::with('approvable:id,display_name')
            ->where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'action'      => $a->action,
                'status'      => $a->status,
                'person_name' => $a->approvable?->display_name ?? 'Data',
                'created_at'  => $a->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users/Show', [
            'member'         => [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'role'               => $user->roles->first()?->name ?? 'user',
                'email_verified_at'  => $user->email_verified_at?->toIso8601String(),
                'created_at'         => $user->created_at->toIso8601String(),
            ],
            'contributions'  => $contributions,
            'approvalHistory' => $approvalHistory,
            'availableRoles' => Role::orderBy('name')->pluck('name'),
            'can'            => [
                'change_role' => auth()->user()->isAdmin() && auth()->id() !== $user->id,
                'suspend'     => auth()->user()->isAdmin() && auth()->id() !== $user->id,
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

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

        return back()->with('message', "Link reset password telah dikirim ke {$user->email}.");
    }
}
