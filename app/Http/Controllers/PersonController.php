<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PersonController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Person::with(['familyUnit', 'creator'])
            ->where('family_unit_id', auth()->user()->family_unit_id ?? Person::first()->family_unit_id);

        // Search
        if ($request->has('search')) {
            $query->where('display_name', 'ilike', '%' . $request->search . '%');
        }

        // Filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $people = $query->orderBy('display_name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('People/Index', [
            'people' => $people,
            'filters' => $request->only(['search', 'status']),
            'can' => [
                'create' => auth()->user()->can('create_person'),
                'edit' => auth()->user()->can('edit_person'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person)
    {
        $this->authorize('view', $person);

        return Inertia::render('People/Show', [
            'person' => $person->load(['familyUnit', 'creator']),
            'can' => [
                'edit' => auth()->user()->can('edit', $person),
                'delete' => auth()->user()->can('delete', $person),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
