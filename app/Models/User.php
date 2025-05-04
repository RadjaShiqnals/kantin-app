<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Get the siswa profile associated with the user.
     */
    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'id_user');
    }

    /**
     * Get the stan profile associated with the user.
     */
    public function stan()
    {
        return $this->hasOne(Stan::class, 'id_user');
    }

    /**
     * Check if user is a student.
     */
    public function isSiswa()
    {
        return $this->role === 'siswa';
    }

    /**
     * Check if user is a stan admin.
     */
    public function isAdminStan()
    {
        return $this->role === 'admin_stan';
    }

    /**
     * Get the related profile based on role.
     */
    public function profile()
    {
        return $this->isSiswa() ? $this->siswa : $this->stan;
    }
}