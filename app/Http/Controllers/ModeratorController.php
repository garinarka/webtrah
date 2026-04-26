<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModeratorController extends Controller
{
    /**
     * Moderator mengajukan pengunduran diri dari unit keluarga yang dinaunginya.
     * Memerlukan persetujuan admin sebelum unit-moderator link dilepas.
     */
    public function resign(Request $request)
    {
        $user = auth()->user();

        if (! $user->isModerator()) {
            abort(403, 'Hanya moderator yang bisa mengajukan pengunduran diri.');
        }

        $request->validate([
            'family_unit_id' => ['required', 'exists:family_units,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'family_unit_id.required' => 'Pilih unit keluarga yang ingin ditinggalkan.',
            'reason.required' => 'Alasan pengunduran diri wajib diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
        ]);

        $familyUnitId = $request->family_unit_id;

        // Pastikan moderator ini memang mengelola unit yang dimaksud
        if (! $user->managesUnit($familyUnitId)) {
            abort(403, 'Kamu tidak terikat dengan unit keluarga ini.');
        }

        // Cek apakah sudah ada pengajuan resign yang pending untuk unit ini
        $existing = Approval::where('status', 'pending')
            ->where('requested_by', $user->id)
            ->where('action', 'resign_moderator')
            ->whereJsonContains('changes->family_unit_id->old', $familyUnitId)
            ->first();

        if ($existing) {
            return back()->withErrors(['error' => 'Sudah ada pengajuan pengunduran diri yang masih menunggu persetujuan.']);
        }

        DB::beginTransaction();
        try {
            $familyUnit = \App\Models\FamilyUnit::find($familyUnitId);

            $approval = Approval::create([
                'approvable_type' => null,   // resign tidak punya approvable model
                'approvable_id' => null,
                'action' => 'resign_moderator',
                'changes' => [
                    'family_unit_id' => ['old' => $familyUnitId, 'new' => null],
                    'moderator_name' => ['old' => $user->name,   'new' => null],
                    'unit_name' => ['old' => $familyUnit?->name, 'new' => null],
                    'reason' => ['old' => null, 'new' => $request->reason],
                ],
                'status' => 'pending',
                'requested_by' => $user->id,
            ]);

            NotificationService::notifyAdminsOfNewApproval(
                $approval->load(['requester'])
            );

            DB::commit();

            return back()->with('message', 'Pengajuan pengunduran diri telah dikirim ke admin.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
