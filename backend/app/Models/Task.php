<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use SoftDeletes;
    
    protected $fillable = ["list_id", "title", "description", "status", "order"];

    public function list(): BelongsTo
    {
        return $this->belongsTo(ListModel::class, "list_id");
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, "task_id");
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(TaskDependency::class, "depends_on_task_id");
    }
}
