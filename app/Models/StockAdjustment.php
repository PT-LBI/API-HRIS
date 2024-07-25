<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;
    protected $table = 'stock_adjustments';
    protected $primaryKey = 'stock_adjustment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_adjustment_id',
        'stock_adjustment_date',
        'stock_adjustment_user_id',
        'stock_adjustment_code',
        'stock_adjustment_status',
        'stock_adjustment_total_product',
        'stock_adjustment_note',
        'created_at',
    ];

    protected $casts = [
        'stock_adjustment_total_product' => 'float',
        'total_add' => 'float',
        'total_out' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
