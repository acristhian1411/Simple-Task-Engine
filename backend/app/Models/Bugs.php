<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
class Bugs extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'severity',
        'test_case_id',
        'test_step_id',
        'reported_by_id',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCases::class, 'test_case_id');
    }

    public function testStep(): BelongsTo
    {
        return $this->belongsTo(TestSteps::class, 'test_step_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_bug', 'bug_id', 'task_id')
            ->withPivot('relation_type')
            ->whereNull('tasks.deleted_at');
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
