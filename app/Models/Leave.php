<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $table = 'leave';
    protected $primaryKey = 'leave_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'leave_user_id',
        'leave_type',
        'leave_start_date',
        'leave_end_date',
        'leave_day',
        'leave_desc',
        'leave_image',
        'leave_status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
  
    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
