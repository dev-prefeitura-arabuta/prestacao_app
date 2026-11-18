<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountabilityAttachmentModel extends Model
{
    protected $table            = 'public_accountability_attachments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'accountability_id',
        'item_number',
        'user_id',
        'original_name',
        'file_name',
        'file_path',
        'file_ext',
        'file_size',
        'created_at',
    ];
}
