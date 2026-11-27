<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'password_hashed',
        'role',
        'avatar',
        'notifications_enabled',
    ];
}
