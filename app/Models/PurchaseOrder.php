<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'pos';
    protected $primaryKey = 'po_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'po_id',
        'po_supplier_id',
        'po_number',
        'po_pic_id',
        'po_date',
        'po_total_product',
        'po_total_product_qty',
        'po_subtotal',
        'po_tax',
        'po_tax_ppn',
        'po_config_tax',
        'po_config_tax_ppn',
        'po_grandtotal',
        'po_status',
        'po_status_receiving',
        'po_status_ship',
        'po_ship_id',
        'po_type',
        'po_payment_method',
        'po_payment_bank_id',
        'po_payment_status',
        'po_chart_account_id',
        'po_note',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        // 'po_tax',
    ];

    protected $casts = [
        'po_total_product' => 'integer',
        'po_total_product_qty' => 'float',
        'po_subtotal' => 'float',
        'po_tax' => 'float',
        'po_tax_ppn' => 'float',
        'po_config_tax' => 'float',
        'po_config_tax_ppn' => 'float',
        'po_grandtotal' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

}
