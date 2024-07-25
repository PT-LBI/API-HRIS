<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPayment extends Model
{
    use HasFactory;
    protected $table = 'transaction_payments';
    protected $primaryKey = 'transaction_payment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_payment_id',
        'transaction_payment_transaction_id',
        'transaction_payment_method',
        'transaction_payment_bank_id',
        'transaction_payment_status',
        'transaction_payment_amount',
        'transaction_payment_installment',
        'transaction_payment_remaining',
        'transaction_payment_note',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'transaction_payment_amount' => 'float',
        'transaction_payment_installment' => 'float',
        'transaction_payment_remaining' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
