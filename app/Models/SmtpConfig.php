<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpConfig extends Model
{
    protected $table = 'config_smtp';

    protected $fillable = [
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'email_address',
        'email_name',
        'reply_to_email',
        'smtp_auth',
        'smtp_timeout',
        'smtp_verify_ssl',
        'status',
    ];
}
