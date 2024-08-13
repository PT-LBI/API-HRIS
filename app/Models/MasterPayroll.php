<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPayroll extends Model
{
    use HasFactory;
    protected $table = 'master_payrolls';
    protected $primaryKey = 'payroll_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payroll_user_id',
        'payroll_value',
        'payroll_overtime_hour',
        'payroll_transport',
        'payroll_communication',
        'payroll_absenteeism_cut',
        'payroll_bpjs',
        'payroll_status',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'payroll_value' => 'float',
        'payroll_overtime_hour' => 'float',
        'payroll_transport' => 'float',
        'payroll_communication' => 'float',
        'payroll_absenteeism_cut' => 'float',
        'payroll_bpjs' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
