<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardStock extends Model
{
    use HasFactory;
    protected $table = 'card_stocks';
    protected $primaryKey = 'card_stock_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'card_stock_id',
        'card_stock_product_id',
        'card_stock_in',
        'card_stock_out',
        'card_stock_actual',
        'card_stock_diff',
        'card_stock_diff_label',
        'card_stock_adjustment_total',
        'card_stock_adjustment_total_label',
        'card_stock_nominal',
        'card_stock_nominal_label',
        'card_stock_type',
        'card_stock_status',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
      return [
          'created_at' => 'datetime:Y-m-d H:i:s',
          'card_stock_nominal' => 'float',
          'card_stock_in' => 'float',
          'card_stock_out' => 'float',
          'card_stock_actual' => 'float',
          'card_stock_diff' => 'float',
          'card_stock_adjustment_total' => 'float',
      ];
    }
}
