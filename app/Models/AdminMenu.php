<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminMenu extends Model
{
    use HasFactory;
    protected $table = 'admin_menus';
    protected $primaryKey = 'menu_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'menu_id',
        'menu_parent_id',
        'menu_key',
        'menu_title',
        'menu_icon',
        'menu_role',
        'menu_status',
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
      ];
    }
}
