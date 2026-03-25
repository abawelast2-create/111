<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name', 'job_title', 'pin', 'pin_changed_at', 'pin_expires_at', 'pin_rotation_days',
        'phone', 'unique_token', 'profile_photo', 'branch_id', 'device_fingerprint',
        'device_registered_at', 'device_bind_mode', 'security_level', 'is_active',
        'flexible_start_time', 'flexible_end_time', 'flexible_window_minutes',
        'hire_date', 'termination_date', 'employment_status',
        'trust_radius', 'avg_latitude', 'avg_longitude',
    ];

    protected $casts = [
        'pin_changed_at'           => 'datetime',
        'pin_expires_at'           => 'datetime',
        'device_registered_at'     => 'datetime',
        'device_bind_mode'         => 'integer',
        'security_level'           => 'integer',
        'is_active'                => 'boolean',
        'pin_rotation_days'        => 'integer',
        'flexible_window_minutes'  => 'integer',
        'hire_date'                => 'date',
        'termination_date'         => 'date',
    ];

    protected $hidden = ['unique_token'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function knownDevices(): HasMany
    {
        return $this->hasMany(KnownDevice::class);
    }

    public function tamperingCases(): HasMany
    {
        return $this->hasMany(TamperingCase::class);
    }

    public function secretReports(): HasMany
    {
        return $this->hasMany(SecretReport::class);
    }

    public function locationLogs(): HasMany
    {
        return $this->hasMany(LocationLog::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function documentGroups(): HasMany
    {
        return $this->hasMany(EmpDocumentGroup::class, 'employee_id')->orderBy('sort_order')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class);
    }

    public function isPinExpired(): bool
    {
        if (!$this->pin_expires_at) return false;
        return now()->greaterThan($this->pin_expires_at);
    }

    public function getFlexibleSchedule(): ?array
    {
        if (!$this->flexible_start_time || !$this->flexible_end_time) return null;
        return [
            'start' => $this->flexible_start_time,
            'end'   => $this->flexible_end_time,
            'window'=> $this->flexible_window_minutes,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('unique_token', $token)
            ->where('is_active', true)
            ->first();
    }

    public static function findByPin(string $pin): ?self
    {
        return static::where('pin', $pin)
            ->where('is_active', true)
            ->first();
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::withTrashed()->where('unique_token', $token)->exists());
        return $token;
    }

    public static function generatePinFromPhone(?string $phone): string
    {
        if ($phone) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($digits) >= 4) {
                $pin = substr($digits, -4);
                if (!static::withTrashed()->where('pin', $pin)->exists()) {
                    return $pin;
                }
            }
        }
        return static::generateUniquePin();
    }

    public static function generateUniquePin(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('pin', $pin)->exists());
        return $pin;
    }
}
