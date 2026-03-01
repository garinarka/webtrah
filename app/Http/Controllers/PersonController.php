<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\AuditLog;
use App\Models\Approval;
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

    // LIST

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Person::with(['familyUnit', 'creator']);

        // user biasa hanya lihat record yang dia buat
        if ($user->isUser() && !$user->isAdmin() && !$user->isModerator()) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('search')) {
            $query->where('display_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $people = $query->orderBy('display_name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('People/Index', [
            'people'  => $people,
            'filters' => $request->only(['search', 'status', 'gender']),
            'can'     => [
                'create'       => $user->can('create', Person::class),
                'edit'         => $user->isAdmin() || $user->isModerator(),
                'delete'       => $user->isAdmin() || $user->isModerator(),
                'bulk_delete'  => $user->isAdmin() || $user->isModerator(),
            ],
        ]);
    }

    // SHOW

    public function show(Person $person)
    {
        $this->authorize('view', $person);

        $auditLogs = AuditLog::where('auditable_type', Person::class)
            ->where('auditable_id', $person->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // relasi — semua yang approved (aktif) maupun pending (untuk ditampilkan ke admin/mod)
        $user = auth()->user();

        $relationshipsRaw = \App\Models\Relationship::with(['subject:id,display_name,gender,birth_date', 'object:id,display_name,gender,birth_date'])
            ->where(fn($q) => $q->where('subject_id', $person->id)->orWhere('object_id', $person->id))
            ->whereIn('status', ['approved', 'pending'])
            ->whereNull('ended_at')
            ->orderBy('type')
            ->get();

        // kelompokkan jadi parents, children, spouses dari sudut pandang person ini
        $parents   = [];
        $children  = [];
        $spouses   = [];
        $pending   = [];

        foreach ($relationshipsRaw as $rel) {
            $entry = [
                'id'           => $rel->id,
                'type'         => $rel->type,
                'is_biological' => $rel->is_biological,
                'status'       => $rel->status,
                'started_at'   => $rel->started_at?->toDateString(),
                'ended_at'     => $rel->ended_at?->toDateString(),
                'ended_reason' => $rel->ended_reason,
            ];

            if ($rel->status === 'pending') {
                // tampilkan pending hanya ke admin/moderator
                if ($user->isAdmin() || $user->isModerator()) {
                    $related = $rel->subject_id === $person->id ? $rel->object : $rel->subject;
                    $pending[] = array_merge($entry, ['person' => $related]);
                }
                continue;
            }

            // approved — kelompokkan berdasarkan type & posisi
            if (in_array($rel->type, ['parent', 'step_parent', 'adopted_parent'])) {
                if ($rel->object_id === $person->id) {
                    // subject adalah orang tua dari person ini
                    $parents[] = array_merge($entry, ['person' => $rel->subject]);
                } else {
                    // person ini adalah orang tua dari object
                    $children[] = array_merge($entry, ['person' => $rel->object]);
                }
            } elseif ($rel->type === 'spouse') {
                $related   = $rel->subject_id === $person->id ? $rel->object : $rel->subject;
                $spouses[] = array_merge($entry, ['person' => $related]);
            }
        }

        // daftar semua orang aktif untuk dropdown tambah relasi
        $peoplelist = Person::where('id', '!=', $person->id)
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'gender', 'birth_date']);

        return Inertia::render('People/Show', [
            'person'      => $person->load(['familyUnit', 'creator']),
            'auditLogs'   => $auditLogs,
            'relationships' => compact('parents', 'children', 'spouses', 'pending'),
            'peopleList'  => $peoplelist,
            'can'         => [
                'edit'              => $user->can('update', $person),
                'delete'            => $user->can('delete', $person),
                'manage_relations'  => $user->isAdmin() || $user->isModerator(),
                'approve_relations' => $user->isAdmin(),
            ],
        ]);
    }

    // CREATE FORM

    public function create()
    {
        $this->authorize('create', Person::class);

        $familyUnits = FamilyUnit::orderBy('name')->get(['id', 'name']);
        $savedDraft  = session('person_draft', null);

        return Inertia::render('People/Create', [
            'familyUnits'         => $familyUnits,
            'savedDraft'          => $savedDraft,
            'defaultFamilyUnitId' => $familyUnits->first()?->id,
        ]);
    }

    // STORE

    public function store(StorePersonRequest $request)
    {
        $data    = $request->validated();
        $isDraft = $data['is_draft'] ?? false;
        $user    = auth()->user();

        DB::beginTransaction();
        try {
            // admin: langsung active tanpa approval
            // moderator / user: masuk pending, butuh approval
            if ($user->isAdmin() && !$isDraft) {
                $status = 'active';
            } else {
                $status = $isDraft ? 'draft' : 'pending';
            }

            $person = Person::create([
                'family_unit_id' => $data['family_unit_id'],
                'created_by'     => $user->id,
                'status'         => $status,
                'gender'         => $data['gender'],
                'birth_date'     => $data['birth_date'],
                'birth_accuracy' => $data['birth_accuracy'],
                'death_date'     => $data['death_date'],
                'death_accuracy' => $data['death_accuracy'],
                'display_name'   => $data['display_name'],
            ]);

            // buat approval request hanya jika bukan admin dan bukan draft
            if (!$user->isAdmin() && !$isDraft) {
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id'   => $person->id,
                    'action'          => 'create',
                    'changes'         => [
                        'display_name'   => ['old' => null, 'new' => $data['display_name']],
                        'gender'         => ['old' => null, 'new' => $data['gender']],
                        'birth_date'     => ['old' => null, 'new' => $data['birth_date']],
                        'birth_accuracy' => ['old' => null, 'new' => $data['birth_accuracy']],
                        'death_date'     => ['old' => null, 'new' => $data['death_date']],
                        'death_accuracy' => ['old' => null, 'new' => $data['death_accuracy']],
                    ],
                    'status'       => 'pending',
                    'requested_by' => $user->id,
                ]);
                NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
            }

            AuditLog::record($person, $isDraft ? 'draft_saved' : 'created', [], [
                'display_name' => $data['display_name'],
                'gender'       => $data['gender'],
                'status'       => $status,
            ]);

            session()->forget('person_draft');
            DB::commit();

            if ($isDraft) {
                $message = 'Draft berhasil disimpan.';
            } elseif ($user->isAdmin()) {
                $message = 'Anggota berhasil ditambahkan.';
            } else {
                $message = 'Anggota berhasil diajukan dan menunggu persetujuan admin.';
            }

            return redirect()->route('people.show', $person)->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // EDIT FORM

    public function edit(Person $person)
    {
        $this->authorize('update', $person);

        $familyUnits = FamilyUnit::orderBy('name')->get(['id', 'name']);
        $auditLogs   = AuditLog::where('auditable_type', Person::class)
            ->where('auditable_id', $person->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('People/Edit', [
            'person'      => $person->load(['familyUnit', 'creator']),
            'familyUnits' => $familyUnits,
            'auditLogs'   => $auditLogs,
        ]);
    }

    // UPDATE

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $data            = $request->validated();
        $trackableFields = ['display_name', 'gender', 'birth_date', 'birth_accuracy', 'death_date', 'death_accuracy'];
        $changes         = [];
        $user            = auth()->user();

        foreach ($trackableFields as $field) {
            $old = $person->{$field} instanceof \Carbon\Carbon
                ? $person->{$field}->toDateString()
                : $person->{$field};
            $new = $data[$field] ?? null;
            if ($old !== $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if (empty($changes)) {
            return back()->with('message', 'Tidak ada perubahan yang terdeteksi.');
        }

        DB::beginTransaction();
        try {
            $oldValues = array_map(
                fn($v) => $v instanceof \Carbon\Carbon ? $v->toDateString() : $v,
                $person->only($trackableFields)
            );

            if ($user->isAdmin()) {
                // admin: langsung update, tidak perlu approval
                $person->update(collect($data)->only($trackableFields)->all());
                AuditLog::record($person, 'updated', $oldValues, collect($data)->only($trackableFields)->all());
                $message = 'Data anggota berhasil diperbarui.';
            } else {
                // moderator & user: buat approval request, data tidak langsung berubah
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id'   => $person->id,
                    'action'          => 'update',
                    'changes'         => $changes,
                    'status'          => 'pending',
                    'requested_by'    => $user->id,
                ]);
                AuditLog::record($person, 'update_requested', $oldValues, array_map(fn($c) => $c['new'], $changes));
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

    // DELETE

    public function destroy(Request $request, Person $person)
    {
        $this->authorize('delete', $person);
        $request->validate(['reason' => ['required', 'string', 'min:5']]);

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $oldValues = $person->toArray();

            if ($user->isAdmin()) {
                // admin: langsung hapus
                AuditLog::record($person, 'deleted', $oldValues, [
                    'deleted_by' => $user->name,
                    'reason'     => $request->reason,
                ]);
                $person->delete();
                DB::commit();
                return redirect()->route('people.index')->with('message', 'Anggota berhasil dihapus.');
            } else {
                // moderator: ajukan penghapusan, tunggu persetujuan admin
                $approval = Approval::create([
                    'approvable_type' => Person::class,
                    'approvable_id'   => $person->id,
                    'action'          => 'delete',
                    'changes'         => ['reason' => ['old' => null, 'new' => $request->reason]],
                    'status'          => 'pending',
                    'requested_by'    => $user->id,
                ]);
                AuditLog::record($person, 'delete_requested', $oldValues, ['reason' => $request->reason]);
                NotificationService::notifyAdminsOfNewApproval($approval->load(['approvable', 'requester']));
                DB::commit();
                return redirect()->route('people.index')->with('message', 'Permintaan penghapusan diajukan dan menunggu persetujuan admin.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // BULK DELETE

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['exists:people,id'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $user    = auth()->user();
        $people  = Person::whereIn('id', $request->ids)->get();
        $deleted = 0;
        $pending = 0;

        DB::beginTransaction();
        try {
            foreach ($people as $person) {
                if (!$user->can('delete', $person)) continue;

                if ($user->isAdmin()) {
                    AuditLog::record($person, 'deleted', $person->toArray(), [
                        'deleted_by' => $user->name,
                        'reason'     => $request->reason,
                        'bulk'       => true,
                    ]);
                    $person->delete();
                    $deleted++;
                } else {
                    Approval::create([
                        'approvable_type' => Person::class,
                        'approvable_id'   => $person->id,
                        'action'          => 'delete',
                        'changes'         => ['reason' => ['old' => null, 'new' => $request->reason]],
                        'status'          => 'pending',
                        'requested_by'    => $user->id,
                    ]);
                    AuditLog::record($person, 'delete_requested', $person->toArray(), [
                        'reason' => $request->reason,
                        'bulk'   => true,
                    ]);
                    $pending++;
                }
            }

            DB::commit();

            if ($user->isAdmin()) {
                $message = "{$deleted} anggota berhasil dihapus.";
            } else {
                $message = "Permintaan penghapusan {$pending} anggota diajukan dan menunggu persetujuan admin.";
            }

            return redirect()->route('people.index')->with('message', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // DRAFT AUTO-SAVE

    public function saveDraft(Request $request)
    {
        $data = $request->validate([
            'display_name'   => ['nullable', 'string', 'max:255'],
            'gender'         => ['nullable', 'in:male,female,unknown'],
            'birth_date'     => ['nullable', 'date'],
            'birth_accuracy' => ['nullable', 'in:exact,year_month,year,unknown'],
            'death_date'     => ['nullable', 'date'],
            'death_accuracy' => ['nullable', 'in:exact,year_month,year,unknown'],
            'family_unit_id' => ['nullable', 'exists:family_units,id'],
            'current_step'   => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        session(['person_draft' => array_merge($data, ['saved_at' => now()->toIso8601String()])]);

        return response()->json(['saved' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function clearDraft()
    {
        session()->forget('person_draft');
        return response()->json(['cleared' => true]);
    }

    // DUPLICATE DETECTION

    public function checkDuplicates(Request $request)
    {
        $request->validate([
            'display_name'   => ['required', 'string', 'min:2'],
            'birth_date'     => ['nullable', 'date'],
            'gender'         => ['nullable', 'in:male,female,unknown'],
            'family_unit_id' => ['nullable', 'exists:family_units,id'],
            'exclude_id'     => ['nullable', 'exists:people,id'],
        ]);

        $name      = $request->display_name;
        $familyId  = $request->family_unit_id;
        $birthDate = $request->birth_date;
        $excludeId = $request->exclude_id;

        $base = fn() => Person::where('status', '!=', 'archived')
            ->when($familyId, fn($q) => $q->where('family_unit_id', $familyId))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId));

        $exactMatches    = $base()->whereRaw('LOWER(display_name) = LOWER(?)', [$name])
            ->get(['id', 'display_name', 'gender', 'birth_date', 'status']);
        $similarMatches  = $base()->whereRaw('LOWER(display_name) LIKE LOWER(?)', ["%{$name}%"])
            ->whereRaw('LOWER(display_name) != LOWER(?)', [$name])
            ->limit(5)->get(['id', 'display_name', 'gender', 'birth_date', 'status']);
        $birthDateMatches = $birthDate
            ? $base()->where('birth_date', $birthDate)->get(['id', 'display_name', 'gender', 'birth_date', 'status'])
            : collect();

        $hasExact     = $exactMatches->isNotEmpty();
        $hasBirthDate = $birthDateMatches->isNotEmpty();
        $hasSimilar   = $similarMatches->isNotEmpty();

        $risk = 'none';
        if ($hasExact && $hasBirthDate) $risk = 'high';
        elseif ($hasExact || $hasBirthDate) $risk = 'medium';
        elseif ($hasSimilar) $risk = 'low';

        return response()->json([
            'risk'               => $risk,
            'exact_matches'      => $exactMatches,
            'similar_matches'    => $similarMatches,
            'birth_date_matches' => $birthDateMatches,
        ]);
    }
}
