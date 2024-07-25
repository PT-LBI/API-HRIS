<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipNoteCategory extends Model
{
    use HasFactory;

    protected $table = 'ship_note_categories';
    protected $primaryKey = 'ship_note_category_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ship_note_category_name',
        'ship_note_category_is_deleted',
        'created_at',
        'deleted_at',
    ];
}
