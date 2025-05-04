<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'menu';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama_makanan',
        'harga',
        'jenis',
        'foto',
        'deskripsi',
        'id_stan',
    ];

    /**
     * Get the stan that owns the menu item.
     */
    public function stan()
    {
        return $this->belongsTo(Stan::class, 'id_stan');
    }

    /**
     * Get the discounts for the menu item.
     */
    public function diskon()
    {
        return $this->belongsToMany(Diskon::class, 'menu_diskon', 'id_menu', 'id_diskon');
    }

    /**
     * Get the detail transactions for the menu item.
     */
    public function detailTransaksi()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_menu');
    }

    /**
     * Get path to foto.
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset('storage/menu/' . $this->foto);
        }
        return asset('images/default-menu.png');
    }

    /**
     * Get active discounts for this menu item.
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
     * Get the highest active discount percentage.
     */
    public function getActiveDiskonPercentageAttribute()
    {
        $activeDiscounts = $this->activeDiskon();
        
        if ($activeDiscounts->isEmpty()) {
            return 0;
        }
        
        return $activeDiscounts->max('persentase_diskon');
    }

    /**
     * Get the discounted price.
     */
    public function getDiscountedPriceAttribute()
    {
        $discountPercentage = $this->active_diskon_percentage;
        
        if ($discountPercentage > 0) {
            return $this->harga * (1 - ($discountPercentage / 100));
        }
        
        return $this->harga;
    }

    /**
     * Scope a query to only include food items.
     */
    public function scopeMakanan($query)
    {
        return $query->where('jenis', 'makanan');
    }

    /**
     * Scope a query to only include drink items.
     */
    public function scopeMinuman($query)
    {
        return $query->where('jenis', 'minuman');
    }
}