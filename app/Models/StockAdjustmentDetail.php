<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentDetail extends Model
{
    use HasFactory;
    protected $table = 'stock_adjustment_details';
    protected $primaryKey = 'stock_adj_detail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_adj_detail_id',
        'stock_adj_detail_sa_id',
        'stock_adj_detail_product_id',
        'stock_adj_detail_in_stock',
        'stock_adj_detail_actual_stock',
        'stock_adj_detail_diff_stock',
        'stock_adj_detail_diff_stock_label',
        'stock_adj_detail_nominal',
        'stock_adj_detail_nominal_label',
        'stock_adj_detail_type',
        'stock_adj_detail_note',
        'created_at',
    ];

    protected $casts = [
        'stock_adj_detail_in_stock' => 'float',
        'stock_adj_detail_actual_stock' => 'float',
        'stock_adj_detail_diff_stock' => 'float',
        'stock_adj_detail_nominal' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
