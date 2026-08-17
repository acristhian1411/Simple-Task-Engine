<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TestCases extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'preconditions',
        'component_id',
        'postconditions',
        'expected_result',
        'status'
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Components::class, 'component_id');
    }

    public function testSteps(): HasMany
    {
        return $this->hasMany(TestSteps::class, 'test_case_id');
    }

    public function actors(): HasMany
    {
        return $this->hasMany(TestCaseActors::class, 'test_case_id');
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bugs::class, 'test_case_id');
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
