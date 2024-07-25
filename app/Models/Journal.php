<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;
    protected $table = 'journals';
    protected $primaryKey = 'journal_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'journal_id',
        'journal_date',
        'journal_name',
        'journal_chart_account_id',
        'journal_po_id',
        'journal_transaction_id',
        'journal_expenses_id',
        'journal_debit',
        'journal_credit',
        'created_at',
    ];

    protected $casts = [
        'journal_debit' => 'float',
        'journal_credit' => 'float',
        'journal_date' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
