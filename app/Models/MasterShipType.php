<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShipType extends Model
{
    use HasFactory;
    protected $table = 'master_ship_types';
    protected $primaryKey = 'ship_type_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ship_type_name',
        'ship_type_is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'ship_type_is_deleted',
    ];
}
