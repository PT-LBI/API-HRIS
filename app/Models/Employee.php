<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
 
    use HasFactory;

    protected $table = 'employees';
    protected $primaryKey = 'employee_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_code',
        'employee_name',
        'employee_address',
        'employee_identity_address',
        'employee_identity_number',
        'employee_npwp',
        'employee_bpjs_kes',
        'employee_bpjs_tk',
        'employee_place_birth',
        'employee_education_json',
        'employee_marital_status',
        'employee_number_children',
        'employee_emergency_contact',
        'employee_entry_year',
        'employee_position',
        'employee_status',
        'employee_image',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];

}
