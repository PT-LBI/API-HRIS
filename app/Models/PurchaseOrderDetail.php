<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'po_details';
    protected $primaryKey = 'po_detail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_detail_id',
        'po_detail_po_id',
        'po_detail_product_id',
        'po_detail_product_name',
        'po_detail_product_sku',
        'po_detail_product_category_id',
        'po_detail_product_category_name',
        'po_detail_qty',
        'po_detail_product_purchase_price',
        'po_detail_subtotal',
        'created_at',
    ];

    protected $casts = [
        'po_detail_product_purchase_price' => 'float',
        'po_detail_qty' => 'float',
        'po_detail_subtotal' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
