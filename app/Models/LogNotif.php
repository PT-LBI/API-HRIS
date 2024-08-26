<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogNotif extends Model
{
    use HasFactory;
    protected $table = 'log_notif';
    protected $primaryKey = 'log_notif_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'log_notif_user_id',
        'log_notif_data_json',
        'log_notif_is_read',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];
}
