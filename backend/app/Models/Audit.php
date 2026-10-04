<?php

namespace App\Models;

use OwenIt\Auditing\Models\Audit as BaseAudit;

// Audit rows are the audit package's storage model, not auditable domain models.
// Extending the package model prevents each audit insert from recursively
// generating another audit record.
class Audit extends BaseAudit
{
    protected $table = "audits";
    protected $primaryKey = "id";
    protected $fillable = ['user_type', 'user_id', 'created_at', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'url', 'ip_address', 'user_agent'];
    // protected $hidden = ["updated_at"];
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
