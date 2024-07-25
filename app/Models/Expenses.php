<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory;
    protected $table = 'expenses';
    protected $primaryKey = 'expenses_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expenses_id',
        'expenses_number',
        'expenses_date',
        'expenses_chart_account_id',
        // 'expenses_config_tax',
        'expenses_amount',
        'expenses_status',
        'expenses_pic_id',
        'expenses_note',
        'expenses_is_deleted',
        'expenses_image',
        'expenses_bank_id',
        'expenses_tax_ppn',
        'expenses_config_tax_ppn',
        'expenses_type',
        'expenses_ship_id',
        'created_at',
    ];

    protected $casts = [
        'expenses_amount' => 'float',
        'expenses_tax_ppn' => 'float',
        'expenses_config_tax_ppn' => 'float',
        'expenses_date' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
