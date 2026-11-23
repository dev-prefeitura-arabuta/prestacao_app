<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityAttachment extends Model
{
    protected $table = 'public_accountability_attachments';

    public $timestamps = false;

    protected $fillable = [
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
