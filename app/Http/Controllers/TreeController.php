<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class TreeController extends Controller
{
    public function index()
    {
        // kirim daftar people aktif untuk root selector
        $people = Person::where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'gender', 'birth_date']);

        return Inertia::render('Tree/Index', [
            'people' => $people,
        ]);
    }

    /**
     * api endpoint — return nodes + edges untuk tree rendering.
     * bfs bi-directional: ancestors ke atas + descendants ke bawah + pasangan.
     */
    public function data(Request $request)
    {
        $request->validate([
            'root_id'    => 'nullable|exists:people,id',
            'depth_up'   => 'nullable|integer|min:0|max:5',
            'depth_down' => 'nullable|integer|min:0|max:5',
        ]);

        // default: ambil person pertama yang active
        $rootId    = $request->root_id ?? Person::where('status', 'active')->value('id');
        $depthUp   = (int) ($request->depth_up   ?? 2); // ke atas (ancestors)
        $depthDown = (int) ($request->depth_down ?? 2); // ke bawah (descendants)

        if (!$rootId) {
            return response()->json(['root_id' => null, 'nodes' => [], 'edges' => []]);
        }

        $nodeIds    = collect([$rootId]);
        $processed  = collect();

        // bfs ke atas (ancestors) ───────────────────────────────────────
        $currentLevel = collect([$rootId]);
        for ($i = 0; $i < $depthUp; $i++) {
            if ($currentLevel->isEmpty()) break;

            // orang tua: subject_id = orang tua, object_id = current persons
            $parents = Relationship::whereIn('object_id', $currentLevel)
                ->whereIn('type', ['parent', 'step_parent', 'adopted_parent'])
                ->whereNull('ended_at')
                ->where('status', 'approved')
                ->pluck('subject_id');

            $currentLevel = $parents->diff($nodeIds);
            $nodeIds = $nodeIds->merge($parents);
        }

        // bfs ke bawah (descendants)
        $currentLevel = collect([$rootId]);
        for ($i = 0; $i < $depthDown; $i++) {
            if ($currentLevel->isEmpty()) break;

            // anak: subject_id = orang tua (current persons), object_id = anak
            $children = Relationship::whereIn('subject_id', $currentLevel)
                ->whereIn('type', ['parent', 'step_parent', 'adopted_parent'])
                ->whereNull('ended_at')
                ->where('status', 'approved')
                ->pluck('object_id');

            $currentLevel = $children->diff($nodeIds);
            $nodeIds = $nodeIds->merge($children);
        }

        // tambahkan pasangan dari semua node yang sudah dikumpulkan
        $spouseIds = Relationship::where(function ($q) use ($nodeIds) {
            $q->whereIn('subject_id', $nodeIds)->orWhereIn('object_id', $nodeIds);
        })
            ->where('type', 'spouse')
            ->where('status', 'approved')
            ->get(['subject_id', 'object_id'])
            ->flatMap(fn($r) => [$r->subject_id, $r->object_id])
            ->unique()
            ->diff($nodeIds);

        $nodeIds = $nodeIds->merge($spouseIds)->unique();

        // ambil data semua nodes
        $nodes = Person::whereIn('id', $nodeIds)
            ->select('id', 'display_name', 'gender', 'birth_date', 'death_date', 'status')
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'name'       => $p->display_name,
                'gender'     => $p->gender,
                'birth_year' => $p->birth_date?->year,
                'death_year' => $p->death_date?->year,
                'status'     => $p->status,
                'is_root'    => $p->id === $rootId,
            ]);

        // ambil semua edges di antara nodes yang terpilih
        $edges = Relationship::where(function ($q) use ($nodeIds) {
            $q->whereIn('subject_id', $nodeIds)->whereIn('object_id', $nodeIds);
        })
            ->whereIn('type', ['parent', 'step_parent', 'adopted_parent', 'spouse'])
            ->whereNull('ended_at')
            ->where('status', 'approved')
            ->get(['id', 'subject_id as source', 'object_id as target', 'type', 'is_biological', 'started_at', 'ended_at'])
            ->map(fn($r) => [
                'id'           => $r->id,
                'source'       => $r->source,
                'target'       => $r->target,
                'type'         => $r->type,
                'is_biological' => $r->is_biological,
            ]);

        return response()->json([
            'root_id' => $rootId,
            'nodes'   => $nodes->values(),
            'edges'   => $edges->values(),
        ]);
    }

    /**
     * search people untuk autocomplete root selector.
     */
    public function searchPeople(Request $request)
    {
        $q = $request->get('q', '');
        $people = Person::where('status', 'active')
            ->where('display_name', 'ilike', "%{$q}%")
            ->orderBy('display_name')
            ->limit(10)
            ->get(['id', 'display_name', 'gender', 'birth_date']);

        return response()->json($people);
    }
}
