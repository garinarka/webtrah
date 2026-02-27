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
        'created_by'
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'is_biological' => 'boolean',
        'metadata' => 'array',
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
}
