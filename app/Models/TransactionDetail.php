<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;
    protected $table = 'transaction_details';
    protected $primaryKey = 'transaction_detail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_detail_id',
        'transaction_detail_transaction_id',
        'transaction_detail_product_id',
        'transaction_detail_product_sku',
        'transaction_detail_product_name',
        'transaction_detail_qty',
        'transaction_detail_price_unit',
        'transaction_detail_total_price',
        'transaction_detail_adjust_price',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'transaction_detail_qty' => 'float',
        'transaction_detail_price_unit' => 'float',
        'transaction_detail_total_price' => 'float',
        'transaction_detail_adjust_price' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
