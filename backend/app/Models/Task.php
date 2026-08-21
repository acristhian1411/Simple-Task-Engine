<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

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

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Components::class, 'task_component', 'task_id', 'component_id')
            ->whereNull('components.deleted_at');
    }

    public function bugs(): BelongsToMany
    {
        return $this->belongsToMany(Bugs::class, 'task_bug', 'task_id', 'bug_id')
            ->withPivot('relation_type')
            ->whereNull('bugs.deleted_at');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    public function recordings(): MorphMany
    {
        return $this->morphMany(Recordings::class, 'recordable');
    }
}
