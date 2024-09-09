<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    
    public function routeNotificationForFcm()
    {
        return $this->user_fcm_token;
    }

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
 
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'user_name',
        'user_code',
        'user_phone_number',
        'user_role',
        'user_identity_number',
        'user_identity_address',
        'user_npwp',
        'user_bpjs_kes',
        'user_bpjs_tk',
        'user_place_birth',
        'user_date_birth',
        'user_gender',
        'user_education_json',
        'user_marital_status',
        'user_number_children',
        'user_emergency_contact',
        'user_entry_year',
        'user_company_id',
        'user_division_id',
        'user_position',
        'user_province_id',
        'user_province_name',
        'user_district_id',
        'user_district_name',
        'user_address',
        'user_location_is_determined',
        'user_location_id',
        'user_status',
        'user_profile_url',
        'user_desc',
        'user_join_date',
        'user_bank_name',
        'user_account_name',
        'user_account_number',
        'user_blood_type',
        'user_fcm_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

}
