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
        $familyUnits = FamilyUnit::with('moderator:id,name')
            ->withCount('people')
            ->orderBy('name')
            ->get();

        return Inertia::render('FamilyUnits/Index', [
            'familyUnits' => $familyUnits,
            'can' => [
                'create' => auth()->user()->isAdmin(),
                'edit' => auth()->user()->isAdmin(),
                'delete' => auth()->user()->isAdmin(),
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

        return Inertia::render('FamilyUnits/Show', [
            'familyUnit' => $familyUnit->load('moderator:id,name'),
            'members' => $members,
            'relationships' => $relationships,
            'can' => [
                'edit' => auth()->user()->isAdmin(),
                'delete' => auth()->user()->isAdmin(),
                'manage_relations' => auth()->user()->isAdmin() || auth()->user()->isModerator(),
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
            'moderator_id' => ['nullable', 'exists:users,id'],
        ], [
            'name.required' => 'Nama unit keluarga wajib diisi.',
            'name.min' => 'Nama minimal 2 karakter.',
            'name.unique' => 'Nama unit keluarga sudah digunakan.',
        ]);

        $familyUnit = FamilyUnit::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'moderator_id' => $data['moderator_id'] ?? null,
        ]);

        // Assign moderator ke unit ini jika dipilih
        if (! empty($data['moderator_id'])) {
            \App\Models\User::where('id', $data['moderator_id'])
                ->update(['family_unit_id' => $familyUnit->id]);
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
        ]);
    }

    public function update(Request $request, FamilyUnit $familyUnit)
    {
        $this->authorize('update', $familyUnit);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:family_units,name,'.$familyUnit->id],
            'description' => ['nullable', 'string', 'max:500'],
            'moderator_id' => ['nullable', 'exists:users,id'],
        ]);

        // Cek apakah ada perubahan aktual
        $noChange = $familyUnit->name === $data['name']
            && ($familyUnit->description ?? '') === ($data['description'] ?? '')
            && (string) ($familyUnit->moderator_id ?? '') === (string) ($data['moderator_id'] ?? '');

        if ($noChange) {
            return back()->with('message', 'Tidak ada perubahan yang terdeteksi.');
        }

        // Lepas moderator lama dari unit ini
        if ($familyUnit->moderator_id && $familyUnit->moderator_id !== ($data['moderator_id'] ?? null)) {
            \App\Models\User::where('id', $familyUnit->moderator_id)
                ->where('family_unit_id', $familyUnit->id)
                ->update(['family_unit_id' => null]);
        }

        $familyUnit->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'moderator_id' => $data['moderator_id'] ?? null,
        ]);

        // Assign moderator baru
        if (! empty($data['moderator_id'])) {
            \App\Models\User::where('id', $data['moderator_id'])
                ->update(['family_unit_id' => $familyUnit->id]);
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
