<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountabilityHistory extends Model
{
    use SoftDeletes;

    protected $table = 'public_accountability_history';

    protected $fillable = [
        'accountability_id',
        'user_id',
        'status',
    ];
}
