<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    use HasFactory;
    protected $table = 'master_petty_cash';
    protected $primaryKey = 'petty_cash_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'petty_cash_name',
        'petty_cash_account_name',
        'petty_cash_account_number',
        'petty_cash_current_balance',
        'petty_cash_first_balance',
    ];

    protected $casts = [
        'petty_cash_current_balance' => 'float',
        'petty_cash_first_balance' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
