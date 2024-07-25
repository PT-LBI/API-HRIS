<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankLog extends Model
{
    use HasFactory;
    protected $table = 'bank_logs';
    protected $primaryKey = 'log_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'log_id',
        'log_bank_id',
        'log_user_id',
        'log_po_id',
        'log_transaction_id',
        'log_expenses_id',
        'log_amount',
        'log_balance_before',
        'log_balance_after',
        'log_type',
        'log_note',
        'created_at',
    ];

    protected $casts = [
        'log_amount' => 'float',
        'log_balance_before' => 'float',
        'log_balance_after' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
