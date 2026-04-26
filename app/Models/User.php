<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'person_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── ROLE HELPERS ───────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    // ── RELATIONSHIPS ──────────────────────────────────────────────────────

    /**
     * Unit-unit keluarga yang dikelola moderator ini (max 3).
     * Menggunakan pivot table moderator_family_units.
     */
    public function managedFamilyUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            FamilyUnit::class,
            'moderator_family_units',
            'user_id',
            'family_unit_id'
        )->withTimestamps();
    }

    /**
     * IDs unit keluarga yang dikelola — shortcut untuk query/policy.
     */
    /**
     * IDs unit keluarga yang dikelola — shortcut untuk query/policy.
     * Menggunakan raw DB query agar tidak ada ambiguitas kolom 'id'
     * saat JOIN antara tabel family_units dan moderator_family_units di PostgreSQL.
     */
    public function getManagedUnitIdsAttribute(): array
    {
        return \Illuminate\Support\Facades\DB::table('moderator_family_units')
            ->where('user_id', $this->id)
            ->pluck('family_unit_id')
            ->toArray();
    }

    /**
     * Apakah moderator ini mengelola unit keluarga dengan ID tertentu?
     * Menggunakan raw DB query untuk menghindari JOIN ambiguity di PostgreSQL.
     */
    public function managesUnit(string $familyUnitId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::table('moderator_family_units')
            ->where('user_id', $this->id)
            ->where('family_unit_id', $familyUnitId)
            ->exists();
    }

    /**
     * Data Person yang terasosiasi dengan user ini (untuk role user).
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
