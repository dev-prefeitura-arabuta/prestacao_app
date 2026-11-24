<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConfig extends Model
{
    protected $table = 'config_whatsapp';

    protected $fillable = [
        'api_url',
        'api_key',
        'session_id',
        'session_name',
        'engine_type',
        'default_country_code',
        'timeout',
        'webhook_url',
        'status',
    ];
}
