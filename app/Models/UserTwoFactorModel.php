<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserTwoFactorModel extends Model
{
    protected $table            = 'user_two_factor';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id',
        'secret',
        'recovery_codes',
        'confirmed_at',
        'last_used_timestep',
    ];
}
