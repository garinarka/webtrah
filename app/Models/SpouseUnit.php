<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpouseUnit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['started_at', 'ended_at', 'ended_reason'];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];
}
