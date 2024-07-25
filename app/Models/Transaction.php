<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $primaryKey = 'transaction_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'transaction_date',
        'transaction_number',
        'transaction_customer_id',
        'transaction_pic_id',
        'transaction_pic_name',
        'transaction_total_product',
        'transaction_total_product_qty',
        'transaction_subtotal',
        'transaction_tax',
        'transaction_tax_ppn',
        'transaction_config_tax',
        'transaction_config_tax_ppn',
        'transaction_type',
        'transaction_disc_type',
        'transaction_disc_percent',
        'transaction_disc_nominal',
        'transaction_shipping_cost',
        'transaction_grandtotal',
        'transaction_status',
        'transaction_status_delivery',
        'transaction_payment_method',
        'transaction_payment_bank_id',
        'transaction_payment_status',
        'transaction_travel_doc',
        'transaction_chart_account_id',
        'transaction_note',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        
    ];

    protected $casts = [
        'transaction_total_product' => 'integer',
        'transaction_total_product_qty' => 'float',
        'transaction_tax' => 'float',
        'transaction_tax_ppn' => 'float',
        'transaction_config_tax' => 'float',
        'transaction_config_tax_ppn' => 'float',
        'transaction_disc_percent' => 'float',
        'transaction_disc_nominal' => 'float',
        'transaction_shipping_cost' => 'float',
        'transaction_subtotal' => 'float',
        'transaction_grandtotal' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
