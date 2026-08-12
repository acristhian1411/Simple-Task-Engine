<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
