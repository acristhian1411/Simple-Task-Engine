<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskDependency extends Model
{
    use SoftDeletes;
    protected $fillable = ["task_id", "depends_on_task_id"];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, "task_id");
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Task::class, "depends_on_task_id");
    }
}
