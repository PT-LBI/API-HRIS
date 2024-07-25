<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategories extends Model
{
    use HasFactory;

    protected $table = 'product_categories';
    protected $primaryKey = 'category_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_name',
        'category_status',
    ];
}
