<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuDiskon extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'menu_diskon';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id_menu',
        'id_diskon',
    ];

    /**
     * Get the menu associated with the menu_diskon.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }

    /**
     * Get the diskon associated with the menu_diskon.
     */
    public function diskon()
    {
        return $this->belongsTo(Diskon::class, 'id_diskon');
    }
}