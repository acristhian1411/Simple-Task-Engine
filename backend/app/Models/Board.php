<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use SoftDeletes;
    protected $fillable = ["title", "description", "user_id"];

    public function lists(): HasMany
    {
        return $this->hasMany(ListModel::class, "board_id");
    }
}
