<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

}
