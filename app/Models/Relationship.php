<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relationship extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'subject_id',
        'object_id',
        'type',
        'spouse_unit_id',
        'started_at',
        'ended_at',
        'ended_reason',
        'is_biological',
        'metadata',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'started_at'  => 'date',
        'ended_at'    => 'date',
        'is_biological' => 'boolean',
        'metadata'    => 'array',
        'approved_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'subject_id');
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'object_id');
    }

    public function spouseUnit(): BelongsTo
    {
        return $this->belongsTo(SpouseUnit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── HELPERS ────────────────────────────────────────────────────────────

    public function typeLabel(): string
    {
        return [
            'parent'          => 'Orang Tua',
            'child'           => 'Anak',
            'spouse'          => 'Pasangan',
            'step_parent'     => 'Orang Tua Tiri',
            'step_child'      => 'Anak Tiri',
            'adopted_parent'  => 'Orang Tua Angkat',
            'adopted_child'   => 'Anak Angkat',
        ][$this->type] ?? $this->type;
    }
}
