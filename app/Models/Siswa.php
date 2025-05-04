<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'siswa';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama_siswa',
        'alamat',
        'telp',
        'id_user',
        'foto',
    ];

    /**
     * Get the user that owns the siswa profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Get the transactions for the siswa.
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_siswa');
    }

    /**
     * Get path to foto.
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset('storage/siswa/' . $this->foto);
        }
        return asset('images/default-profile.png');
    }

    /**
     * Get the siswa transactions for a specific month.
     */
    public function transaksiByMonth($month, $year)
    {
        return $this->transaksi()
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'desc')
            ->get();
    }
}