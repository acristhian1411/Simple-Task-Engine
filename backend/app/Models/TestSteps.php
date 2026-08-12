<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSteps extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'test_case_id',
        'step_number',
        'action',
        'expected',
        'type'
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCases::class, 'test_case_id');
    }
}
