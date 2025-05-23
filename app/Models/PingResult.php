<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PingResult extends Model
{
    protected $fillable = ['ip_address', 'status', 'packet_loss', 'message'];

    // Relasi belongsTo ke DeviceDetail
    public function deviceDetail()
    {
        return $this->belongsTo(DeviceDetail::class, 'ip_address', 'ip_address');
    }
}
