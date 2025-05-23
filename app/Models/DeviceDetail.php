<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceDetail extends Model
{
    protected $fillable = ['device_name', 'device_type', 'ip_address'];

    // Relasi hasMany ke PingResult
    public function pingResults()
    {
        return $this->hasMany(PingResult::class, 'ip_address', 'ip_address');
    }
}
