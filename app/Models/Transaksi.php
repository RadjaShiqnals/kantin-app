<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'transaksi';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tanggal',
        'id_stan',
        'id_siswa',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    /**
     * Get the stan associated with the transaction.
     */
    public function stan()
    {
        return $this->belongsTo(Stan::class, 'id_stan');
    }

    /**
     * Get the siswa associated with the transaction.
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }

    /**
     * Get the detail transactions for the transaction.
     */
    public function detailTransaksi()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi');
    }

    /**
     * Calculate the total amount of the transaction.
     */
    public function getTotalAttribute()
    {
        $total = 0;
        
        foreach ($this->detailTransaksi as $detail) {
            $total += $detail->harga_beli * $detail->qty;
        }
        
        return $total;
    }

    /**
     * Format the invoice number.
     */
    public function getInvoiceNumberAttribute()
    {
        return 'INV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'belum dikonfirm' => 'warning',
            'dimasak' => 'info',
            'diantar' => 'primary',
            'sampai' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'belum dikonfirm' => 'Menunggu Konfirmasi',
            'dimasak' => 'Sedang Dimasak',
            'diantar' => 'Sedang Diantar',
            'sampai' => 'Pesanan Sampai',
            default => 'Unknown',
        };
    }

    /**
     * Scope a query to only include transactions with specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter transactions by month and year.
     */
    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereMonth('tanggal', $month)
                     ->whereYear('tanggal', $year);
    }
}