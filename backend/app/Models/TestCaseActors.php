<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestCaseActors extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
}
