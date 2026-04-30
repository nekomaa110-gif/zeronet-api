<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadCheck extends Model
{
    // Gunakan koneksi radius
    protected $connection = 'radius';

    protected $table = 'radcheck';

    // FreeRADIUS tidak pakai timestamps standar Laravel
    public $timestamps = false;

    protected $fillable = [
        'username',
        'attribute',
        'op',
        'value',
    ];

    protected $hidden = [
        'value', // Sembunyikan password dari response default
    ];

    /**
     * Scope untuk filter berdasarkan attribute password
     */
    public function scopePasswordOnly($query)
    {
        return $query->where('attribute', 'Cleartext-Password');
    }

    /**
     * Scope untuk search username
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where('username', 'LIKE', "%{$keyword}%");
    }
}
