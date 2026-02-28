<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'family_unit_id',
        'created_by',
        'status',
        'gender',
        'birth_date',
        'birth_accuracy',
        'death_date',
        'death_accuracy',
        'display_name',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    public function familyUnit(): BelongsTo
    {
        return $this->belongsTo(FamilyUnit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInFamily($query, string $familyUnitId)
    {
        return $query->where('family_unit_id', $familyUnitId);
    }

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    // ── RELATIONSHIPS ──────────────────────────────────────────────────────

    /**
     * relasi di mana orang ini adalah subject (misal: "saya adalah orang tua dari x")
     */
    public function relationshipsAsSubject()
    {
        return $this->hasMany(Relationship::class, 'subject_id');
    }

    /**
     * relasi di mana orang ini adalah object (misal: "x adalah orang tua dari saya")
     */
    public function relationshipsAsObject()
    {
        return $this->hasMany(Relationship::class, 'object_id');
    }

    /**
     * orang tua: relationships di mana object_id = this->id and type = 'parent'
     * subject = orang tua, object = anak (orang ini)
     */
    public function parents()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'object_id',   // fk ke tabel ini (anak = orang ini)
            'subject_id'   // fk ke person lain (orang tua)
        )->wherePivot('type', 'parent')
            ->wherePivot('status', 'approved')
            ->whereNull('relationships.ended_at')
            ->withPivot(['id', 'type', 'is_biological', 'status', 'started_at']);
    }

    /**
     * anak: relationships di mana subject_id = this->id and type = 'parent'
     * subject = orang tua (orang ini), object = anak
     */
    public function children()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'subject_id',  // fk ke tabel ini (orang tua = orang ini)
            'object_id'    // fk ke person lain (anak)
        )->wherePivot('type', 'parent')
            ->wherePivot('status', 'approved')
            ->whereNull('relationships.ended_at')
            ->withPivot(['id', 'type', 'is_biological', 'status', 'started_at']);
    }

    /**
     * pasangan: relationships di mana subject_id atau object_id = this->id and type = 'spouse'
     */
    public function spousesAsSubject()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'subject_id',
            'object_id'
        )->wherePivot('type', 'spouse')
            ->wherePivot('status', 'approved')
            ->withPivot(['id', 'type', 'status', 'started_at', 'ended_at', 'ended_reason', 'spouse_unit_id']);
    }

    public function spousesAsObject()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'object_id',
            'subject_id'
        )->wherePivot('type', 'spouse')
            ->wherePivot('status', 'approved')
            ->withPivot(['id', 'type', 'status', 'started_at', 'ended_at', 'ended_reason', 'spouse_unit_id']);
    }
}
