<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'stan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama_stan',
        'nama_pemilik',
        'telp',
        'id_user',
    ];

    /**
     * Get the user that owns the stan.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Get the menu items for the stan.
     */
    public function menu()
    {
        return $this->hasMany(Menu::class, 'id_stan');
    }

    /**
     * Get the discounts for the stan.
     */
    public function diskon()
    {
        return $this->hasMany(Diskon::class, 'id_stan');
    }

    /**
     * Get the transactions for the stan.
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_stan');
    }

    /**
     * Get active discounts (current date falls between start and end date).
     */
    public function activeDiskon()
    {
        $now = now();
        return $this->diskon()
            ->where('tanggal_awal', '<=', $now)
            ->where('tanggal_akhir', '>=', $now)
            ->get();
    }

    /**
     * Get the stan's transactions for a specific month.
     */
    public function transaksiByMonth($month, $year)
    {
        return $this->transaksi()
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    /**
     * Get the stan's income for a specific month.
     */
    public function incomeByMonth($month, $year)
    {
        $transactions = $this->transaksiByMonth($month, $year);
        
        $total = 0;
        foreach ($transactions as $transaction) {
            foreach ($transaction->detailTransaksi as $detail) {
                $total += $detail->harga_beli * $detail->qty;
            }
        }
        
        return $total;
    }
}