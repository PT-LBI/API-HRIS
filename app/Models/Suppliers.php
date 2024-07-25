<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_name',
        'supplier_type',
        'supplier_other_name',
        'supplier_phone_number',
        'supplier_identity_number',
        'supplier_npwp',
        'supplier_address',
        'supplier_province_id',
        'supplier_province_name',
        'supplier_district_id',
        'supplier_district_name',
        'supplier_status',
        'supplier_image',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // protected function casts(): array
    // {
        // return [
        //     'email_verified_at' => 'datetime',
        //     'password' => 'hashed',
        // ];
    // }
}
