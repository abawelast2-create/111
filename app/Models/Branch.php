<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'latitude', 'longitude', 'geofence_radius',
        'allowed_ip_ranges', 'city', 'wifi_ssids',
        'work_start_time', 'work_end_time',
        'check_in_start_time', 'check_in_end_time',
        'check_out_start_time', 'check_out_end_time',
        'checkout_show_before', 'allow_overtime',
        'overtime_start_after', 'overtime_min_duration', 'is_active',
    ];

    protected $casts = [
        'latitude'             => 'decimal:8',
        'longitude'            => 'decimal:8',
        'geofence_radius'      => 'integer',
        'checkout_show_before' => 'integer',
        'allow_overtime'       => 'boolean',
        'overtime_start_after' => 'integer',
        'overtime_min_duration'=> 'integer',
        'is_active'            => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getSchedule(): array
    {
        return [
            'work_start_time'      => $this->work_start_time,
            'work_end_time'        => $this->work_end_time,
            'check_in_start_time'  => $this->check_in_start_time,
            'check_in_end_time'    => $this->check_in_end_time,
            'check_out_start_time' => $this->check_out_start_time,
            'check_out_end_time'   => $this->check_out_end_time,
            'checkout_show_before' => $this->checkout_show_before,
            'allow_overtime'       => $this->allow_overtime,
            'overtime_start_after' => $this->overtime_start_after,
            'overtime_min_duration'=> $this->overtime_min_duration,
        ];
    }
}
