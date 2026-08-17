<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;

class Recordings extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'title',
        'status',
        'file_path',
        'mime_type',
        'duration_ms',
        'file_size_bytes',
        'console_log_path',
        'network_log_path',
        'recordable_type',
        'recordable_id',
        'recorded_by_id',
        'finished_at',
    ];

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}