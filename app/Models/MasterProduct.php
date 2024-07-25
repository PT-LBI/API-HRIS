<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProduct extends Model
{
    use HasFactory;

    protected $table = 'master_products';
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_name',
        'product_sku',
        'product_category_id',
        'product_hpp',
        'product_show_hpp',
        'product_sell_price',
        'product_unit',
        'product_stock',
        'product_image',
        'product_status',
        'product_price_updated_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'product_hpp' => 'float',
        'product_sell_price' => 'float',
        'product_stock_value' => 'float',
        'product_stock' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'product_price_updated_at' => 'datetime:Y-m-d H:i:s',
    ];

}
