<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayroll extends Model
{
    use HasFactory;
    protected $table = 'user_payrolls';
    protected $primaryKey = 'user_payroll_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_payroll_user_id',
        'user_payroll_payroll_id',
        'user_payroll_value',
        'user_payroll_overtime_hour_total',
        'user_payroll_overtime_hour',
        'user_payroll_transport',
        'user_payroll_communication',
        'user_payroll_meal_allowance',
        'user_payroll_absenteeism_cut',
        'user_payroll_bpjs',
        'user_payroll_total_accepted',
        'user_payroll_status',
        'user_payroll_month',
        'user_payroll_year',
        'user_payroll_income_json',
        'user_payroll_income_total',
        'user_payroll_deduct_json',
        'user_payroll_deduct_total',
        'user_payroll_sent_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'user_payroll_value' => 'float',
        'user_payroll_meal_allowance' => 'float',
        'user_payroll_absenteeism_cut_total' => 'float',
        'user_payroll_income_total' => 'float',
        'user_payroll_deduct_total' => 'float',
        'user_payroll_overtime_hour_total' => 'float',
        'user_payroll_overtime_hour' => 'float',
        'user_payroll_transport' => 'float',
        'user_payroll_communication' => 'float',
        'user_payroll_absenteeism_cut' => 'float',
        'user_payroll_bpjs' => 'float',
        'user_payroll_total_accepted' => 'float',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
