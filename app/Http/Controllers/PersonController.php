<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\FamilyUnit;
use App\Models\Person;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PersonController extends Controller
{
    use AuthorizesRequests;

    // ─── LIST ──────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Person::with(['familyUnit', 'creator:id,name']);

        $allowedStatuses = ['active', 'archived'];

        if ($request->filled('status') && in_array($request->status, $allowedStatuses)) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('search')) {
            $query->where('display_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $people = $query->orderBy('display_name')->paginate(20)->withQueryString();

        return Inertia::render('People/Index', [
            'people' => $people,
            'filters' => $request->only(['search', 'status', 'gender']),
            'draftCount' => Person::where('status', 'draft')
                ->where('created_by', $user->id)
                ->count(),
            'can' => [
                'create' => $user->can('create', Person::class),
                'edit' => $user->isAdmin() || $user->isModerator(),
                'delete' => $user->isAdmin() || $user->isModerator(),
                'bulk_delete' => $user->isAdmin() || $user->isModerator(),
            ],
        ]);
    }

    // ─── DRAFTS PAGE ───────────────────────────────────────────────────────

    public function drafts()
    {
        $user = auth()->user();
        $drafts = Person::with(['familyUnit'])
            ->where('status', 'draft')
            ->where('created_by', $user->id)
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('People/Drafts', [
            'drafts' => $drafts,
            'can' => ['edit' => true, 'delete' => $user->isAdmin() || $user->isModerator()],
        ]);
    }

    // ─── SHOW ──────────────────────────────────────────────────────────────

    public function show(Person $person)
    {
        $this->authorize('view', $person);

        $auditLogs = AuditLog::where('auditable_type', Person::class)
            ->where('auditable_id', $person->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $user = auth()->user();

        $relationshipsRaw = \App\Models\Relationship::with([
            'subject:id,display_name,gender,birth_date',
            'object:id,display_name,gender,birth_date',
        ])
            ->where(fn ($q) => $q->where('subject_id', $person->id)->orWhere('object_id', $person->id))
            ->whereIn('status', ['approved', 'pending'])
            ->whereNull('ended_at')
            ->orderBy('type')
            ->get();

        $parents = $children = $spouses = $pending = [];

        foreach ($relationshipsRaw as $rel) {
            $entry = [
                'id' => $rel->id,
                'type' => $rel->type,
                'is_biological' => $rel->is_biological,
                'status' => $rel->status,
                'started_at' => $rel->started_at?->toDateString(),
                'ended_at' => $rel->ended_at?->toDateString(),
                'ended_reason' => $rel->ended_reason,
            ];

            if ($rel->status === 'pending') {
                if ($user->isAdmin() || $user->isModerator()) {
                    $related = $rel->subject_id === $person->id ? $rel->object : $rel->subject;
                    $pending[] = array_merge($entry, ['person' => $related]);
                }

                continue;
            }

            if (in_array($rel->type, ['parent', 'step_parent', 'adopted_parent'])) {
                if ($rel->object_id === $person->id) {
                    $parents[] = array_merge($entry, ['person' => $rel->subject]);
                } else {
                    $children[] = array_merge($entry, ['person' => $rel->object]);
                }
            } elseif ($rel->type === 'spouse') {
                $related = $rel->subject_id === $person->id ? $rel->object : $rel->subject;
                $spouses[] = array_merge($entry, ['person' => $related]);
            }
        }

        $peoplelist = Person::where('id', '!=', $person->id)
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'gender', 'birth_date']);

        // Cari approval pending milik user ini untuk person ini
        // Digunakan agar tombol "Batalkan Pengajuan" hanya muncul jika ada
        $pendingApproval = Approval::where('approvable_type', Person::class)
            ->where('approvable_id', $person->id)
            ->where('requested_by', $user->id)
            ->where('status', 'pending')
            ->whereIn('action', ['create', 'update', 'delete'])
            ->latest()
            ->first();

        return Inertia::render('People/Show', [
            'person' => $person->load(['familyUnit', 'creator']),
            'auditLogs' => $auditLogs,
            'relationships' => compact('parents', 'children', 'spouses', 'pending'),
            'peopleList' => $peoplelist,
            'pendingApprovalId' => $pendingApproval?->id,
            'can' => [
                'edit' => $user->can('update', $person),
                'delete' => $user->can('delete', $person),
                'manage_relations' => $user->can('create', [\App\Models\Relationship::class, $person]),
                'approve_relations' => $user->isAdmin(),
                // Tombol batalkan pengajuan: muncul jika ada approval pending milik user ini
                'cancel_approval' => $pendingApproval !== null,
            ],
        ]);
    }

    // ─── CREATE FORM ───────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $this->authorize('create', Person::class);

        $familyUnits = FamilyUnit::orderBy('name')->get(['id', 'name']);
        $savedDraft = session('person_draft', null);

        // Ambil semua orang aktif untuk dropdown relasi di StepFamily
        // Frontend akan filter berdasarkan family_unit_id yang dipilih
        $allPeople = Person::where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'gender', 'birth_date', 'family_unit_id']);

        // Jika dibuka dari /people/create?family_unit_id={id}
        // (misalnya dari halaman Show unit keluarga), kunci unit keluarganya.
        // Ini juga memastikan approval yang dibuat moderator masuk ke unit yang benar
        // sehingga bisa terlihat di /approvals oleh moderator itu sendiri.
        $lockedFamilyUnitId = null;
        $lockedFamilyUnit = null;
        if ($request->filled('family_unit_id')) {
            $unit = FamilyUnit::find($request->family_unit_id);
            if ($unit) {
                $lockedFamilyUnitId = $unit->id;
                $lockedFamilyUnit = $unit->only(['id', 'name']);
            }
        }

        return Inertia::render('People/Create', [
            'familyUnits' => $familyUnits,
            'allPeople' => $allPeople,
            'savedDraft' => $savedDraft,
            'defaultFamilyUnitId' => $lockedFamilyUnitId ?? $familyUnits->first()?->id,
            'lockedFamilyUnitId' => $lockedFamilyUnitId,
            'lockedFamilyUnit' => $lockedFamilyUnit,
        ]);
    }

    // ─── STORE ─────────────────────────────────────────────────────────────

    public function store(StorePersonRequest $request)
    {
        $data = $request->validated();
        $isDraft = $data['is_draft'] ?? false;
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $status = $user->isAdmin() && ! $isDraft ? 'active'
                    : ($isDraft ? 'draft' : 'pending');

            $person = Person::create([
                'family_unit_id' => $data['family_unit_id'],
                'created_by' => $user->id,
                'status' => $status,
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'birth_accuracy' => $data['birth_accuracy'],
                'death_date' => $data['death_date'],
                'death_accuracy' => $data['death_accuracy'],
                'display_name' => $data['display_name'],
            ]);

            if (! $user->isAdmin() && ! $isDraft) {
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id' => $person->id,
                    'action' => 'create',
                    'changes' => [
                        'display_name' => ['old' => null, 'new' => $data['display_name']],
                        'gender' => ['old' => null, 'new' => $data['gender']],
                        'birth_date' => ['old' => null, 'new' => $data['birth_date']],
                        'birth_accuracy' => ['old' => null, 'new' => $data['birth_accuracy']],
                        'death_date' => ['old' => null, 'new' => $data['death_date']],
                        'death_accuracy' => ['old' => null, 'new' => $data['death_accuracy']],
                    ],
                    'status' => 'pending',
                    'requested_by' => $user->id,
                ]);
                NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
            }

            // Buat relasi awal bila ada (hanya untuk admin, langsung approved)
            // Non-admin: relasi diabaikan — relasi bisa ditambah setelah approved
            $initialRelations = $request->input('initial_relations', []);
            if ($user->isAdmin() && ! $isDraft && ! empty($initialRelations)) {
                foreach ($initialRelations as $rel) {
                    $relatedId = $rel['related_id'] ?? null;
                    $type = $rel['type'] ?? null;
                    if (! $relatedId || ! $type) {
                        continue;
                    }

                    [$subjectId, $objectId, $storedType] = match ($type) {
                        'parent', 'step_parent', 'adopted_parent' => [$relatedId, $person->id, $type],
                        'child', 'step_child', 'adopted_child' => [$person->id, $relatedId, match ($type) {
                            'step_child' => 'step_parent',
                            'adopted_child' => 'adopted_parent',
                            default => 'parent',
                        }],
                        default => [$person->id, $relatedId, 'spouse'],
                    };

                    // Skip bila sudah ada relasi serupa
                    $exists = \App\Models\Relationship::where('subject_id', $subjectId)
                        ->where('object_id', $objectId)
                        ->where('type', $storedType)
                        ->whereNull('ended_at')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Validasi bersama: same family unit + single active spouse.
                    // Wizard step 3 dijalankan dalam transaction yang sama dengan Person::create(),
                    // jadi kalau ini gagal, seluruh proses create person ikut di-rollback.
                    \App\Services\RelationshipValidator::assertValid(
                        $person,
                        Person::findOrFail($relatedId),
                        $storedType,
                        $subjectId,
                        $objectId
                    );

                    \App\Models\Relationship::create([
                        'subject_id' => $subjectId,
                        'object_id' => $objectId,
                        'type' => $storedType,
                        'is_biological' => $rel['is_biological'] ?? true,
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'created_by' => $user->id,
                    ]);
                }
            }

            AuditLog::record($person, $isDraft ? 'draft_saved' : 'created', [], [
                'display_name' => $data['display_name'],
                'status' => $status,
            ]);

            session()->forget('person_draft');
            DB::commit();

            $message = $isDraft ? 'Draft berhasil disimpan.'
                : ($user->isAdmin() ? 'Anggota berhasil ditambahkan.'
                    : 'Anggota berhasil diajukan dan menunggu persetujuan admin.');

            return redirect()->route('people.show', $person)->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── EDIT FORM ─────────────────────────────────────────────────────────

    public function edit(Person $person)
    {
        $this->authorize('update', $person);

        $familyUnits = FamilyUnit::orderBy('name')->get(['id', 'name']);
        $auditLogs = AuditLog::where('auditable_type', Person::class)
            ->where('auditable_id', $person->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('People/Edit', [
            'person' => $person->load(['familyUnit', 'creator']),
            'familyUnits' => $familyUnits,
            'auditLogs' => $auditLogs,
        ]);
    }

    // ─── UPDATE ────────────────────────────────────────────────────────────

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $data = $request->validated();
        $isDraft = $data['is_draft'] ?? false;
        $isDraftMode = $person->status === 'draft'; // person saat ini masih draft
        $trackableFields = ['display_name', 'gender', 'birth_date', 'birth_accuracy', 'death_date', 'death_accuracy'];
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $oldValues = array_map(
                fn ($v) => $v instanceof \Carbon\Carbon ? $v->toDateString() : $v,
                $person->only($trackableFields)
            );

            // ── MODE: SIMPAN DRAFT ─────────────────────────────────────────
            // is_draft=true → update fields, pertahankan status='draft'
            if ($isDraft) {
                $person->update(array_merge(
                    collect($data)->only($trackableFields)->all(),
                    ['status' => 'draft']
                ));
                AuditLog::record($person, 'draft_saved', $oldValues, collect($data)->only($trackableFields)->all());
                DB::commit();

                return redirect()->route('people.drafts')->with('message', 'Draft berhasil disimpan.');
            }

            // ── MODE: SIMPAN & AKTIFKAN DARI DRAFT ────────────────────────
            // is_draft=false, person.status='draft', admin → set active langsung
            // is_draft=false, person.status='draft', moderator → buat approval 'create', set pending
            if ($isDraftMode) {
                if ($user->isAdmin()) {
                    $person->update(array_merge(
                        collect($data)->only($trackableFields)->all(),
                        ['status' => 'active']
                    ));
                    AuditLog::record($person, 'created', $oldValues, collect($data)->only($trackableFields)->all());
                    $message = 'Data berhasil dipublikasikan.';
                } else {
                    // Moderator: update fields ke form terbaru, set pending, buat approval 'create'
                    $person->update(array_merge(
                        collect($data)->only($trackableFields)->all(),
                        ['status' => 'pending']
                    ));
                    $approval = Approval::create([
                        'approvable_type' => Person::class,
                        'approvable_id' => $person->id,
                        'action' => 'create',
                        'changes' => array_map(
                            fn ($field) => ['old' => null, 'new' => $data[$field] ?? null],
                            array_combine($trackableFields, $trackableFields)
                        ),
                        'status' => 'pending',
                        'requested_by' => $user->id,
                    ]);
                    AuditLog::record($person, 'update_requested', $oldValues, collect($data)->only($trackableFields)->all());
                    NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
                    $message = 'Data diajukan dan menunggu persetujuan admin.';
                }

                DB::commit();

                return redirect()->route('people.show', $person)->with('message', $message);
            }

            // ── MODE: EDIT BIASA ──────────────────────────────────────────
            // Person sudah active/archived, cek apakah ada perubahan
            $changes = [];
            foreach ($trackableFields as $field) {
                $old = $oldValues[$field];
                $new = $data[$field] ?? null;
                if ($old !== $new) {
                    $changes[$field] = ['old' => $old, 'new' => $new];
                }
            }

            if (empty($changes)) {
                DB::rollBack();

                return back()->with('message', 'Tidak ada perubahan yang terdeteksi.');
            }

            if ($user->isAdmin()) {
                $person->update(collect($data)->only($trackableFields)->all());
                AuditLog::record($person, 'updated', $oldValues, collect($data)->only($trackableFields)->all());
                $message = 'Data anggota berhasil diperbarui.';
            } else {
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id' => $person->id,
                    'action' => 'update',
                    'changes' => $changes,
                    'status' => 'pending',
                    'requested_by' => $user->id,
                ]);
                AuditLog::record($person, 'update_requested', $oldValues, array_map(fn ($c) => $c['new'], $changes));
                NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
                $message = 'Permintaan perubahan diajukan dan menunggu persetujuan admin.';
            }

            DB::commit();

            return redirect()->route('people.show', $person)->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── DELETE ────────────────────────────────────────────────────────────

    public function destroy(Request $request, Person $person)
    {
        $this->authorize('delete', $person);

        // Draft bisa dihapus tanpa alasan
        if ($person->status === 'draft') {
            $person->delete();

            return redirect()->route('people.drafts')->with('message', 'Draft berhasil dihapus.');
        }

        $request->validate(['reason' => ['required', 'string', 'min:5']]);
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $oldValues = $person->toArray();

            if ($user->isAdmin()) {
                AuditLog::record($person, 'deleted', $oldValues, [
                    'deleted_by' => $user->name,
                    'reason' => $request->reason,
                ]);
                $person->delete();
                DB::commit();

                return redirect()->route('people.index')->with('message', 'Anggota berhasil dihapus.');
            } else {
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id' => $person->id,
                    'action' => 'delete',
                    'changes' => ['reason' => ['old' => null, 'new' => $request->reason]],
                    'status' => 'pending',
                    'requested_by' => $user->id,
                ]);
                AuditLog::record($person, 'delete_requested', $oldValues, ['reason' => $request->reason]);
                NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
                DB::commit();

                return redirect()->route('people.index')
                    ->with('message', 'Permintaan penghapusan diajukan dan menunggu persetujuan admin.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── BULK DELETE ───────────────────────────────────────────────────────

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:people,id'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $user = auth()->user();
        $people = Person::whereIn('id', $request->ids)->get();
        $deleted = $pending = 0;

        DB::beginTransaction();
        try {
            foreach ($people as $person) {
                if (! $user->can('delete', $person)) {
                    continue;
                }

                if ($user->isAdmin()) {
                    AuditLog::record($person, 'deleted', $person->toArray(), [
                        'deleted_by' => $user->name, 'reason' => $request->reason, 'bulk' => true,
                    ]);
                    $person->delete();
                    $deleted++;
                } else {
                    Approval::create([
                        'approvable_type' => Person::class,
                        'approvable_id' => $person->id,
                        'action' => 'delete',
                        'changes' => ['reason' => ['old' => null, 'new' => $request->reason]],
                        'status' => 'pending',
                        'requested_by' => $user->id,
                    ]);
                    AuditLog::record($person, 'delete_requested', $person->toArray(), [
                        'reason' => $request->reason, 'bulk' => true,
                    ]);
                    $pending++;
                }
            }

            DB::commit();
            $message = $user->isAdmin()
                ? "{$deleted} anggota berhasil dihapus."
                : "Permintaan penghapusan {$pending} anggota diajukan dan menunggu persetujuan admin.";

            return redirect()->route('people.index')->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── DRAFT AUTO-SAVE ───────────────────────────────────────────────────

    public function saveDraft(Request $request)
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,unknown'],
            'birth_date' => ['nullable', 'date'],
            'birth_accuracy' => ['nullable', 'in:exact,year_month,year,unknown'],
            'death_date' => ['nullable', 'date'],
            'death_accuracy' => ['nullable', 'in:exact,year_month,year,unknown'],
            'family_unit_id' => ['nullable', 'exists:family_units,id'],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        session(['person_draft' => array_merge($data, ['saved_at' => now()->toIso8601String()])]);

        return response()->json(['saved' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function clearDraft()
    {
        session()->forget('person_draft');

        return response()->json(['cleared' => true]);
    }

    // ─── DUPLICATE DETECTION ───────────────────────────────────────────────

    public function checkDuplicates(Request $request)
    {
        $request->validate([
            'display_name' => ['required', 'string', 'min:2'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,unknown'],
            'family_unit_id' => ['nullable', 'exists:family_units,id'],
            'exclude_id' => ['nullable', 'exists:people,id'],
        ]);

        $name = $request->display_name;
        $familyId = $request->family_unit_id;
        $birthDate = $request->birth_date;
        $excludeId = $request->exclude_id;

        $base = fn () => Person::where('status', '!=', 'archived')
            ->when($familyId, fn ($q) => $q->where('family_unit_id', $familyId))
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        $exactMatches = $base()->whereRaw('LOWER(display_name) = LOWER(?)', [$name])
            ->get(['id', 'display_name', 'gender', 'birth_date', 'status']);
        $similarMatches = $base()->whereRaw('LOWER(display_name) LIKE LOWER(?)', ["%{$name}%"])
            ->whereRaw('LOWER(display_name) != LOWER(?)', [$name])
            ->limit(5)->get(['id', 'display_name', 'gender', 'birth_date', 'status']);
        $birthDateMatches = $birthDate
            ? $base()->where('birth_date', $birthDate)->get(['id', 'display_name', 'gender', 'birth_date', 'status'])
            : collect();

        $risk = 'none';
        if ($exactMatches->isNotEmpty() && $birthDateMatches->isNotEmpty()) {
            $risk = 'high';
        } elseif ($exactMatches->isNotEmpty() || $birthDateMatches->isNotEmpty()) {
            $risk = 'medium';
        } elseif ($similarMatches->isNotEmpty()) {
            $risk = 'low';
        }

        return response()->json([
            'risk' => $risk,
            'exact_matches' => $exactMatches,
            'similar_matches' => $similarMatches,
            'birth_date_matches' => $birthDateMatches,
        ]);
    }
}
