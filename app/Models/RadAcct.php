<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    protected $connection = 'radius';

    protected $table = 'radacct';

    public $timestamps = false;

    protected $primaryKey = 'radacctid';

    protected $fillable = [
        'acctsessionid',
        'acctuniqueid',
        'username',
        'nasipaddress',
        'nasportid',
        'acctstarttime',
        'acctstoptime',
        'acctinputoctets',
        'acctoutputoctets',
        'calledstationid',
        'callingstationid',
        'framedipaddress',
        'acctsessiontime',
    ];

    protected $casts = [
        'acctstarttime' => 'datetime',
        'acctstoptime' => 'datetime',
    ];

    /**
     * Scope untuk sesi yang masih aktif
     */
    public function scopeActive($query)
    {
        return $query->whereNull('acctstoptime');
    }

    /**
     * Scope untuk sesi yang sudah selesai
     */
    public function scopeInactive($query)
    {
        return $query->whereNotNull('acctstoptime');
    }

    /**
     * Hitung durasi sesi dalam format human readable
     */
    public function getSessionDurationAttribute(): string
    {
        if ($this->acctsessiontime) {
            $hours = floor($this->acctsessiontime / 3600);
            $minutes = floor(($this->acctsessiontime % 3600) / 60);
            $seconds = $this->acctsessiontime % 60;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return '00:00:00';
    }
}
