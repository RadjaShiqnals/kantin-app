<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diskon extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'diskon';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nama_diskon',
        'persentase_diskon',
        'tanggal_awal',
        'tanggal_akhir',
        'id_stan',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tanggal_awal' => 'datetime',
        'tanggal_akhir' => 'datetime',
        'persentase_diskon' => 'double',
    ];

    /**
     * Get the stan that owns the discount.
     */
    public function stan()
    {
        return $this->belongsTo(Stan::class, 'id_stan');
    }

    /**
     * Get the menu items that have this discount.
     */
    public function menu()
    {
        return $this->belongsToMany(Menu::class, 'menu_diskon', 'id_diskon', 'id_menu');
    }

    /**
     * Check if discount is active.
     */
    public function isActive()
    {
        $now = now();
        return $now->greaterThanOrEqualTo($this->tanggal_awal) && 
               $now->lessThanOrEqualTo($this->tanggal_akhir);
    }

    /**
     * Get active status as attribute.
     */
    public function getIsActiveAttribute()
    {
        return $this->isActive();
    }

    /**
     * Get status text as attribute.
     */
    public function getStatusTextAttribute()
    {
        if ($this->isActive()) {
            return 'Aktif';
        }
        
        $now = now();
        if ($now->lessThan($this->tanggal_awal)) {
            return 'Akan Datang';
        }
        
        return 'Kedaluwarsa';
    }

    /**
     * Scope a query to only include active discounts.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('tanggal_awal', '<=', $now)
                     ->where('tanggal_akhir', '>=', $now);
    }

    /**
     * Scope a query to only include future discounts.
     */
    public function scopeUpcoming($query)
    {
        $now = now();
        return $query->where('tanggal_awal', '>', $now);
    }

    /**
     * Scope a query to only include expired discounts.
     */
    public function scopeExpired($query)
    {
        $now = now();
        return $query->where('tanggal_akhir', '<', $now);
    }
}