<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'detail_transaksi';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id_transaksi',
        'id_menu',
        'qty',
        'harga_beli',
    ];

    /**
     * Get the transaction that owns the detail.
     */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }

    /**
     * Get the menu item associated with the detail.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }

    /**
     * Calculate the subtotal.
     */
    public function getSubtotalAttribute()
    {
        return $this->harga_beli * $this->qty;
    }
}