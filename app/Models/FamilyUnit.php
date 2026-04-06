<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FamilyUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'moderator_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // ── RELATIONSHIPS ──────────────────────────────────────────────────────

    /** Moderator yang ditugaskan ke unit ini */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /** Semua anggota (Person) yang terdaftar di unit ini */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }
}
