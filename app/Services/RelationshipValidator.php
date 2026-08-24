<?php

namespace App\Services;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Validation\ValidationException;

/**
 * Validasi bersama untuk SEMUA entry point pembuatan relasi:
 * - RelationshipController::store() (dipakai oleh halaman detail person & detail unit keluarga)
 * - PersonController::store() (relasi awal di wizard step 3)
 *
 * Wajib dipanggil SEBELUM Relationship::create() di masing-masing tempat.
 */
class RelationshipValidator
{
    /**
     * Pastikan kedua person berada di unit keluarga yang sama.
     * Relasi lintas unit keluarga tidak diperbolehkan.
     */
    public static function assertSameFamilyUnit(Person $subject, Person $object): void
    {
        if ($subject->family_unit_id !== $object->family_unit_id) {
            throw ValidationException::withMessages([
                'related_id' => 'Relasi hanya bisa dibuat antar anggota dalam unit keluarga yang sama.',
            ]);
        }
    }

    /**
     * Pastikan subject & object belum sama-sama punya pasangan aktif lain.
     * "Aktif" = status approved/pending, belum diakhiri (ended_at null).
     * Hanya berlaku untuk type 'spouse'.
     */
    public static function assertSingleActiveSpouse(string $type, string $subjectId, string $objectId): void
    {
        if ($type !== 'spouse') {
            return;
        }

        foreach ([$subjectId, $objectId] as $personId) {
            $hasActiveSpouse = Relationship::where('type', 'spouse')
                ->where(function ($q) use ($personId) {
                    $q->where('subject_id', $personId)->orWhere('object_id', $personId);
                })
                ->whereIn('status', ['approved', 'pending'])
                ->whereNull('ended_at')
                ->exists();

            if ($hasActiveSpouse) {
                $person = Person::find($personId);
                throw ValidationException::withMessages([
                    'related_id' => ($person?->display_name ?? 'Salah satu person').' sudah punya relasi pasangan aktif. Akhiri relasi tersebut terlebih dahulu.',
                ]);
            }
        }
    }

    /**
     * Opsi A (cascade): akhiri otomatis semua relasi pasangan AKTIF milik $person
     * saat person tsb dihapus. Dipanggil di titik final penghapusan person:
     * - PersonController::destroy() (admin, langsung)
     * - PersonController::bulkDestroy() (admin, langsung)
     * - ApprovalController::approve() saat action 'delete' disetujui (moderator)
     *
     * "Aktif" = type spouse, status approved/pending, ended_at masih null.
     * Reason dicatat 'disownment' (nilai enum yang sama dipakai utk pemutusan
     * paksa non-alami lain di sistem, lihat ApprovalController::approve()
     * action 'delete_relation').
     *
     * @return array<string> ID relasi yang diakhiri (untuk AuditLog)
     */
    public static function cascadeEndSpouseRelationships(Person $person): array
    {
        $relationships = Relationship::where('type', 'spouse')
            ->where(function ($q) use ($person) {
                $q->where('subject_id', $person->id)->orWhere('object_id', $person->id);
            })
            ->whereIn('status', ['approved', 'pending'])
            ->whereNull('ended_at')
            ->get();

        $ids = [];
        foreach ($relationships as $rel) {
            $rel->update([
                'ended_at' => now()->toDateString(),
                'ended_reason' => 'disownment',
            ]);
            $ids[] = $rel->id;
        }

        return $ids;
    }

    /**
     * Jalankan kedua validasi pembuatan relasi sekaligus. Lempar ValidationException jika salah satu gagal.
     */
    public static function assertValid(Person $subject, Person $object, string $type, string $subjectId, string $objectId): void
    {
        self::assertSameFamilyUnit($subject, $object);
        self::assertSingleActiveSpouse($type, $subjectId, $objectId);
    }
}
