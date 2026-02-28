<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
        'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit log entry for any model.
     */
    public static function record(
        Model $model,
        string $event,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'event'          => $event,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'user_id'        => auth()->id(),
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'url'            => Request::fullUrl(),
        ]);
    }

    /**
     * Get readable diff between old and new values.
     */
    public function getDiff(): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values ?? []),
            array_keys($this->new_values ?? [])
        ));

        foreach ($allKeys as $key) {
            $old = $this->old_values[$key] ?? null;
            $new = $this->new_values[$key] ?? null;

            if ($old !== $new) {
                $diff[] = [
                    'field' => $key,
                    'old'   => $old,
                    'new'   => $new,
                ];
            }
        }

        return $diff;
    }
}
