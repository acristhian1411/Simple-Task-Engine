<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestCaseActors extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'test_case_id',
        'actor_name',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCases::class, 'test_case_id');
    }
}
