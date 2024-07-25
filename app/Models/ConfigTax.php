<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigTax extends Model
{
    use HasFactory;
    protected $table = 'config_tax';
    protected $primaryKey = 'tax_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tax_value',
        'tax_type',
        'tax_ppn_value',
        'tax_ppn_type',
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
          'updated_at' => 'datetime:Y-m-d H:i:s',
          'tax_value' => 'float',
          'tax_ppn_value' => 'float',
      ];
    }
}
