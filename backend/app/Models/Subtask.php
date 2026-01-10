<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subtask extends Model
{
    use SoftDeletes;
    protected $fillable = ["task_id", "title", "is_completed"];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
