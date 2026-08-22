<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Audit extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    // use SoftDeletes;
    protected $table = "audits";
    protected $primaryKey = "id";
    protected $fillable = ['user_type', 'user_id', 'created_at', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'url', 'ip_address', 'user_agent', 'target'];
    // protected $hidden = ["updated_at"];
    protected $dateFormat = 'Y-m-d H:i:s';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
