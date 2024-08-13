<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    use HasFactory;
    protected $table = 'presence';
    protected $primaryKey = 'presence_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'presence_user_id',
        'presence_shift_id',
        'presence_in',
        'presence_in_photo',
        'presence_out',
        'presence_out_photo',
        'presence_extra_time',
        'presence_status',
        'presence_longitude',
        'presence_latitude',
        'presence_max_distance',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'presence_max_distance' => 'float',
        'presence_in' => 'datetime:Y-m-d H:i:s',
        'presence_out' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
