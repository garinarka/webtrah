<?php

namespace App\Http\Controllers;

use App\Models\FamilyUnit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FamilyUnitController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $familyUnits = FamilyUnit::with(['assignedModerators:id,name'])
            ->withCount('people')
            ->orderBy('name')
            ->get();

        $user = auth()->user();

        // IDs unit yang bisa diedit user ini -- server-computed agar reliable.
        // Untuk moderator: query langsung ke pivot table agar tidak ada ambiguitas
        // kolom 'id' antara tabel family_units dan moderator_family_units.
        $editableUnitIds = $user->isAdmin()
            ? $familyUnits->pluck('id')->toArray()
            : ($user->isModerator()
                ? \Illuminate\Support\Facades\DB::table('moderator_family_units')
                    ->where('user_id', $user->id)
                    ->pluck('family_unit_id')
                    ->toArray()
                : []);

        return Inertia::render('FamilyUnits/Index', [
            'familyUnits' => $familyUnits,
            'editableUnitIds' => $editableUnitIds,
            'can' => [
                'create' => $user->isAdmin(),
                'edit' => $user->isAdmin() || $user->isModerator(),
                'delete' => $user->isAdmin(),
            ],
        ]);
    }

    public function show(FamilyUnit $familyUnit)
    {
        // Semua user bisa lihat detail unit keluarga
        $this->authorize('view', $familyUnit);

        $members = $familyUnit->people()
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'gender', 'birth_date', 'birth_accuracy', 'status', 'family_unit_id']);

        // Relationships antar anggota di unit ini
        $memberIds = $members->pluck('id');

        $relationships = \App\Models\Relationship::with([
            'subject:id,display_name,gender',
            'object:id,display_name,gender',
        ])
            ->whereIn('subject_id', $memberIds)
            ->whereIn('object_id', $memberIds)
            ->whereIn('status', ['approved', 'pending'])
            ->whereNull('ended_at')
            ->get();

        $user = auth()->user();

        // manage_relations hanya untuk:
        // - Admin: semua unit
        // - Moderator: hanya unit yang mereka naungi (family_unit_id cocok)
        $canManageRelations = $user->isAdmin()
            || ($user->isModerator() && $user->managesUnit($familyUnit->id));

        return Inertia::render('FamilyUnits/Show', [
            'familyUnit' => $familyUnit->load('moderator:id,name'),
            'members' => $members,
            'relationships' => $relationships,
            'can' => [
                'edit' => $user->can('update', $familyUnit),
                'delete' => $user->isAdmin(),
                'manage_relations' => $canManageRelations,
                // Tambah anggota internal: admin + moderator ter-assign ke unit ini saja
                'add_member' => $user->isAdmin()
                    || ($user->isModerator() && $user->managesUnit($familyUnit->id)),
                // Moderator yang terikat dengan unit ini bisa mengajukan resign
                'can_resign' => $user->isModerator() && $user->managesUnit($familyUnit->id),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', FamilyUnit::class);

        $moderators = \App\Models\User::role('moderator')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('FamilyUnits/Create', [
            'moderators' => $moderators,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', FamilyUnit::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:family_units,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'moderator_ids' => ['nullable', 'array', 'max:3'],
            'moderator_ids.*' => ['exists:users,id'],
        ], [
            'name.required' => 'Nama unit keluarga wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.unique' => 'Nama unit keluarga sudah digunakan.',
            'moderator_ids.max' => 'Maksimal 3 moderator per unit.',
        ]);

        $familyUnit = FamilyUnit::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'moderator_id' => ! empty($data['moderator_ids']) ? $data['moderator_ids'][0] : null,
        ]);

        // Assign semua moderator yang dipilih ke pivot table
        $moderatorIds = $data['moderator_ids'] ?? [];
        foreach ($moderatorIds as $modId) {
            $mod = \App\Models\User::find($modId);
            if ($mod && $mod->managedFamilyUnits()->count() < 3) {
                $mod->managedFamilyUnits()->syncWithoutDetaching([$familyUnit->id]);
            }
        }

        return redirect()->route('family-units.index')
            ->with('message', "Unit keluarga \"{$familyUnit->name}\" berhasil dibuat.");
    }

    public function edit(FamilyUnit $familyUnit)
    {
        $this->authorize('update', $familyUnit);

        $moderators = \App\Models\User::role('moderator')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('FamilyUnits/Edit', [
            'familyUnit' => $familyUnit,
            'moderators' => $moderators,
            'currentModeratorIds' => $familyUnit->assignedModerators()->pluck('users.id')->toArray(),
        ]);
    }

    public function update(Request $request, FamilyUnit $familyUnit)
    {
        $this->authorize('update', $familyUnit);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:family_units,name,'.$familyUnit->id],
            'description' => ['nullable', 'string', 'max:500'],
            'moderator_ids' => ['nullable', 'array', 'max:3'],
            'moderator_ids.*' => ['exists:users,id'],
        ], [
            'moderator_ids.max' => 'Maksimal 3 moderator per unit.',
        ]);

        $newModIds = $data['moderator_ids'] ?? [];
        $oldModIds = $familyUnit->assignedModerators()->pluck('users.id')->toArray();
        $removedIds = array_diff($oldModIds, $newModIds);
        $addedIds = array_diff($newModIds, $oldModIds);

        // Cek apakah ada perubahan aktual
        sort($newModIds);
        sort($oldModIds);
        $noChange = $familyUnit->name === $data['name']
                 && ($familyUnit->description ?? '') === ($data['description'] ?? '')
                 && $newModIds === $oldModIds;

        if ($noChange) {
            return back()->with('message', 'Tidak ada perubahan yang terdeteksi.');
        }

        // Lepas moderator yang dihapus
        foreach ($removedIds as $modId) {
            $mod = \App\Models\User::find($modId);
            $mod?->managedFamilyUnits()->detach($familyUnit->id);
        }

        $familyUnit->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'moderator_id' => ! empty($newModIds) ? $newModIds[0] : null,
        ]);

        // Assign moderator baru
        foreach ($addedIds as $modId) {
            $mod = \App\Models\User::find($modId);
            if ($mod && $mod->managedFamilyUnits()->count() < 3) {
                $mod->managedFamilyUnits()->syncWithoutDetaching([$familyUnit->id]);
            }
        }

        return redirect()->route('family-units.index')
            ->with('message', "Unit keluarga \"{$familyUnit->name}\" berhasil diperbarui.");
    }

    public function destroy(FamilyUnit $familyUnit)
    {
        $this->authorize('delete', $familyUnit);

        if ($familyUnit->people()->exists()) {
            return back()->withErrors(['error' => 'Unit keluarga tidak bisa dihapus karena masih memiliki anggota.']);
        }

        $name = $familyUnit->name;
        $familyUnit->delete();

        return redirect()->route('family-units.index')
            ->with('message', "Unit keluarga \"{$name}\" berhasil dihapus.");
    }

    /**
     * Buat unit keluarga baru secara inline dari wizard form (admin only).
     * Dipanggil via axios dari StepFamily.vue — return JSON bukan Inertia redirect.
     */
    public function storeInline(Request $request)
    {
        $this->authorize('create', FamilyUnit::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:family_units,name'],
        ], [
            'name.required' => 'Nama unit keluarga wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.unique' => 'Nama unit keluarga sudah digunakan.',
        ]);

        $familyUnit = FamilyUnit::create(['name' => $data['name']]);

        return response()->json([
            'id' => $familyUnit->id,
            'name' => $familyUnit->name,
        ], 201);
    }
}
