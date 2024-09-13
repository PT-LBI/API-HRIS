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
        'presence_schedule_id',
        'presence_in_time',
        'presence_in_photo',
        'presence_out_time',
        'presence_out_photo',
        'presence_extra_time',
        'presence_status',
        'presence_in_longitude',
        'presence_in_latitude',
        'presence_out_longitude',
        'presence_out_latitude',
        'presence_max_distance',
        'presence_in_note',
        'presence_out_note',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'presence_max_distance' => 'float',
        'presence_in_time' => 'datetime:Y-m-d H:i:s',
        'presence_out_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
