<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterShip extends Model
{
    use HasFactory;

    protected $table = 'master_ships';
    protected $primaryKey = 'ship_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ship_name',
        'ship_number',
        'ship_captain',
        'ship_main_machine',
        'ship_propeler',
        'ship_gt',
        'ship_type_id',
        'ship_last_dock',
        'ship_compressor',
        'ship_compressor_driving_machine',
        'ship_skat',
        'ship_sipi',
        'ship_eligibility_letter',
        'ship_status',
        'ship_image',
        'ship_is_deleted',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'ship_is_deleted',
    ];
}
