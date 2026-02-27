<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TreeController extends Controller
{
    public function index()
    {
        return Inertia::render('Tree/Index');
    }

    public function data(Request $request)
    {
        $request->validate([
            'root_id' => 'nullable|exists:people,id',
            'depth' => 'nullable|integer|min:1|max:3',
        ]);

        $rootId = $request->root_id ?? Person::first()->id;
        $depth = $request->depth ?? 2;

        $nodes = $this->getNodes($rootId, $depth);
        $edges = $this->getEdges($nodes->pluck('id'));

        return response()->json([
            'root_id' => $rootId,
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }

    private function getNodes(string $rootId, int $depth)
    {
        // Get root
        $root = Person::findOrFail($rootId);
        $nodeIds = collect([$rootId]);
        $currentLevel = collect([$rootId]);

        // BFS untuk setiap level
        for ($i = 0; $i < $depth; $i++) {
            $nextLevel = Relationship::whereIn('subject_id', $currentLevel)
                ->where('type', 'parent')
                ->whereNull('ended_at')
                ->where('status', 'approved')
                ->pluck('object_id');

            $nodeIds = $nodeIds->merge($nextLevel);
            $currentLevel = $nextLevel;
        }

        return Person::whereIn('id', $nodeIds)
            ->select('id', 'display_name', 'gender', 'birth_date', 'status')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->display_name,
                'gender' => $p->gender,
                'year' => $p->birth_date ? $p->birth_date->year : null,
                'status' => $p->status,
            ]);
    }

    private function getEdges($nodeIds)
    {
        return Relationship::whereIn('subject_id', $nodeIds)
            ->whereIn('object_id', $nodeIds)
            ->where('type', 'parent')
            ->whereNull('ended_at')
            ->where('status', 'approved')
            ->select('subject_id as source', 'object_id as target', 'type')
            ->get();
    }
}
