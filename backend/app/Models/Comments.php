<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Comments extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

}
