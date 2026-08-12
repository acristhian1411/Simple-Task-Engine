<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ComponentDependencies extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'component_id',
        'depends_on_id',
        'criticality',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Components::class, 'component_id');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Components::class, 'depends_on_id');
    }
}
