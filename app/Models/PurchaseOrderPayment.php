<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPayment extends Model
{
    use HasFactory;
    protected $table = 'po_payments';
    protected $primaryKey = 'po_payment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_payment_id',
        'po_payment_po_id',
        'po_payment_method',
        'po_payment_bank_id',
        'po_payment_status',
        'po_payment_amount',
        'po_payment_installment',
        'po_payment_remaining',
        'po_payment_note',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'po_payment_amount' => 'float',
        'po_payment_installment' => 'float',
        'po_payment_remaining' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
