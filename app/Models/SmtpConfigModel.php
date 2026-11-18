<?php

namespace App\Models;

use CodeIgniter\Model;

class SmtpConfigModel extends Model
{
    protected $table            = 'config_smtp';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
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

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
