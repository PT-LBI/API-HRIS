<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBank extends Model
{
    use HasFactory;

    protected $table = 'master_banks';
    protected $primaryKey = 'bank_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_current_balance',
        'bank_first_balance',
    ];

    protected $casts = [
        'bank_current_balance' => 'float',
        'bank_first_balance' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
