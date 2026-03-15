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

    /**
     * Cast 'date:Y-m-d' — serialize ke JSON sebagai "YYYY-MM-DD" bukan ISO timestamp.
     * 
     * Sebelumnya cast 'date' menyebabkan Carbon serialize ke "2007-09-14T17:00:00.000000Z"
     * (UTC midnight dari timezone Asia/Jakarta) sehingga frontend menerima tanggal -1 hari.
     * Dengan format 'date:Y-m-d', output JSON selalu "2007-09-15" tanpa timezone noise.
     */
    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'death_date' => 'date:Y-m-d',
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
}
