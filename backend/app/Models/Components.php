<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Components extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Components::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Components::class, 'parent_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_component', 'component_id', 'task_id')
            ->whereNull('tasks.deleted_at');
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(TestCases::class, 'component_id');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Components::class, 'component_dependencies', 'component_id', 'depends_on_id')
            ->withPivot('criticality');
    }

    public function criticalDependencies(): BelongsToMany
    {
        return $this->belongsToMany(Components::class, 'component_dependencies', 'component_id', 'depends_on_id')
            ->withPivot('criticality')
            ->wherePivot('criticality', 'critical');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Components::class, 'component_dependencies', 'depends_on_id', 'component_id')
            ->withPivot('criticality');
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
