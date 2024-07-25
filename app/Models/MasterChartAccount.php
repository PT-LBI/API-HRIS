<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterChartAccount extends Model
{
    use HasFactory;

    protected $table = 'master_chart_accounts';
    protected $primaryKey = 'master_chart_account_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'master_chart_account_name',
        'master_chart_account_code',
        'master_chart_account_type',
        'master_chart_account_is_deleted',
        'created_at',
        'updated_at',
    ];
    
}
