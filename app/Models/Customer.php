<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
 
    use HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'user_phone_number',
        'user_role',
        'user_identity_number',
        'user_npwp',
        'user_province_id',
        'user_province_name',
        'user_district_id',
        'user_district_name',
        'user_address',
        'user_position',
        'user_status',
        'user_profile_url',
        'image',
        'user_desc',
        'user_join_date',
        'created_at',
        'updated_at',
        'user_is_deleted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        'user_is_deleted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
      return [
          'email_verified_at' => 'datetime',
          'password' => 'hashed',
      ];
    }
}
